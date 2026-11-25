<?php

namespace App\Services\Waste;

use App\Enums\AssetServiceStatus;
use App\Enums\CostEntryKind;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Enums\PropertyUsageType;
use App\Enums\StructureNodeType;
use App\Enums\WasteCollectionSource;
use App\Enums\WasteConfirmationChannel;
use App\Enums\WasteEvidenceStatus;
use App\Enums\WasteGoalType;
use App\Enums\WasteIntervalUnit;
use App\Enums\WasteMissedReason;
use App\Enums\WastePickupKind;
use App\Enums\WastePickupStatus;
use App\Enums\WasteScheduleSource;
use App\Models\Asset;
use App\Models\Company;
use App\Models\CostEntry;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Property;
use App\Models\StructureNode;
use App\Models\WasteCollection;
use App\Models\WasteContainer;
use App\Models\WasteContainerType;
use App\Models\WasteEmissionFactor;
use App\Models\WasteEvidence;
use App\Models\WasteFillReading;
use App\Models\WasteFraction;
use App\Models\WasteFractionGroup;
use App\Models\WasteGoal;
use App\Models\WastePickup;
use App\Models\WasteSchedule;
use App\Models\WasteTreatment;
use App\Models\WasteTreatmentCategory;
use Carbon\CarbonImmutable;
use Closure;
use Filament\Facades\Filament;
use Random\Engine\Mt19937;
use Random\Randomizer;

class WasteDemoData
{
    public const PROPERTY_PREFIX = 'ABF-DEMO-';

    public const NOTE = 'Demo-Daten – werden von „php artisan waste:demo“ neu erzeugt.';

    public const PROJECT_CODE = 'ABF-DEMO-P1';

    public const FACTOR_SOURCE = 'Beispielwert (Demo) – durch Faktoren aus anerkannter Quelle ersetzen';

    /**
     * @var array<int, array{code: string, name: string, group: string, density: int, treatment: string, color: string, hazardous?: bool}>
     */
    private const FRACTIONS = [
        ['code' => '20 01 01', 'name' => 'Papier/Pappe', 'group' => 'paper', 'density' => 60, 'treatment' => 'R3', 'color' => '#2f68cc'],
        ['code' => '15 01 06', 'name' => 'Leichtverpackungen', 'group' => 'packaging', 'density' => 30, 'treatment' => 'R3', 'color' => '#dfae00'],
        ['code' => '20 01 08', 'name' => 'Bioabfall', 'group' => 'bio', 'density' => 450, 'treatment' => 'R3', 'color' => '#8a5a2e'],
        ['code' => '20 01 02', 'name' => 'Glas', 'group' => 'glass', 'density' => 300, 'treatment' => 'R5', 'color' => '#2e8f55'],
        ['code' => '20 01 38', 'name' => 'Holz', 'group' => 'wood', 'density' => 150, 'treatment' => 'R1', 'color' => '#c28744'],
        ['code' => '20 03 01', 'name' => 'Gemischte Siedlungsabfälle', 'group' => 'mixed', 'density' => 120, 'treatment' => 'R1', 'color' => '#4d5459'],
        ['code' => '20 01 21*', 'name' => 'Leuchtstoffröhren', 'group' => 'hazardous', 'density' => 150, 'treatment' => 'R4', 'color' => '#d1472a', 'hazardous' => true],
        ['code' => '13 02 05*', 'name' => 'Altöl', 'group' => 'hazardous', 'density' => 900, 'treatment' => 'R9', 'color' => '#7a2e1f', 'hazardous' => true],
        ['code' => '20 01 36', 'name' => 'Elektroaltgeräte', 'group' => 'electrical', 'density' => 200, 'treatment' => 'R4', 'color' => '#5b8def'],
        ['code' => '17 01 01', 'name' => 'Beton', 'group' => 'construction', 'density' => 1500, 'treatment' => 'R5', 'color' => '#9aa5b1'],
        ['code' => '17 02 01', 'name' => 'Bauholz', 'group' => 'construction', 'density' => 250, 'treatment' => 'R1', 'color' => '#a0703c'],
        ['code' => '17 06 04', 'name' => 'Dämmmaterial', 'group' => 'construction', 'density' => 30, 'treatment' => 'D1', 'color' => '#e9d36a'],
        ['code' => '17 08 02', 'name' => 'Baustoffe auf Gipsbasis', 'group' => 'construction', 'density' => 800, 'treatment' => 'R5', 'color' => '#d9d4c7'],
        ['code' => '17 09 04', 'name' => 'Gemischte Bau- und Abbruchabfälle', 'group' => 'mixed', 'density' => 400, 'treatment' => 'R12', 'color' => '#5c5c5c'],
    ];

