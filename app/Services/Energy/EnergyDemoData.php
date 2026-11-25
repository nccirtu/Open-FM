<?php

namespace App\Services\Energy;

use App\Enums\EnergyGoalPeriod;
use App\Enums\EnergyGoalType;
use App\Enums\EnergyMedium;
use App\Enums\MeterKind;
use App\Enums\MeterReadingSource;
use App\Enums\PropertyUsageType;
use App\Enums\RoomUse;
use App\Enums\StructureNodeType;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\EnergyGoal;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\Property;
use App\Models\StructureNode;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Support\Str;
use Random\Engine\Mt19937;
use Random\Randomizer;

final class EnergyDemoData
{
    public const PROPERTY_PREFIX = 'DEMO-';

    public const NOTE = 'Demo-Daten – werden von „php artisan energy:demo“ neu erzeugt.';

    private Randomizer $random;

    private Organization $organization;

    private CarbonImmutable $start;

    private CarbonImmutable $today;

    /**
     * @var array<string, float>
     */
    private array $temperature = [];

    private ?string $userId = null;

    private int $readingCount = 0;

    private int $meterCount = 0;

    public function __construct(
        private ConsumptionAggregator $aggregator,
        private MeterReadingRecorder $recorder,
    ) {}

    /**
     * @return array{properties: array<int, string>, meters: int, readings: int, goals: int}
     */
    public function seed(Organization $organization, ?CarbonInterface $today = null, int $seed = 2026): array
    {
        return $this->withinTenant($organization, function () use ($organization, $today, $seed): array {
            $this->organization = $organization;
            $this->random = new Randomizer(new Mt19937($seed));
            $this->today = CarbonImmutable::parse($today ?? now())->startOfDay();
            $this->start = $this->today->subYear()->startOfYear();
            $this->userId = $organization->users()->orderBy('users.created_at')->value('users.id');
            $this->readingCount = 0;
            $this->meterCount = 0;
            $this->weather();

            $this->deleteDemoRecords();

            $office = $this->office();
            $residential = $this->residential();
            $goals = $this->goals($office, $residential);

            return [
                'properties' => [$office['property']->property_number, $residential['property']->property_number],
                'meters' => $this->meterCount,
                'readings' => $this->readingCount,
                'goals' => $goals,
            ];
        });
    }

    public function remove(Organization $organization): int
    {
        return $this->withinTenant($organization, function () use ($organization): int {
            $this->organization = $organization;

            return $this->deleteDemoRecords();
        });
    }

    /**
     * @template TResult
     *
     * @param  Closure(): TResult  $callback
     */
    private function withinTenant(Organization $organization, Closure $callback): mixed
    {
        $previousTenant = Filament::getTenant();
        $previousTeamId = getPermissionsTeamId();

        Filament::setTenant($organization, isQuiet: true);
        setPermissionsTeamId($organization->getKey());

        try {
            return $callback();
        } finally {
            Filament::setTenant($previousTenant, isQuiet: true);
            setPermissionsTeamId($previousTeamId);
        }
    }

    private function deleteDemoRecords(): int
    {
        EnergyGoal::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $this->organization->getKey())
            ->where('notes', self::NOTE)
            ->delete();

