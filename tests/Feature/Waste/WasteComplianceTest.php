<?php

use App\Enums\CostEntryKind;
use App\Enums\WasteCollectionSource;
use App\Enums\WasteEvidenceStatus;
use App\Enums\WasteMissedReason;
use App\Filament\Waste\Pages\HazardousRegisterPage;
use App\Filament\Waste\Pages\InvoiceCheckPage;
use App\Filament\Waste\Pages\WasteDocumentationPage;
use App\Filament\Waste\Widgets\HazardousWasteWidget;
use App\Models\Company;
use App\Models\CostEntry;
use App\Models\Organization;
use App\Models\Property;
use App\Models\StructureNode;
use App\Models\WasteContainer;
use App\Models\WasteEvidence;
use App\Models\WasteFraction;
use App\Models\WastePickup;
use App\Models\WasteSettings;
use App\Services\Waste\HazardousWasteRegister;
use App\Services\Waste\PickupService;
use App\Services\Waste\WasteCollectionRecorder;
use App\Services\Waste\WasteDocumentation;
use App\Services\Waste\WasteInvoiceCheck;
use Carbon\CarbonImmutable;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

/**
 * @return array{property: Property, paper: WasteContainer, mixed: WasteContainer}
 */
function complianceFixture(): array
{
    test()->travelTo(CarbonImmutable::parse('2026-10-15 10:00'));

    $property = Property::factory()->create(['property_number' => 'DEMO-01']);
    $node = StructureNode::factory()->create(['property_id' => $property->getKey(), 'name' => 'Müllplatz']);
    $company = Company::factory()->create(['name' => 'Muster Entsorgung GmbH']);
    $container = fn (WasteFraction $fraction, string $number): WasteContainer => WasteContainer::factory()->create([
        'property_id' => $property->getKey(),
        'structure_node_id' => $node->getKey(),
        'waste_fraction_id' => $fraction->getKey(),
        'company_id' => $company->getKey(),
        'number' => $number,
    ]);

    return [
        'property' => $property,
        'paper' => $container(WasteFraction::factory()->paper()->create(), 'P-1'),
        'mixed' => $container(WasteFraction::factory()->mixed()->create(), 'R-1'),
    ];
}

it('builds the documentation checklist with the separation rate and data hints', function () {
    loginAsAdminInWastePanel();
    ['property' => $property, 'paper' => $paper, 'mixed' => $mixed] = complianceFixture();
    $recorder = app(WasteCollectionRecorder::class);
    $recorder->recordForContainer($paper, CarbonImmutable::parse('2026-09-01'), WasteCollectionSource::Import, massKg: 850, attributes: ['document_number' => 'WS-1']);
    $recorder->recordForContainer($mixed, CarbonImmutable::parse('2026-09-02'), WasteCollectionSource::Import, massKg: 150);
    WasteEvidence::factory()->create(['property_id' => $property->getKey(), 'year' => 2026, 'type' => 'site_plan', 'status' => WasteEvidenceStatus::Available]);

    $doc = app(WasteDocumentation::class)->for($property, 2026);
    $items = collect($doc['items'])->keyBy('type');

    expect($doc['separation'])->toMatchArray(['rate' => 85.0, 'target' => 90.0, 'met' => false])
        ->and($doc['separation']['due']->toDateString())->toBe('2027-03-31')
        ->and($doc['mixed'])->toHaveCount(1)
        ->and($doc['complete'])->toBeFalse()
        ->and($items['site_plan']['status'])->toBe(WasteEvidenceStatus::Available)
        ->and($items['site_plan']['hint'])->toContain('1 Sammelstellen mit 2 Behältern')
        ->and($items['practice_records']['hint'])->toContain('2 Leerungen im Jahr, davon 1 mit Beleg')
        ->and($items['acceptor_declaration']['hint'])->toContain('Muster Entsorgung GmbH')
        ->and($items['pretreatment_confirmation']['required'])->toBeTrue()
        ->and($items['pretreatment_confirmation']['status'])->toBe(WasteEvidenceStatus::Open);
});

it('does not require pretreatment evidence without mixed waste', function () {
    loginAsAdminInWastePanel();
    ['property' => $property, 'paper' => $paper] = complianceFixture();
    app(WasteCollectionRecorder::class)->recordForContainer($paper, CarbonImmutable::parse('2026-09-01'), WasteCollectionSource::Import, massKg: 100);
    WasteSettings::forOrganization(null)->fill(['evidence_types' => ['site_plan', 'pretreatment_confirmation']])->save();
    WasteEvidence::factory()->create(['property_id' => $property->getKey(), 'year' => 2026, 'type' => 'site_plan', 'status' => WasteEvidenceStatus::Available]);

    $doc = app(WasteDocumentation::class)->for($property, 2026);

    expect(collect($doc['items'])->firstWhere('type', 'pretreatment_confirmation')['status'])->toBe(WasteEvidenceStatus::NotRequired)
        ->and($doc['separation']['met'])->toBeTrue()
        ->and($doc['complete'])->toBeTrue();
});