    private Organization $organization;

    private Randomizer $random;

    /**
     * @var array<string, WasteFraction>
     */
    private array $fractions = [];

    public function __construct(
        private WasteLookupDefaults $defaults,
        private WasteTemplateImporter $templates,
        private WasteCollectionRecorder $recorder,
        private PickupGenerator $generator,
        private PickupService $pickups,
    ) {}

    /**
     * @return array{properties: array<int, string>, containers: int, collections: int, pickups: int}
     */
    public function seed(Organization $organization, ?CarbonImmutable $today = null, int $seed = 2026): array
    {
        $today = ($today ?? CarbonImmutable::today())->startOfDay();

        return $this->withinTenant($organization, function () use ($today, $seed): array {
            $this->random = new Randomizer(new Mt19937($seed));
            $this->deleteDemoRecords();
            $this->defaults->seed($this->organization);
            $this->fractions();

            $disposer = $this->company('Muster Entsorgung GmbH');
            $recycler = $this->company('Grünwert Recycling GmbH');

            $office = $this->property('01', 'Lindenallee 14', PropertyUsageType::Commercial, ['latitude' => 49.0094, 'longitude' => 8.4044, 'net_area' => 2400, 'gross_area' => 2900, 'construction_year' => 1985]);
            $north = $this->collectionPoint($office, 'Müllplatz Nord');
            $kitchen = $this->collectionPoint($office, 'Kantine Anlieferung');
            $technical = $this->collectionPoint($office, 'Technikraum UG');
            $this->units($office, ['Kanzlei Weber' => 420, 'Praxis Dr. Lang' => 260, 'Kantine' => 180]);

            $market = $this->property('02', 'Gewerbering 3', PropertyUsageType::Commercial, ['latitude' => 49.0255, 'longitude' => 8.3605, 'net_area' => 2150, 'gross_area' => 2400, 'construction_year' => 2004]);
            $ramp = $this->collectionPoint($market, 'Rampe');
            $yard = $this->collectionPoint($market, 'Hof');
            $workshop = $this->collectionPoint($market, 'Werkstatt');
            $this->units($market, ['Lebensmittelmarkt' => 1800, 'Kfz-Werkstatt' => 350]);

            $plans = [
                [$office, $north, '20 01 01', ['B-0101', 'B-0102', 'B-0103', 'B-0104'], 'bin', 1100, $disposer, ['unit' => WasteIntervalUnit::Week, 'every' => 1, 'weekday' => 2, 'staging' => true], [12.5, null, 4.0], [55, 95]],
                [$office, $kitchen, '20 01 08', ['B-0111', 'B-0112', 'B-0113'], 'bin', 240, $disposer, ['unit' => WasteIntervalUnit::Week, 'every' => 1, 'weekday' => 3], [8.9, null, 2.0], [50, 100]],
                [$office, $north, '15 01 06', ['B-0117', 'B-0118', 'B-0119'], 'bin', 1100, $disposer, ['unit' => WasteIntervalUnit::Week, 'every' => 2, 'weekday' => 5, 'staging' => true], [6.0, null, null], [25, 45]],
                [$office, $north, '20 03 01', ['B-0120', 'B-0121'], 'bin', 1100, $disposer, ['unit' => WasteIntervalUnit::Week, 'every' => 2, 'weekday' => 4], [24.0, null, 4.0], [60, 100]],
                [$office, $technical, '20 01 21*', ['B-0150'], 'collection_box', 60, $disposer, null, [35.0, null, null], null],
                [$market, $yard, '20 01 02', ['B-0131', 'B-0132'], 'bin', 240, $recycler, ['unit' => WasteIntervalUnit::Month, 'every' => 1, 'weekday' => 1, 'week' => 1], [null, null, null], [45, 100]],
                [$market, $ramp, '20 01 38', ['B-0140'], 'skip', 7000, $recycler, null, [95.0, 60.0, null], null],
                [$market, $ramp, '20 01 01', ['B-0142'], 'compactor', 20000, $disposer, null, [120.0, 145.0, 180.0], null],
                [$market, $yard, '20 03 01', ['B-0201', 'B-0202'], 'bin', 1100, $disposer, ['unit' => WasteIntervalUnit::Week, 'every' => 1, 'weekday' => 1], [24.0, null, 4.0], [85, 100]],
                [$market, $workshop, '13 02 05*', ['B-0160'], 'other', 1000, $disposer, null, [40.0, 250.0, null], null],
            ];

            $collections = 0;
            $containerCount = 0;
            $startOfHistory = $today->startOfYear();

            foreach ($plans as [$property, $point, $code, $numbers, $type, $volume, $company, $rule, $prices, $fill]) {
                $containers = collect($numbers)->map(fn (string $number): WasteContainer => $this->container($property, $point, $code, $number, $type, $volume, $company, $prices));
                $containerCount += $containers->count();
                $schedule = $this->schedule($property, $point, $containers->all(), $rule, $startOfHistory);
                $collections += $fill !== null ? $this->history($schedule, $containers->all(), $startOfHistory, $today, $fill) : 0;
            }

            $collections += $this->onDemandHistory($today);
            $this->openCases($today);
            $this->invoices($startOfHistory, $today);
            $collections += $this->previousYear($today);
            $this->evidence($office, $market, $disposer, $today);
            $this->goals($office, $market, $today);
            $this->emissionFactors();
            $collections += $this->constructionProject($office, $recycler, $today);
            $this->sensorReadings($today);
            $this->assetToDispose($office, $technical);

            $created = WasteSchedule::query()->whereHas('property', fn ($query) => $query->where('property_number', 'like', self::PROPERTY_PREFIX.'%'))->get()
                ->sum(fn (WasteSchedule $schedule): int => $this->generator->generate($schedule, $today));

            return [
                'properties' => [$office->property_number, $market->property_number],
                'containers' => $containerCount,
                'collections' => $collections,
                'pickups' => $created,
            ];
        });
    }