        return Property::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $this->organization->getKey())
            ->where('property_number', 'like', self::PROPERTY_PREFIX.'%')
            ->delete();
    }

    /**
     * @return array{property: Property, meters: array<string, Meter>, assets: array<string, Asset>}
     */
    private function office(): array
    {
        $property = $this->property('DEMO-01', 'Lindenallee 12', '76131', PropertyUsageType::Commercial, gross: 2850, net: 2400);

        $building = $this->node($property, null, StructureNodeType::Building, 'Gebäude A');
        $basement = $this->node($property, $building, StructureNodeType::Floor, 'Untergeschoss', ['level' => -1]);
        $technical = $this->node($property, $basement, StructureNodeType::Room, 'Technikzentrale', ['room_use' => RoomUse::Technical, 'area' => 120]);
        $serverRoom = $this->node($property, $basement, StructureNodeType::Room, 'Serverraum', ['room_use' => RoomUse::Technical, 'area' => 35]);
        $ground = $this->node($property, $building, StructureNodeType::Floor, 'Erdgeschoss', ['level' => 0]);
        $this->node($property, $ground, StructureNodeType::Room, 'Empfang', ['room_use' => RoomUse::Entrance, 'area' => 80]);
        $canteen = $this->node($property, $ground, StructureNodeType::Room, 'Kantine mit Küche', ['room_use' => RoomUse::StaffRoom, 'area' => 260]);
        $officesGround = $this->node($property, $ground, StructureNodeType::Room, 'Büroflächen EG', ['room_use' => RoomUse::Office, 'area' => 520]);
        $first = $this->node($property, $building, StructureNodeType::Floor, '1. Obergeschoss', ['level' => 1]);
        $officesFirst = $this->node($property, $first, StructureNodeType::Room, 'Büroflächen 1. OG', ['room_use' => RoomUse::Office, 'area' => 760]);
        $this->node($property, $first, StructureNodeType::Room, 'Besprechungszentrum', ['room_use' => RoomUse::Office, 'area' => 140]);
        $second = $this->node($property, $building, StructureNodeType::Floor, '2. Obergeschoss', ['level' => 2]);
        $officesSecond = $this->node($property, $second, StructureNodeType::Room, 'Büroflächen 2. OG', ['room_use' => RoomUse::Office, 'area' => 760]);
        $outdoor = $this->node($property, null, StructureNodeType::Outdoor, 'Parkplatz und Außenanlage', ['room_use' => RoomUse::Outdoor]);

        $assets = [
            'ventilation' => $this->asset($property, $technical, 'Lüftungsanlage RLT 1', 'Lüftungsanlage', '2014-05-01'),
            'chiller' => $this->asset($property, $technical, 'Kaltwassersatz KWS 1', 'Kaltwassersatz', '2016-04-15'),
            'boiler' => $this->asset($property, $technical, 'Gas-Brennwertkessel', 'Heizkessel', '2012-09-01'),
            'server' => $this->asset($property, $serverRoom, 'Server und USV', 'Serverraum-Technik', '2019-02-01'),
            'lift' => $this->asset($property, $ground, 'Aufzug A1', 'Aufzug', '2008-11-01'),
            'charging' => $this->asset($property, $outdoor, 'Ladepark (6 Ladepunkte)', 'E-Ladesäule', '2023-03-01'),
            'lighting' => $this->asset($property, $building, 'Beleuchtung Büroflächen', 'Beleuchtungsanlage', '2010-01-01'),
            'kitchen' => $this->asset($property, $canteen, 'Kantinenküche', 'Herd', '2017-07-01'),
            'pv' => $this->asset($property, $building, 'PV-Anlage Dach (99 kWp)', 'PV-Anlage', '2021-06-01'),
        ];

        $thisYear = fn (CarbonImmutable $day, int $fromMonth = 1): bool => $day->year === $this->today->year && $day->month >= $fromMonth;

        $consumption = [
            'ventilation' => $this->series(fn (CarbonImmutable $day, float $t): float => ($day->isWeekend() ? 55 : 175 + 2 * max(0, 5 - $t)) * ($thisYear($day, 3) ? 0.88 : 1.0), 0.05),
            'chiller' => $this->series(fn (CarbonImmutable $day, float $t): float => max(0, $t - 14) * ($day->isWeekend() ? 8 : 26), 0.1),
            'server' => $this->series(fn (CarbonImmutable $day): float => 112 + 0.012 * $this->start->diffInDays($day), 0.03),
            'charging' => $this->series(fn (CarbonImmutable $day): float => ($day->isWeekend() ? 18 : 85) * ($thisYear($day) ? 1.4 : 1.0), 0.25),
            'lift' => $this->series(fn (CarbonImmutable $day): float => $day->isWeekend() ? 4 : 17, 0.1),
            'general' => $this->series(fn (CarbonImmutable $day): float => $day->isWeekend()
                ? 45
                : 160 * (1 + 0.3 * cos(2 * M_PI * ($day->dayOfYear + 10) / 365)) * ($thisYear($day, 2) ? 0.8 : 1.0) + 95 + 140, 0.06),
            'pv' => $this->series(fn (CarbonImmutable $day): float => 99 * (0.6 + 4.6 * (0.5 + 0.5 * cos(2 * M_PI * ($day->dayOfYear - 172) / 365))) * $this->random->getFloat(0.35, 1.15), 0.0),
            'gas' => $this->series(fn (CarbonImmutable $day, float $t): float => 105 * max(0, 15 - $t) * ($day->isWeekend() ? 0.65 : 1.0) + ($day->isWeekend() ? 10 : 35), 0.08),
            'water' => $this->series(fn (CarbonImmutable $day): float => $day->isWeekend() ? 0.9 : 7.2, 0.15),
            'kitchenWater' => $this->series(fn (CarbonImmutable $day): float => $day->isWeekend() ? 0.1 : 1.9, 0.15),
        ];
        $mainElectricity = $this->sum($consumption, ['ventilation', 'chiller', 'server', 'charging', 'lift', 'general']);

        $main = $this->meter($property, 'Hauptzähler Strom', EnergyMedium::Electricity, MeterKind::Main, $technical, [
            'number' => '1ESY1160452187',
            'market_location_id' => '51238696781',
            'calibration_valid_until' => $this->today->addYears(4)->endOfYear(),
        ]);
        $main->suppliedNodes()->attach($building->getKey(), ['share' => 100]);

        $subs = [
            'ventilation' => ['UZ Lüftung RLT 1', 'UZ-01', MeterReadingSource::Import, $technical, $this->today->addYears(3)],
            'chiller' => ['UZ Kälte KWS 1', 'UZ-02', MeterReadingSource::Api, $technical, $this->today->addYears(5)],
            'server' => ['UZ Serverraum', 'UZ-03', MeterReadingSource::Api, $serverRoom, $this->today->addDays(75)],
            'charging' => ['UZ Ladepark', 'UZ-04', MeterReadingSource::Api, $outdoor, $this->today->addYears(6)],
            'lift' => ['UZ Aufzug A1', 'UZ-05', MeterReadingSource::Manual, $ground, $this->today->subDays(20)],
        ];
        $meters = ['main' => $main];

        foreach ($subs as $key => [$name, $number, $source, $location, $calibration]) {
            $meter = $this->meter($property, $name, EnergyMedium::Electricity, MeterKind::Sub, $location, [
                'parent_id' => $main->getKey(),
                'number' => $number,
                'calibration_valid_until' => $calibration,
            ]);
            $meter->measuredAssets()->attach($assets[$key]->getKey(), ['share' => 100]);
            $meters[$key] = $meter;
            $this->readings($meter, $consumption[$key], $source === MeterReadingSource::Manual ? 'monthly' : 'daily', $source, $this->random->getFloat(8000, 60000));
        }

        $meters['server']->suppliedNodes()->attach($serverRoom->getKey(), ['share' => 100]);

        $general = $this->meter($property, 'Allgemeinstrom (berechnet)', EnergyMedium::Electricity, MeterKind::Virtual, null, [
            'parent_id' => $main->getKey(),
            'unit' => 'kWh',
            'formula' => [
                ['meter_id' => $main->getKey(), 'sign' => 1],
                ...array_map(fn (string $key): array => ['meter_id' => $meters[$key]->getKey(), 'sign' => -1], array_keys($subs)),
            ],
            'notes' => 'Hauptzähler minus alle Unterzähler: Beleuchtung, Küche, Arbeitsplätze.',
        ]);
        $general->measuredAssets()->attach([
            $assets['lighting']->getKey() => ['share' => 55],
            $assets['kitchen']->getKey() => ['share' => 25],
        ]);
        $general->suppliedNodes()->attach([
            $officesGround->getKey() => ['share' => 30],
            $officesFirst->getKey() => ['share' => 35],
            $officesSecond->getKey() => ['share' => 35],
        ]);

        $this->readings($main, $mainElectricity, 'daily', MeterReadingSource::Api, 184320.0);

        $pv = $this->meter($property, 'PV-Erzeugung Dach', EnergyMedium::PvGeneration, MeterKind::Main, $building, [
            'number' => 'PV-01',
            'calibration_valid_until' => $this->today->addYears(5)->endOfYear(),
        ]);
        $pv->measuredAssets()->attach($assets['pv']->getKey(), ['share' => 100]);
        $this->readings($pv, $consumption['pv'], 'daily', MeterReadingSource::Api, 92410.0);

        $gas = $this->meter($property, 'Gaszähler', EnergyMedium::Gas, MeterKind::Main, $technical, [
            'number' => '7GMT0012345678',
            'unit' => 'm³',
            'factor' => 10.5,
            'market_location_id' => '50817432095',
            'calibration_valid_until' => $this->today->addYears(3)->endOfYear(),
        ]);
        $gas->measuredAssets()->attach($assets['boiler']->getKey(), ['share' => 100]);
        $gas->suppliedNodes()->attach($building->getKey(), ['share' => 100]);
        $this->readings($gas, $consumption['gas'], 'monthly', MeterReadingSource::Manual, 48213.0);

        $water = $this->meter($property, 'Wasserzähler Haupt', EnergyMedium::Water, MeterKind::Main, $technical, [
            'number' => 'W-4711-01',
            'calibration_valid_until' => $this->today->addDays(150),
        ]);
        $water->suppliedNodes()->attach($building->getKey(), ['share' => 100]);
        $this->readings($water, $this->sum($consumption, ['water', 'kitchenWater']), 'monthly', MeterReadingSource::Manual, 3120.0);

        $kitchenWater = $this->meter($property, 'Wasser Kantinenküche', EnergyMedium::Water, MeterKind::Sub, $canteen, [
            'parent_id' => $water->getKey(),
            'number' => 'W-4711-02',
            'calibration_valid_until' => $this->today->addYears(2),
        ]);
        $kitchenWater->measuredAssets()->attach($assets['kitchen']->getKey(), ['share' => 100]);
        $this->readings($kitchenWater, $consumption['kitchenWater'], 'monthly', MeterReadingSource::Manual, 640.0);

        $this->suspiciousReadings($gas, $kitchenWater, $consumption['kitchenWater']);

        return ['property' => $property, 'meters' => [...$meters, 'general' => $general, 'gas' => $gas], 'assets' => $assets];
    }

    /**
     * @return array{property: Property, meters: array<string, Meter>, assets: array<string, Asset>}
     */
    private function residential(): array
    {
        $property = $this->property('DEMO-02', 'Am Wiesengrund 5', '76189', PropertyUsageType::Residential, gross: 1180, net: 960);

        $house = $this->node($property, null, StructureNodeType::Building, 'Haus 1');
        $cellar = $this->node($property, $house, StructureNodeType::Floor, 'Kellergeschoss', ['level' => -1]);
        $boilerRoom = $this->node($property, $cellar, StructureNodeType::Room, 'Heizraum', ['room_use' => RoomUse::Technical, 'area' => 30]);
        $outdoor = $this->node($property, null, StructureNodeType::Outdoor, 'Außenanlage', ['room_use' => RoomUse::Outdoor]);
        $units = [];
        $floors = [['Erdgeschoss', 0, [68, 82]], ['1. Obergeschoss', 1, [68, 82]], ['2. Obergeschoss', 2, [68, 82]], ['Dachgeschoss', 3, [95, 95]]];

        foreach ($floors as [$floorName, $level, $areas]) {
            $floor = $this->node($property, $house, StructureNodeType::Floor, $floorName, ['level' => $level]);

            foreach ($areas as $area) {
                $units[] = $this->node($property, $floor, StructureNodeType::Unit, 'Wohnung '.(count($units) + 1), ['area' => $area]);
            }
        }

        $assets = [
            'heatPump' => $this->asset($property, $boilerRoom, 'Wärmepumpe WP 1', 'Wärmepumpe', '2020-10-01'),
            'hotWater' => $this->asset($property, $boilerRoom, 'Warmwasserspeicher', 'Warmwasserbereiter', '2020-10-01'),
            'lift' => $this->asset($property, $house, 'Aufzug', 'Aufzug', '2020-10-01'),
            'outdoorLighting' => $this->asset($property, $outdoor, 'Außenbeleuchtung', 'Außenbeleuchtung', '2020-10-01'),
            'stairLighting' => $this->asset($property, $house, 'Treppenhausbeleuchtung', 'Beleuchtungsanlage', '2020-10-01'),
        ];

        $heating = $this->series(fn (CarbonImmutable $day, float $t): float => 14 * max(0, 15 - $t), 0.06);
        $heat = array_map(fn (float $value): float => $value + 30 * (1 + $this->random->getFloat(-0.1, 0.1)), $heating);
        $heatPumpPower = [];

        foreach ($heat as $date => $value) {
            $heatPumpPower[$date] = $value / max(2.2, min(4.4, 2.6 + 0.08 * $this->temperature[$date]));
        }

        $common = $this->series(fn (CarbonImmutable $day): float => 7 + 0.5 * (12 + 4 * cos(2 * M_PI * ($day->dayOfYear + 10) / 365)) + 3.5 + 4, 0.08);

        $commonMeter = $this->meter($property, 'Allgemeinstrom', EnergyMedium::Electricity, MeterKind::Main, $cellar, [
            'number' => '1ESY1160778801',
            'market_location_id' => '52076318844',
            'calibration_valid_until' => $this->today->addYears(5)->endOfYear(),
        ]);
        $commonMeter->measuredAssets()->attach([
            $assets['lift']->getKey() => ['share' => 35],
            $assets['outdoorLighting']->getKey() => ['share' => 25],
            $assets['stairLighting']->getKey() => ['share' => 20],
        ]);
        $this->readings($commonMeter, $common, 'monthly', MeterReadingSource::Manual, 21870.0);

        $heatPumpMeter = $this->meter($property, 'Wärmepumpenstrom', EnergyMedium::Electricity, MeterKind::Main, $boilerRoom, [
            'number' => '1ESY1160778802',
            'market_location_id' => '52076318852',
            'calibration_valid_until' => $this->today->addYears(6)->endOfYear(),
        ]);
        $heatPumpMeter->measuredAssets()->attach($assets['heatPump']->getKey(), ['share' => 100]);
        $this->readings($heatPumpMeter, $heatPumpPower, 'daily', MeterReadingSource::Api, 38440.0);

        $heatMeter = $this->meter($property, 'Wärmemengenzähler Heizung', EnergyMedium::Heat, MeterKind::Main, $boilerRoom, [
            'number' => 'WMZ-00',
            'calibration_valid_until' => $this->today->addYears(2)->endOfYear(),
        ]);
        $heatMeter->measuredAssets()->attach($assets['hotWater']->getKey(), ['share' => 25]);
        $heatMeter->suppliedNodes()->attach($house->getKey(), ['share' => 100]);
        $this->readings($heatMeter, $heat, 'daily', MeterReadingSource::Api, 152300.0);

        $weights = array_map(fn (StructureNode $unit): float => (float) $unit->area * $this->random->getFloat(0.8, 1.25), $units);
        $weightSum = array_sum($weights);

        foreach ($units as $index => $unit) {
            $share = 0.85 * $weights[$index] / $weightSum;
            $meter = $this->meter($property, 'WMZ '.$unit->name, EnergyMedium::Heat, MeterKind::Sub, $unit, [
                'parent_id' => $heatMeter->getKey(),
                'number' => sprintf('WMZ-%02d', $index + 1),
                'calibration_valid_until' => $this->today->addYears(4)->endOfYear(),
            ]);
            $meter->suppliedNodes()->attach($unit->getKey(), ['share' => 100]);
            $this->readings($meter, array_map(fn (float $value): float => $value * $share * (1 + $this->random->getFloat(-0.05, 0.05)), $heating), 'monthly', MeterReadingSource::Import, $this->random->getFloat(4000, 15000));
        }

        $water = $this->meter($property, 'Wasserzähler Haus', EnergyMedium::Water, MeterKind::Main, $cellar, [
            'number' => 'W-0815-01',
            'calibration_valid_until' => $this->today->addYears(3)->endOfYear(),
        ]);
        $water->suppliedNodes()->attach($house->getKey(), ['share' => 100]);
        $this->readings($water, $this->series(fn (CarbonImmutable $day): float => 8 * 0.27 * ($day->isWeekend() ? 1.1 : 1.0), 0.12), 'monthly', MeterReadingSource::Manual, 1890.0);

        return ['property' => $property, 'meters' => ['heat' => $heatMeter], 'assets' => $assets];
    }

    /**
     * @param  array{property: Property, meters: array<string, Meter>, assets: array<string, Asset>}  $office
     * @param  array{property: Property, meters: array<string, Meter>, assets: array<string, Asset>}  $residential
     */
    private function goals(array $office, array $residential): int
    {
        $year = $this->today->year;
        $goals = [
            ['Strom Bürogebäude −10 % ggü. Vorjahr', $office['property'], EnergyMedium::Electricity, EnergyGoalType::Reduction, EnergyGoalPeriod::Year, $year, 10, $year - 1],
            ['Heizenergie Bürogebäude max. 110 kWh/m²', $office['property'], EnergyMedium::Gas, EnergyGoalType::MaxIntensity, EnergyGoalPeriod::Year, $year, 110, null],
            ['Serverraum max. 3.500 kWh je Monat', $office['meters']['server'], EnergyMedium::Electricity, EnergyGoalType::MaxConsumption, EnergyGoalPeriod::Month, $year, 3500, null],
            ['Ladepark max. 36.000 kWh', $office['assets']['charging'], EnergyMedium::Electricity, EnergyGoalType::MaxConsumption, EnergyGoalPeriod::Year, $year, 36000, null],
            ['Wärme Wohnanlage max. 60 kWh/m²', $residential['property'], EnergyMedium::Heat, EnergyGoalType::MaxIntensity, EnergyGoalPeriod::Year, $year, 60, null],
            ['Strom alle Objekte −5 % ggü. Vorjahr', null, EnergyMedium::Electricity, EnergyGoalType::Reduction, EnergyGoalPeriod::Year, $year, 5, $year - 1],
            ['Strom Bürogebäude max. 235.000 kWh', $office['property'], EnergyMedium::Electricity, EnergyGoalType::MaxConsumption, EnergyGoalPeriod::Year, $year - 1, 235000, null],
        ];

        foreach ($goals as [$name, $subject, $medium, $type, $period, $goalYear, $target, $baseline]) {
            $goal = new EnergyGoal([
                'name' => $name,
                'medium' => $medium,
                'type' => $type,
                'period' => $period,
                'year' => $goalYear,
                'target_value' => $target,
                'baseline_year' => $baseline,
                'notes' => self::NOTE,
            ]);
            $goal->goalable()->associate($subject);
            $goal->organization_id = $this->organization->getKey();
            $goal->save();
        }

        return count($goals);
    }

    /**
     * @param  array<string, float>  $kitchenWaterDaily
     */
    private function suspiciousReadings(Meter $gas, Meter $kitchenWater, array $kitchenWaterDaily): void
    {
        $at = $this->today->subDay()->setTime(17, 30);
        $lastGas = (float) $gas->readings()->latest('read_at')->value('value');

        $this->recorder->record($gas, $at, round($lastGas - 900, 1), MeterReadingSource::Manual, [
            'note' => 'Demo: Zahlendreher beim Ablesen – Stand korrigieren oder Ablesung löschen.',
            'created_by' => $this->userId,
        ]);

        $last = $kitchenWater->readings()->latest('read_at')->first(['read_at', 'value']);
        $days = max(1.0, CarbonImmutable::parse($last->read_at)->diffInHours($at) / 24);
        $usual = array_sum(array_slice($kitchenWaterDaily, -60)) / 60;

        $this->recorder->record($kitchenWater, $at->addMinutes(15), round((float) $last->value + $usual * $days * 5, 3), MeterReadingSource::Manual, [
            'note' => 'Demo: Wasserverbrauch stark erhöht – Leck oder Ablesefehler?',
            'created_by' => $this->userId,
        ]);

        $this->readingCount += 2;
    }

    private function property(string $number, string $street, string $zip, PropertyUsageType $usage, float $gross, float $net): Property
    {
        $property = new Property([
            'property_number' => $number,
            'street' => $street,
            'zip_code' => $zip,
            'city' => 'Karlsruhe',
            'country' => 'DE',
            'usage_type' => $usage,
            'gross_area' => $gross,
            'net_area' => $net,
            'manager_id' => $this->userId,
        ]);
        $property->organization_id = $this->organization->getKey();
        $property->save();

        return $property;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function node(Property $property, ?StructureNode $parent, StructureNodeType $type, string $name, array $attributes = []): StructureNode
    {
        $node = new StructureNode([
            'property_id' => $property->getKey(),
            'parent_id' => $parent?->getKey(),
            'type' => $type,
            'name' => $name,
            'heated' => in_array($type, [StructureNodeType::Room, StructureNodeType::Unit], true) ? true : null,
            ...$attributes,
        ]);
        $node->organization_id = $this->organization->getKey();
        $node->save();

        return $node;
    }

    private function asset(Property $property, StructureNode $location, string $name, string $type, string $installedAt): Asset
    {
        $asset = new Asset([
            'property_id' => $property->getKey(),
            'structure_node_id' => $location->getKey(),
            'asset_type_id' => AssetType::query()->firstOrCreate(['name' => $type])->getKey(),
            'name' => $name,
            'installed_at' => $installedAt,
        ]);
        $asset->organization_id = $this->organization->getKey();
        $asset->save();

        return $asset;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function meter(Property $property, string $name, EnergyMedium $medium, MeterKind $kind, ?StructureNode $location, array $attributes = []): Meter
    {
        $meter = new Meter([
            'property_id' => $property->getKey(),
            'structure_node_id' => $location?->getKey(),
            'name' => $name,
            'medium' => $medium,
            'kind' => $kind,
            'unit' => $medium->defaultUnit(),
            'factor' => 1,
            'is_active' => true,
            ...$attributes,
        ]);
        $meter->organization_id = $this->organization->getKey();
        $meter->save();
        $this->meterCount++;

        return $meter;
    }

    private function weather(): void
    {
        $this->temperature = [];
        $anomaly = 0.0;

        foreach ($this->days() as $day) {
            $anomaly = 0.7 * $anomaly + $this->random->getFloat(-2.5, 2.5);
            $this->temperature[$day->toDateString()] = 10.0 + 9.5 * sin(2 * M_PI * ($day->dayOfYear - 110) / 365) + $anomaly;
        }
    }

    /**
     * @return iterable<int, CarbonImmutable>
     */
    private function days(): iterable
    {
        for ($day = $this->start; $day->lessThan($this->today); $day = $day->addDay()) {
            yield $day;
        }
    }

    /**
     * @param  Closure(CarbonImmutable, float): float  $model
     * @return array<string, float>
     */
    private function series(Closure $model, float $noise): array
    {
        $series = [];

        foreach ($this->days() as $day) {
            $date = $day->toDateString();
            $series[$date] = max(0.0, $model($day, $this->temperature[$date]) * (1 + $this->random->getFloat(-1, 1) * $noise));
        }

        return $series;
    }

    /**
     * @param  array<string, array<string, float>>  $series
     * @param  array<int, string>  $keys
     * @return array<string, float>
     */
    private function sum(array $series, array $keys): array
    {
        $sum = [];

        foreach ($keys as $key) {
            foreach ($series[$key] as $date => $value) {
                $sum[$date] = ($sum[$date] ?? 0.0) + $value;
            }
        }

        return $sum;
    }

    /**
     * @param  array<string, float>  $daily
     */
    private function readings(Meter $meter, array $daily, string $schedule, MeterReadingSource $source, float $startValue): void
    {
        $factor = (float) $meter->factor ?: 1.0;
        $cumulative = [];
        $total = 0.0;

        foreach ($daily as $date => $value) {
            $cumulative[$date] = $total;
            $total += $value;
        }

        $counterAt = function (CarbonImmutable $at) use ($daily, $cumulative, $total): float {
            $date = $at->toDateString();

            if (! isset($daily[$date])) {
                return $at->lessThan($this->start) ? 0.0 : $total;
            }

            return $cumulative[$date] + $daily[$date] * ($at->secondsSinceMidnight() / 86400);
        };

        $now = now();
        $rows = [];

        foreach ($this->readingTimes($schedule) as $at) {
            $rows[] = [
                'id' => (string) Str::uuid7(),
                'organization_id' => $this->organization->getKey(),
                'meter_id' => $meter->getKey(),
                'read_at' => $at->toDateTimeString(),
                'value' => round($startValue + $counterAt($at) / $factor, 3),
                'source' => $source->value,
                'status' => 'plausible',
                'flag' => null,
                'photo' => null,
                'note' => null,
                'created_by' => $source === MeterReadingSource::Manual ? $this->userId : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            MeterReading::query()->insert($chunk);
        }

        $this->readingCount += count($rows);
        $this->aggregator->refresh($meter);
    }

    /**
     * @return array<int, CarbonImmutable>
     */
    private function readingTimes(string $schedule): array
    {
        if ($schedule === 'daily') {
            return [...iterator_to_array($this->days(), false), $this->today];
        }

        $times = [$this->start->setTime(9, 0)];

        for ($month = $this->start->addMonth(); $month->lessThan($this->today); $month = $month->addMonth()) {
            $at = $month->addDays($this->random->getInt(0, 3))->setTime($this->random->getInt(7, 15), $this->random->getInt(0, 59));

            if ($at->lessThan($this->today)) {
                $times[] = $at;
            }
        }

        return $times;
    }
}