it('stores evidence from the documentation page and exports it as pdf', function () {
    loginAsAdminInWastePanel();
    ['property' => $property] = complianceFixture();

    Livewire::test(WasteDocumentationPage::class, ['property' => $property->getKey(), 'year' => 2026])
        ->assertSee(__('waste.evidence_types.acceptor_declaration'))
        ->callAction(TestAction::make('editEvidence')->arguments(['type' => 'acceptor_declaration']), [
            'status' => WasteEvidenceStatus::Available->value,
            'valid_until' => '2027-12-31',
            'note' => 'Erklärung liegt vor',
        ])
        ->assertHasNoActionErrors();

    expect(WasteEvidence::query()->where('type', 'acceptor_declaration')->sole())
        ->status->toBe(WasteEvidenceStatus::Available)
        ->year->toBe(2026)
        ->note->toBe('Erklärung liegt vor');

    $response = $this->get(route('waste.documentation.pdf', ['property' => $property, 'year' => 2026]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

it('hides the documentation pdf of foreign tenants', function () {
    loginAsAdmin();
    $foreign = inOrganization(Organization::factory()->create(), fn (): Property => Property::factory()->create());

    $this->get(route('waste.documentation.pdf', ['property' => $foreign, 'year' => 2026]))->assertNotFound();
});

it('tracks register deadlines of hazardous waste and the small quantity threshold', function () {
    loginAsAdminInWastePanel();
    ['property' => $property] = complianceFixture();
    $hazardous = WasteContainer::factory()->create(['property_id' => $property->getKey(), 'waste_fraction_id' => WasteFraction::factory()->hazardous()->create()->getKey(), 'number' => 'G-1']);
    $recorder = app(WasteCollectionRecorder::class);
    $overdue = $recorder->recordForContainer($hazardous, CarbonImmutable::parse('2026-09-01'), WasteCollectionSource::Import, massKg: 1800);
    $open = $recorder->recordForContainer($hazardous, CarbonImmutable::parse('2026-10-10'), WasteCollectionSource::Import, massKg: 400);
    $register = app(HazardousWasteRegister::class);

    expect($register->status($overdue))->toBe('overdue')
        ->and($register->status($open))->toBe('open')
        ->and($register->deadline($open)->toDateString())->toBe('2026-10-20')
        ->and($register->retainUntil($open)->toDateString())->toBe('2029-10-10')
        ->and($register->thresholds(2026)->sole())->toMatchArray(['mass_t' => 2.2, 'threshold_t' => 2.0]);

    Livewire::test(HazardousRegisterPage::class)
        ->assertCanSeeTableRecords([$overdue, $open])
        ->callTableAction('editRegister', $overdue, ['document_number' => 'UES-4711', 'certificate_number' => 'ENA-1'])
        ->assertHasNoTableActionErrors();

    expect($register->status($overdue->refresh()))->toBe('entered')
        ->and($overdue->certificate_number)->toBe('ENA-1');

    Livewire::test(HazardousWasteWidget::class)
        ->assertSee('DEMO-01')
        ->assertSee(__('waste.widgets.hazardous_over'));
});

it('lists only hazardous collections in the register', function () {
    loginAsAdminInWastePanel();
    ['paper' => $paper] = complianceFixture();
    $collection = app(WasteCollectionRecorder::class)->recordForContainer($paper, CarbonImmutable::parse('2026-10-01'), WasteCollectionSource::Import, massKg: 20);

    Livewire::test(HazardousRegisterPage::class)->assertCanNotSeeTableRecords([$collection]);
});

it('compares expected invoice amounts with booked costs and skips free extra pickups', function () {
    loginAsAdminInWastePanel();
    ['paper' => $paper, 'mixed' => $mixed] = complianceFixture();
    $paper->update(['price_per_pickup' => 20, 'price_per_tonne' => 100, 'monthly_rent' => 5]);
    $recorder = app(WasteCollectionRecorder::class);
    $recorder->recordForContainer($paper, CarbonImmutable::parse('2026-09-07'), WasteCollectionSource::Import, massKg: 500);
    $missed = WastePickup::factory()->forContainer($paper)->create(['due_on' => '2026-09-14']);
    $extra = app(PickupService::class)->markMissed($missed, WasteMissedReason::DisposerAbsent);
    $recorder->recordForContainer($paper, CarbonImmutable::parse('2026-09-16'), WasteCollectionSource::Import, massKg: 300, pickup: $extra);
    $recorder->recordForContainer($mixed, CarbonImmutable::parse('2026-09-08'), WasteCollectionSource::Import, massKg: 100);
    $cost = new CostEntry([
        'costable_type' => $paper->getMorphClass(),
        'costable_id' => $paper->getKey(),
        'kind' => CostEntryKind::Actual,
        'amount' => 107.1,
        'tax_rate' => 19,
        'net_amount' => 90,
        'booked_on' => '2026-09-30',
    ]);
    $cost->organization_id = $paper->organization_id;
    $cost->save();

    $rows = app(WasteInvoiceCheck::class)->rows(CarbonImmutable::parse('2026-09-01'));

    expect($extra->free_of_charge)->toBeTrue()
        ->and($rows[$paper->getKey()])->toMatchArray(['pickups' => 1, 'tonnes' => 0.5, 'expected' => 75.0, 'actual' => 90.0, 'difference' => 15.0, 'status' => 'over'])
        ->and($rows[$mixed->getKey()]['status'])->toBe('no_prices');

    Livewire::test(InvoiceCheckPage::class, ['month' => '2026-09'])
        ->assertSee('P-1')
        ->assertSee(__('waste.invoice.status.over'));
});

it('rates invoice deviations within the tolerance as ok', function (?float $expected, float $actual, string $status) {
    expect(app(WasteInvoiceCheck::class)->status($expected, $actual))->toBe($status);
})->with([
    'exact' => [100.0, 100.0, 'ok'],
    'within two percent' => [100.0, 101.5, 'ok'],
    'undercharged' => [100.0, 90.0, 'under'],
    'missing invoice' => [100.0, 0.0, 'missing'],
    'no prices' => [null, 50.0, 'no_prices'],
]);