    public function remove(Organization $organization): int
    {
        return $this->withinTenant($organization, fn (): int => $this->deleteDemoRecords());
    }

    private function withinTenant(Organization $organization, Closure $callback): mixed
    {
        $this->organization = $organization;
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
        $organizationId = $this->organization->getKey();

        CostEntry::query()->withoutGlobalScopes()->where('organization_id', $organizationId)->where('description', self::NOTE)->delete();
        WasteGoal::query()->withoutGlobalScopes()->where('organization_id', $organizationId)->where('notes', self::NOTE)->delete();
        WasteEmissionFactor::query()->withoutGlobalScopes()->where('organization_id', $organizationId)->where('source', self::FACTOR_SOURCE)->delete();
        Project::query()->withoutGlobalScopes()->where('organization_id', $organizationId)->where('code', self::PROJECT_CODE)->get()->each(fn (Project $project) => $project->delete());

        return Property::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $this->organization->getKey())
            ->where('property_number', 'like', self::PROPERTY_PREFIX.'%')
            ->get()
            ->each(fn (Property $property) => $property->delete())
            ->count();
    }

    private function fractions(): void
    {
        if (array_key_exists('de', WasteTemplateImporter::templateOptions())) {
            $this->templates->importTemplate($this->organization, 'de');
        }

        foreach (self::FRACTIONS as $sort => $spec) {
            $fraction = WasteFraction::query()->where('code', $spec['code'])->first();

            if ($fraction === null) {
                $fraction = WasteFraction::query()->create([
                    'name' => $spec['name'],
                    'code' => $spec['code'],
                    'is_hazardous' => $spec['hazardous'] ?? false,
                    'waste_fraction_group_id' => WasteFractionGroup::query()->where('key', $spec['group'])->value('id'),
                    'color' => $spec['color'],
                    'density_kg_m3' => $spec['density'],
                    'waste_treatment_id' => WasteTreatment::query()->where('code', $spec['treatment'])->value('id'),
                    'sort' => $sort,
                ]);
            }

            $this->fractions[$spec['code']] = $fraction;
        }
    }

    private function company(string $name): Company
    {
        return Company::query()->firstOrCreate(['name' => $name], ['role' => 'supplier']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function property(string $suffix, string $street, PropertyUsageType $usage, array $attributes = []): Property
    {
        return Property::query()->create([
            'property_number' => self::PROPERTY_PREFIX.$suffix,
            'street' => $street,
            'zip_code' => '76131',
            'city' => 'Karlsruhe',
            'country' => 'DE',
            'usage_type' => $usage,
            ...$attributes,
        ]);
    }

    /**
     * @param  array<string, int>  $units
     */
    private function units(Property $property, array $units): void
    {
        foreach (array_keys($units) as $sort => $name) {
            StructureNode::query()->create([
                'property_id' => $property->getKey(),
                'type' => StructureNodeType::Unit,
                'name' => $name,
                'area' => $units[$name],
                'sort' => $sort,
            ]);
        }
    }

    private function collectionPoint(Property $property, string $name): StructureNode
    {
        return StructureNode::query()->create([
            'property_id' => $property->getKey(),
            'type' => StructureNodeType::Outdoor,
            'name' => $name,
        ]);
    }

    /**
     * @param  array{0: float|null, 1: float|null, 2: float|null}  $prices
     */
    private function container(Property $property, StructureNode $point, string $code, string $number, string $type, int $volume, Company $company, array $prices): WasteContainer
    {
        return WasteContainer::query()->create([
            'property_id' => $property->getKey(),
            'structure_node_id' => $point->getKey(),
            'waste_fraction_id' => $this->fractions[$code]->getKey(),
            'waste_container_type_id' => WasteContainerType::query()->where('key', $type)->value('id'),
            'company_id' => $company->getKey(),
            'number' => $number,
            'volume_l' => $volume,
            'price_per_pickup' => $prices[0],
            'price_per_tonne' => $prices[1],
            'monthly_rent' => $prices[2],
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<int, WasteContainer>  $containers
     * @param  array{unit: WasteIntervalUnit, every: int, weekday: int, week?: int, staging?: bool}|null  $rule
     */
    private function schedule(Property $property, StructureNode $point, array $containers, ?array $rule, CarbonImmutable $startsOn): WasteSchedule
    {
        $schedule = WasteSchedule::query()->create([
            'property_id' => $property->getKey(),
            'structure_node_id' => $point->getKey(),
            'source' => $rule === null ? WasteScheduleSource::OnDemand : WasteScheduleSource::Rule,
            'interval_unit' => $rule['unit'] ?? WasteIntervalUnit::Week,
            'interval_value' => $rule['every'] ?? 1,
            'weekday' => $rule['weekday'] ?? null,
            'week_of_month' => $rule['week'] ?? null,
            'window_from' => '06:00',
            'window_to' => '14:00',
            'needs_staging' => $rule['staging'] ?? false,
            'confirmation_channels' => [WasteConfirmationChannel::Scan->value, WasteConfirmationChannel::Data->value],
            'starts_on' => $startsOn->toDateString(),
            'is_active' => true,
        ]);
        $schedule->containers()->attach(array_map(fn (WasteContainer $container): string => $container->getKey(), $containers));

        return $schedule;
    }

    /**
     * @param  array<int, WasteContainer>  $containers
     * @param  array{0: int, 1: int}  $fill
     */
    private function history(WasteSchedule $schedule, array $containers, CarbonImmutable $from, CarbonImmutable $today, array $fill): int
    {
        $dates = app(ScheduleOccurrences::class)->between($schedule, $from, $today->subDay());
        $count = 0;

        foreach ($dates as $date) {
            foreach ($containers as $container) {
                $pickup = $this->pickup($schedule, $container, $date, WastePickupKind::Regular);
                $level = $this->random->getInt($fill[0], $fill[1]);
                $mode = $this->random->getInt(1, 20);

                match (true) {
                    $mode <= 8 => $this->pickups->confirm($pickup, WasteCollectionSource::Import, $level, round($this->recorder->estimate($container, $level) * $this->random->getFloat(0.85, 1.15), 1)),
                    $mode <= 13 => $this->pickups->confirm($pickup, WasteCollectionSource::Scan, $level),
                    default => $this->recorder->completeByPlan($pickup),
                };
                $count++;
            }
        }

        return $count;
    }

    private function openCases(CarbonImmutable $today): void
    {
        $rest = WastePickup::query()
            ->whereHas('container', fn ($query) => $query->whereIn('number', ['B-0120', 'B-0121']))
            ->where('status', WastePickupStatus::Confirmed->value)
            ->orderByDesc('due_on')
            ->limit(2)
            ->get();

        foreach ($rest as $pickup) {
            $extra = $this->pickups->markMissed($pickup, WasteMissedReason::DisposerAbsent, self::NOTE);

            if ($extra !== null && $pickup->due_on !== null) {
                $this->pickups->plan($extra, $pickup->due_on->addDay());
                $this->pickups->confirm($extra, WasteCollectionSource::Scan, 90);
            }
        }

        $compactor = WasteContainer::query()->where('number', 'B-0142')->first();

        if ($compactor !== null) {
            $request = $this->pickups->request($compactor);
            $request->forceFill(['requested_at' => $today->subDay()->setTime(10, 12)])->save();
        }
    }

    private function onDemandHistory(CarbonImmutable $today): int
    {
        $count = 0;
        $series = [
            'B-0142' => [21, [3200, 4800], false],
            'B-0140' => [30, [1200, 2000], false],
        ];

        foreach ($series as $number => [$every, $mass, $documented]) {
            $container = WasteContainer::query()->where('number', $number)->sole();

            for ($date = $today->startOfYear()->addDays(10); $date->lessThan($today->subDays(5)); $date = $date->addDays($every)) {
                $this->onDemandPickup($container, $date, (float) $this->random->getInt(...$mass));
                $count++;
            }
        }

        $hazardous = [
            ['B-0150', 230, 38, 'UES-2026-0113'],
            ['B-0150', 120, 52, 'UES-2026-0418'],
            ['B-0150', 40, 45, null],
            ['B-0150', 3, 41, null],
            ['B-0160', 200, 800, 'BGS-4471-2026'],
            ['B-0160', 110, 820, 'BGS-4502-2026'],
            ['B-0160', 20, 790, null],
        ];

        foreach ($hazardous as [$number, $daysAgo, $mass, $document]) {
            $container = WasteContainer::query()->where('number', $number)->sole();
            $collection = $this->onDemandPickup($container, $today->subDays($daysAgo), (float) $mass);
            $collection->forceFill(['document_number' => $document, 'certificate_number' => $document !== null ? 'ENA-'.$container->number.'-2026' : null])->save();
            $count++;
        }

        return $count;
    }

    private function onDemandPickup(WasteContainer $container, CarbonImmutable $date, float $massKg): WasteCollection
    {
        $schedule = $container->schedules()->first();
        $pickup = $this->pickup($schedule, $container, $date, WastePickupKind::OnDemand);
        $pickup->forceFill(['requested_at' => $date->subDays(2)->setTime(9, 0)])->save();
        $this->pickups->confirm($pickup, WasteCollectionSource::Import, massKg: $massKg);

        return $pickup->collection()->sole();
    }

    private function invoices(CarbonImmutable $from, CarbonImmutable $today): void
    {
        $check = app(WasteInvoiceCheck::class);
        $lastMonth = $today->startOfMonth()->subMonth();

        for ($month = $from->startOfMonth(); $month->lessThan($today->startOfMonth()); $month = $month->addMonth()) {
            foreach ($check->rows($month) as $row) {
                $container = $row['container'];

                if (! str_starts_with((string) $container->property?->property_number, self::PROPERTY_PREFIX)) {
                    continue;
                }

                $isLast = $month->equalTo($lastMonth);
                $net = match (true) {
                    $isLast && $container->number === 'B-0201' => null,
                    $isLast && $container->number === 'B-0101' => $row['expected'] * 1.15,
                    $row['expected'] === null => $container->number === 'B-0131' ? 25.0 : null,
                    default => $row['expected'],
                };

                if ($net === null || $net <= 0) {
                    continue;
                }

                $entry = new CostEntry([
                    'costable_type' => $container->getMorphClass(),
                    'costable_id' => $container->getKey(),
                    'company_id' => $container->company_id,
                    'kind' => CostEntryKind::Actual,
                    'amount' => round($net * 1.19, 2),
                    'tax_rate' => 19,
                    'net_amount' => round($net, 2),
                    'invoice_number' => 'RE-'.$month->format('Ym').'-'.$container->number,
                    'booked_on' => $month->endOfMonth()->toDateString(),
                    'vendor' => $container->company?->name,
                    'description' => self::NOTE,
                ]);
                $entry->organization_id = $this->organization->getKey();
                $entry->save();
            }
        }
    }

    private function previousYear(CarbonImmutable $today): int
    {
        $months = max(1, $today->month - 1 + $today->day / $today->daysInMonth);
        $count = 0;

        $totals = WasteCollection::query()
            ->whereHas('property', fn ($query) => $query->where('property_number', 'like', self::PROPERTY_PREFIX.'%'))
            ->whereNull('project_id')
            ->whereYear('collected_at', $today->year)
            ->selectRaw('property_id, waste_fraction_id, SUM(mass_kg) as mass')
            ->groupBy('property_id', 'waste_fraction_id')
            ->get();

        foreach ($totals as $total) {
            $property = Property::query()->find($total->property_id);
            $fraction = WasteFraction::query()->find($total->waste_fraction_id);
            $monthly = (float) $total->getAttribute('mass') / $months * 1.08;

            for ($month = 1; $month <= 12; $month++) {
                $this->recorder->recordForProperty(
                    $property,
                    $fraction,
                    CarbonImmutable::create($today->year - 1, $month)->endOfMonth()->setTime(12, 0),
                    round($monthly * $this->random->getFloat(0.9, 1.1), 1),
                    WasteCollectionSource::Import,
                    ['note' => self::NOTE, 'document_number' => $fraction->is_hazardous ? sprintf('MA-%d-%02d', $today->year - 1, $month) : null],
                );
                $count++;
            }
        }

        return $count;
    }

    private function evidence(Property $office, Property $market, Company $disposer, CarbonImmutable $today): void
    {
        $records = [
            [$office, 'site_plan', WasteEvidenceStatus::Available, null, 'Lageplan Müllplatz Nord, Kantine und Technikraum (Stand '.$today->year.').'],
            [$office, 'photos', WasteEvidenceStatus::Available, null, 'Fotos der Sammelstellen vom '.$today->subMonths(2)->format('d.m.Y').'.'],
            [$office, 'practice_records', WasteEvidenceStatus::Available, null, 'Wiegescheine des Entsorgers liegen monatlich vor.'],
            [$office, 'acceptor_declaration', WasteEvidenceStatus::Available, $today->addYear()->endOfYear(), 'Erklärung der Muster Entsorgung GmbH.'],
            [$market, 'acceptor_declaration', WasteEvidenceStatus::Available, $today->addDays(30), 'Erklärung läuft bald ab — erneuern.'],
            [$market, 'site_plan', WasteEvidenceStatus::Open, null, null],
        ];

        foreach ($records as [$property, $type, $status, $validUntil, $note]) {
            WasteEvidence::query()->create([
                'property_id' => $property->getKey(),
                'company_id' => $type === 'acceptor_declaration' ? $disposer->getKey() : null,
                'year' => $today->year,
                'type' => $type,
                'status' => $status,
                'valid_until' => $validUntil?->toDateString(),
                'note' => $note,
            ]);
        }
    }

    private function goals(Property $office, Property $market, CarbonImmutable $today): void
    {
        $goals = [
            ['Recyclingquote Portfolio', null, WasteGoalType::MinRecyclingRate, 65, null],
            ['Getrenntsammlung Lindenallee', $office, WasteGoalType::MinSeparationRate, 90, null],
            ['Abfallmenge Gewerbering', $market, WasteGoalType::MaxMass, 95, null],
            ['Reduktion Gewerbering', $market, WasteGoalType::Reduction, 3, $today->year - 1],
            ['Abfall je m² Lindenallee', $office, WasteGoalType::MaxMassPerArea, 12, null],
        ];

        foreach ($goals as [$name, $property, $type, $target, $baseline]) {
            WasteGoal::query()->create([
                'property_id' => $property?->getKey(),
                'name' => $name,
                'type' => $type,
                'year' => $today->year,
                'target_value' => $target,
                'baseline_year' => $baseline,
                'notes' => self::NOTE,
            ]);
        }
    }

    private function emissionFactors(): void
    {
        $factors = ['recycling' => 21, 'other_recovery' => 380, 'incineration' => 450, 'landfill' => 520];

        foreach ($factors as $key => $factor) {
            WasteEmissionFactor::query()->create([
                'waste_treatment_category_id' => WasteTreatmentCategory::query()->where('key', $key)->value('id'),
                'kg_co2e_per_t' => $factor,
                'source' => self::FACTOR_SOURCE,
            ]);
        }
    }

    private function constructionProject(Property $office, Company $recycler, CarbonImmutable $today): int
    {
        $project = Project::query()->create([
            'property_id' => $office->getKey(),
            'code' => self::PROJECT_CODE,
            'name' => 'Umbau Kantine Lindenallee (Demo)',
            'description' => self::NOTE,
            'type' => ProjectType::Remodel,
            'status' => ProjectStatus::Active,
            'start_date' => $today->subDays(60)->toDateString(),
            'end_date' => $today->addDays(30)->toDateString(),
            'budget' => 250000,
        ]);

        $loads = [
            ['17 01 01', 18000, 12, 50, 'WS-77120'],
            ['17 09 04', 3500, 9, 45, 'WS-77131'],
            ['17 02 01', 1800, 6, 38, 'WS-77142'],
            ['17 08 02', 1200, 2, 30, 'WS-77150'],
            ['17 06 04', 300, 8, 21, 'WS-77163'],
        ];

        foreach ($loads as [$code, $mass, $volume, $daysAgo, $slip]) {
            $this->recorder->recordForProperty($office, $this->fractions[$code], $today->subDays($daysAgo)->setTime(10, 0), (float) $mass, WasteCollectionSource::Manual, [
                'project_id' => $project->getKey(),
                'company_id' => $recycler->getKey(),
                'volume_m3' => (float) $volume,
                'document_number' => $slip,
            ]);
        }

        return count($loads);
    }

    private function sensorReadings(CarbonImmutable $today): void
    {
        $container = WasteContainer::query()->where('number', 'B-0142')->sole();

        for ($day = 13; $day >= 0; $day--) {
            $reading = new WasteFillReading([
                'waste_container_id' => $container->getKey(),
                'measured_at' => $today->subDays($day)->setTime(6, 0),
                'fill_level_pct' => (int) round(22 + (13 - $day) * 4.9),
                'temperature_c' => $this->random->getInt(110, 190) / 10,
                'sensor_id' => 'LORA-7F3A',
                'pickup_detected' => false,
            ]);
            $reading->organization_id = $this->organization->getKey();
            $reading->save();
        }
    }

    private function assetToDispose(Property $office, StructureNode $technical): void
    {
        Asset::query()->create([
            'property_id' => $office->getKey(),
            'structure_node_id' => $technical->getKey(),
            'name' => 'Kühlschrank Teeküche (Demo)',
            'manufacturer' => 'Liebherr',
            'service_status' => AssetServiceStatus::OutOfOperation,
        ]);
    }

    private function pickup(WasteSchedule $schedule, WasteContainer $container, CarbonImmutable $date, WastePickupKind $kind): WastePickup
    {
        return WastePickup::query()->create([
            'waste_schedule_id' => $schedule->getKey(),
            'waste_container_id' => $container->getKey(),
            'property_id' => $container->property_id,
            'due_on' => $date->toDateString(),
            'kind' => $kind,
            'status' => WastePickupStatus::Planned,
        ]);
    }
}
