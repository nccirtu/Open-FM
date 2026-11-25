<?php

use App\Enums\WasteCollectionSource;
use App\Enums\WasteGoalType;
use App\Filament\Waste\Resources\WasteGoals\Pages\ManageWasteGoals;
use App\Filament\Waste\Widgets\WasteGoalsWidget;
use App\Models\Property;
use App\Models\WasteFraction;
use App\Models\WasteGoal;
use App\Services\Waste\WasteCollectionRecorder;
use App\Services\Waste\WasteGoalEvaluator;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

function goalFixture(): Property
{
    test()->travelTo(CarbonImmutable::parse('2026-07-01 12:00'));

    $property = Property::factory()->create(['net_area' => 1000]);
    $recorder = app(WasteCollectionRecorder::class);
    $recorder->recordForProperty($property, WasteFraction::factory()->paper()->create(), CarbonImmutable::parse('2026-03-02'), 300, WasteCollectionSource::Manual);
    $recorder->recordForProperty($property, WasteFraction::factory()->mixed()->create(), CarbonImmutable::parse('2026-05-04'), 100, WasteCollectionSource::Manual);

    return $property;
}

it('rates minimum quotas by the current value with a tolerance of five points', function (float $target, string $status) {
    loginAsAdminInWastePanel();
    $property = goalFixture();

    $result = app(WasteGoalEvaluator::class)->evaluate(WasteGoal::factory()->create([
        'property_id' => $property->getKey(),
        'type' => WasteGoalType::MinRecyclingRate,
        'year' => 2026,
        'target_value' => $target,
    ]));

    expect($result)->toMatchArray(['actual' => 75.0, 'forecast' => null, 'status' => $status, 'unit' => '%']);
})->with([
    'reached' => [70, 'good'],
    'just below' => [78, 'warn'],
    'missed' => [90, 'crit'],
]);

it('projects masses to the end of the year', function (float $target, string $status) {
    loginAsAdminInWastePanel();
    $property = goalFixture();

    $result = app(WasteGoalEvaluator::class)->evaluate(WasteGoal::factory()->create([
        'property_id' => $property->getKey(),
        'type' => WasteGoalType::MaxMass,
        'year' => 2026,
        'target_value' => $target,
    ]));

    expect($result['actual'])->toBe(0.4)
        ->and($result['forecast'])->toBe(0.8)
        ->and($result['status'])->toBe($status);
})->with([
    'on track' => [1.0, 'good'],
    'forecast above' => [0.6, 'warn'],
    'already above' => [0.3, 'crit'],
]);

it('derives reduction targets from the baseline year and masses per square metre from the area', function () {
    loginAsAdminInWastePanel();
    $property = goalFixture();
    app(WasteCollectionRecorder::class)->recordForProperty($property, WasteFraction::query()->firstOrFail(), CarbonImmutable::parse('2025-06-01'), 1000, WasteCollectionSource::Import);
    $evaluator = app(WasteGoalEvaluator::class);

    $reduction = $evaluator->evaluate(WasteGoal::factory()->create([
        'property_id' => $property->getKey(),
        'type' => WasteGoalType::Reduction,
        'year' => 2026,
        'target_value' => 10,
        'baseline_year' => 2025,
    ]));
    $perArea = $evaluator->evaluate(WasteGoal::factory()->create([
        'property_id' => $property->getKey(),
        'type' => WasteGoalType::MaxMassPerArea,
        'year' => 2026,
        'target_value' => 1,
    ]));

    expect($reduction)->toMatchArray(['target' => 0.9, 'actual' => 0.4, 'status' => 'good'])
        ->and($perArea)->toMatchArray(['actual' => 0.4, 'forecast' => 0.8, 'unit' => 'kg/m²', 'status' => 'good']);
});

it('reports goals without data or baseline as unknown', function () {
    loginAsAdminInWastePanel();
    $property = goalFixture();
    $evaluator = app(WasteGoalEvaluator::class);

    expect($evaluator->evaluate(WasteGoal::factory()->create(['type' => WasteGoalType::Reduction, 'year' => 2026, 'target_value' => 10, 'baseline_year' => 2020]))['status'])->toBe('unknown')
        ->and($evaluator->evaluate(WasteGoal::factory()->create(['property_id' => $property->getKey(), 'year' => 2027]))['status'])->toBe('unknown');
});

it('manages goals and shows them on the dashboard', function () {
    loginAsAdminInWastePanel();
    $property = goalFixture();

    Livewire::test(ManageWasteGoals::class)
        ->callAction('create', [
            'name' => 'Recycling Lindenallee',
            'property_id' => $property->getKey(),
            'type' => WasteGoalType::MinRecyclingRate->value,
            'year' => 2026,
            'target_value' => 70,
        ])
        ->assertHasNoActionErrors()
        ->assertSee('Recycling Lindenallee');

    Livewire::test(WasteGoalsWidget::class)
        ->assertSee('Recycling Lindenallee')
        ->assertSee(__('waste.goal_status.good'));
});
