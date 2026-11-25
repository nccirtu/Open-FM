<?php

use App\Enums\WastePickupStatus;
use App\Models\Project;
use App\Models\Property;
use App\Models\WasteCollection;
use App\Models\WasteDailyValue;
use App\Models\WasteEmissionFactor;
use App\Models\WasteEvidence;
use App\Models\WasteFillReading;
use App\Models\WasteGoal;
use App\Models\WastePickup;
use App\Services\Waste\HazardousWasteRegister;
use App\Services\Waste\WasteDemoData;
use App\Services\Waste\WasteInvoiceCheck;
use App\Services\Waste\WasteRightsizing;
use Carbon\CarbonImmutable;

it('seeds reproducible demo data with open cases and replaces it on the next run', function () {
    loginAsAdminInWastePanel();
    $this->travelTo(CarbonImmutable::parse('2026-10-11 08:00'));
    $demo = app(WasteDemoData::class);

    $result = $demo->seed(defaultOrganization());

    expect($result['properties'])->toBe(['ABF-DEMO-01', 'ABF-DEMO-02'])
        ->and($result['containers'])->toBe(20)
        ->and($result['collections'])->toBeGreaterThan(300)
        ->and((float) WasteDailyValue::query()->sum('mass_kg'))->toEqualWithDelta((float) WasteCollection::query()->sum('mass_kg'), 0.01)
        ->and(WastePickup::query()->where('confirmed_via', 'schedule')->count())->toBeGreaterThan(50)
        ->and(WastePickup::query()->where('status', WastePickupStatus::Missed->value)->count())->toBe(2)
        ->and(WastePickup::query()->where('status', WastePickupStatus::Requested->value)->count())->toBe(1)
        ->and(WastePickup::query()->whereDate('due_on', '>', '2026-10-11')->exists())->toBeTrue()
        ->and(WasteGoal::query()->count())->toBe(5)
        ->and(WasteEvidence::query()->count())->toBe(6)
        ->and(WasteEmissionFactor::query()->count())->toBe(4)
        ->and(Project::query()->where('code', WasteDemoData::PROJECT_CODE)->sole()->wasteCollections()->count())->toBe(5)
        ->and(WasteFillReading::query()->count())->toBe(14)
        ->and(WasteCollection::query()->whereYear('collected_at', 2025)->exists())->toBeTrue();

    $invoices = app(WasteInvoiceCheck::class)->rows(CarbonImmutable::parse('2026-09-01'));
    $statuses = $invoices->mapWithKeys(fn (array $row): array => [$row['container']->number => $row['status']]);
    $register = app(HazardousWasteRegister::class);
    $hazardous = $register->query()->whereYear('collected_at', 2026)->get()->map(fn (WasteCollection $collection): string => $register->status($collection))->countBy();

    expect($statuses['B-0101'])->toBe('over')
        ->and($statuses['B-0201'])->toBe('missing')
        ->and($statuses['B-0131'])->toBe('no_prices')
        ->and($statuses['B-0111'])->toBe('ok')
        ->and($hazardous->all())->toMatchArray(['entered' => 4, 'overdue' => 2, 'open' => 1])
        ->and(app(WasteRightsizing::class)->recommendations()->firstWhere(fn (array $row): bool => $row['container']->number === 'B-0117')['recommendation'])->toBe('reduce');

    $demo->seed(defaultOrganization());

    expect(Property::query()->where('property_number', 'like', 'ABF-DEMO-%')->count())->toBe(2)
        ->and($demo->remove(defaultOrganization()))->toBe(2)
        ->and(WasteCollection::query()->count())->toBe(0)
        ->and(WasteGoal::query()->count())->toBe(0)
        ->and(WasteEmissionFactor::query()->count())->toBe(0)
        ->and(Project::query()->where('code', WasteDemoData::PROJECT_CODE)->exists())->toBeFalse();
});
