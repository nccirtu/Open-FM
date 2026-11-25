<?php

use App\Enums\MeterKind;
use App\Enums\MeterReadingSource;
use App\Filament\Energy\Pages\RecordReadingPage;
use App\Filament\Energy\Resources\MeterResource;
use App\Filament\Energy\Resources\MeterResource\Pages\CreateMeter;
use App\Filament\Energy\Resources\MeterResource\Pages\ListMeters;
use App\Filament\Energy\Resources\MeterResource\Widgets\MeterConsumersWidget;
use App\Models\Asset;
use App\Models\Meter;
use App\Models\Property;
use App\Services\Energy\EnergyFlow;
use App\Services\Energy\MeterReadingRecorder;
use Cafm\QrCode\QrCode;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

/**
 * @return array{property: Property, main: Meter, sub: Meter, rest: Meter, vent: Asset, light: Asset}
 */
function consumersFixture(): array
{
    $property = Property::factory()->create(['property_number' => 'B-1']);
    $main = Meter::factory()->main()->create(['property_id' => $property->getKey(), 'name' => 'Hauptzähler']);
    $sub = Meter::factory()->childOf($main)->create(['name' => 'UZ Lüftung']);
    $rest = Meter::factory()->childOf($main)->create([
        'name' => 'Allgemeinstrom',
        'kind' => MeterKind::Virtual,
        'formula' => [['meter_id' => $main->getKey(), 'sign' => 1], ['meter_id' => $sub->getKey(), 'sign' => -1]],
    ]);
    $vent = Asset::factory()->create(['property_id' => $property->getKey(), 'name' => 'Lüftungsanlage RLT']);
    $light = Asset::factory()->create(['property_id' => $property->getKey(), 'name' => 'Beleuchtung Büro']);
    $sub->measuredAssets()->attach($vent->getKey(), ['share' => 100]);
    $rest->measuredAssets()->attach($light->getKey(), ['share' => 70]);

    $recorder = app(MeterReadingRecorder::class);
    $start = CarbonImmutable::today()->startOfYear();
    $end = $start->addDays(10);
    $recorder->record($main, $start, 0, MeterReadingSource::Api);
    $recorder->record($main, $end, 1000, MeterReadingSource::Api);
    $recorder->record($sub, $start, 0, MeterReadingSource::Api);
    $recorder->record($sub, $end, 400, MeterReadingSource::Api);

    return compact('property', 'main', 'sub', 'rest', 'vent', 'light');
}

it('shows where the consumption of a meter goes, down to the assets', function () {
    loginAsAdminInEnergyPanel();
    ['main' => $main] = consumersFixture();

    Livewire::test(MeterConsumersWidget::class, ['record' => $main])
        ->assertSuccessful()
        ->assertSee(__('energy.consumers.title'))
        ->assertSee('UZ Lüftung')
        ->assertSee('Allgemeinstrom')
        ->assertSee('Lüftungsanlage RLT')
        ->assertSee('Beleuchtung Büro')
        ->assertSee(__('energy.flow.unassigned'));
});

it('ranks the consumers of a meter at the finest known level', function () {
    loginAsAdminInEnergyPanel();
    ['main' => $main, 'vent' => $vent, 'light' => $light] = consumersFixture();

    $flow = app(EnergyFlow::class)->buildFor($main, CarbonImmutable::today()->startOfYear(), CarbonImmutable::today());
    $consumers = collect(MeterConsumersWidget::consumers($flow, $main->getKey(), 1000))
        ->map(fn (array $row): array => [$row['label'], round($row['value']), round($row['percent'])])
        ->all();

    expect($consumers)->toBe([
        ['Beleuchtung Büro', 420.0, 42.0],
        ['Lüftungsanlage RLT', 400.0, 40.0],
        [__('energy.flow.unassigned'), 180.0, 18.0],
    ])->and(collect($flow['links'])->pluck('target')->all())->toContain('asset:'.$vent->getKey(), 'asset:'.$light->getKey());
});

it('counts calculated remainder meters in the property flow without double counting', function () {
    loginAsAdminInEnergyPanel();
    ['property' => $property, 'main' => $main, 'rest' => $rest] = consumersFixture();
    $total = Meter::factory()->create([
        'property_id' => $property->getKey(),
        'name' => 'Summe aller Zähler',
        'kind' => MeterKind::Virtual,
        'formula' => [['meter_id' => $main->getKey(), 'sign' => 1]],
    ]);

    $flow = app(EnergyFlow::class)->build($property, $main->medium, CarbonImmutable::today()->startOfYear(), CarbonImmutable::today());
    $outgoing = collect($flow['links'])->where('source', $main->getKey())->sum('value');

    expect(EnergyFlow::isRemainder($rest))->toBeTrue()
        ->and(EnergyFlow::isRemainder($total))->toBeFalse()
        ->and(collect($flow['nodes'])->pluck('id')->all())->toContain($rest->getKey())->not->toContain($total->getKey())
        ->and(round($outgoing))->toBe(1000.0);
});

it('explains an empty meter instead of showing an empty diagram', function () {
    loginAsAdminInEnergyPanel();
    $meter = Meter::factory()->main()->create();

    Livewire::test(MeterConsumersWidget::class, ['record' => $meter])
        ->assertSee(__('energy.consumers.empty'));
});

it('lists the consumers of each meter in the meter table', function () {
    loginAsAdminInEnergyPanel();
    ['sub' => $sub] = consumersFixture();

    Livewire::test(ListMeters::class)
        ->assertCanSeeTableRecords([$sub])
        ->assertSee('Lüftungsanlage RLT');
});

it('asks for the market location id on main meters only', function () {
    loginAsAdminInEnergyPanel();

    Livewire::test(CreateMeter::class)
        ->fillForm(['kind' => MeterKind::Main])
        ->assertFormFieldVisible('market_location_id')
        ->fillForm(['kind' => MeterKind::Sub])
        ->assertFormFieldHidden('market_location_id');
});

it('opens the reading page for the scanned meter', function () {
    requiresPlugin('qr-code');
    loginAsAdminInEnergyPanel();
    ['main' => $main, 'rest' => $rest] = consumersFixture();
    $organization = defaultOrganization();

    expect(QrCode::supports($main))->toBeTrue()
        ->and(QrCode::labelLines($main)[0])->toBe('Hauptzähler');

    $this->get(QrCode::url($main))
        ->assertRedirect(RecordReadingPage::getUrl(['meter' => $main->getKey()], panel: 'energy', tenant: $organization));
    $this->get(QrCode::url($rest))
        ->assertRedirect(MeterResource::getUrl('view', ['record' => $rest], panel: 'energy', tenant: $organization));
});
