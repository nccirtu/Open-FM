<?php

use App\Enums\WasteCollectionSource;
use App\Enums\WasteEvidenceStatus;
use App\Filament\Waste\Clusters\Settings\Pages\WasteSettingsPage;
use App\Models\Project;
use App\Models\Property;
use App\Models\WasteEvidence;
use App\Models\WasteFraction;
use App\Models\WasteSettings;
use App\Services\Waste\ConstructionWasteSummary;
use App\Services\Waste\CountryRules;
use App\Services\Waste\HazardousWasteRegister;
use App\Services\Waste\WasteCollectionRecorder;
use App\Services\Waste\WasteDocumentation;
use App\Services\Waste\WasteLookupDefaults;
use App\Services\Waste\WasteTemplateImporter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

/**
 * @param  array<string, mixed>  $values
 */
function countrySettings(array $values): WasteSettings
{
    $settings = WasteSettings::forOrganization(null);
    $settings->fill($values)->save();

    return $settings;
}

it('calculates register deadlines in calendar days, working days or months', function (array $rules, string $deadline) {
    loginAsAdminInWastePanel();
    countrySettings(['rules' => $rules]);
    $collection = app(WasteCollectionRecorder::class)->recordForProperty(Property::factory()->create(), WasteFraction::factory()->hazardous()->create(), CarbonImmutable::parse('2026-01-30 10:00'), 40, WasteCollectionSource::Import);

    expect(app(HazardousWasteRegister::class)->deadline($collection)?->toDateString())->toBe($deadline);
})->with([
    'default: 10 calendar days' => [[], '2026-02-09'],
    'Italy: 10 working days' => [['register_deadline_days' => null, 'register_deadline_working_days' => 10], '2026-02-13'],
    'France: 1 month' => [['register_deadline_days' => null, 'register_deadline_months' => 1], '2026-02-28'],
]);

it('applies a construction threshold in tonnes instead of cubic metres', function () {
    loginAsAdminInWastePanel();
    countrySettings(['rules' => ['construction_small_quantity_m3' => null, 'construction_small_quantity_t' => 750, 'construction_recovery_pct' => null]]);
    $project = Project::factory()->create(['property_id' => Property::factory()->create()->getKey()]);
    $concrete = WasteFraction::factory()->group('construction')->treatment('R5')->create(['code' => '31427']);
    app(WasteCollectionRecorder::class)->recordForProperty($project->property, $concrete, now(), 20000, WasteCollectionSource::Manual, ['project_id' => $project->getKey(), 'volume_m3' => 400]);

    expect(app(ConstructionWasteSummary::class)->for($project))->toMatchArray([
        'small_quantity' => true,
        'small_quantity_t' => 750.0,
        'small_quantity_m3' => null,
        'target' => null,
        'met' => null,
    ]);
});

it('requires evidence types only when their condition applies', function () {
    loginAsAdminInWastePanel();
    test()->travelTo(CarbonImmutable::parse('2026-10-15 10:00'));
    countrySettings(['evidence_types' => [
        ['key' => 'consignment_note', 'when' => 'hazardous', 'basis' => '§ 18 AWG 2002', 'url' => 'https://example.test/awg'],
        ['key' => 'pre_demolition_audit', 'when' => 'construction'],
        ['key' => 'waste_officer_appointment', 'when' => 'conditional'],
        ['key' => 'asbestos_survey', 'when' => 'built_before_2001'],
        ['key' => 'waste_contract', 'when' => 'always'],
    ]]);
    $property = Property::factory()->create(['construction_year' => 1995]);
    app(WasteCollectionRecorder::class)->recordForProperty($property, WasteFraction::factory()->hazardous()->create(), CarbonImmutable::parse('2026-05-01'), 30, WasteCollectionSource::Import);
    WasteEvidence::factory()->create(['property_id' => $property->getKey(), 'year' => 2026, 'type' => 'waste_officer_appointment', 'status' => WasteEvidenceStatus::Available]);

    $items = collect(app(WasteDocumentation::class)->for($property, 2026)['items'])->keyBy('type');

    expect($items['consignment_note'])->toMatchArray(['required' => true, 'status' => WasteEvidenceStatus::Open, 'basis' => '§ 18 AWG 2002', 'url' => 'https://example.test/awg'])
        ->and($items['consignment_note']['hint'])->toContain('0,03 t')
        ->and($items['pre_demolition_audit'])->toMatchArray(['required' => false, 'status' => WasteEvidenceStatus::NotRequired])
        ->and($items['waste_officer_appointment'])->toMatchArray(['required' => false, 'status' => WasteEvidenceStatus::Available])
        ->and($items['asbestos_survey'])->toMatchArray(['required' => true])
        ->and($items['waste_contract']['required'])->toBeTrue();
});

it('lists the country rules with units, legal basis and defaults', function () {
    loginAsAdminInWastePanel();
    $settings = countrySettings([
        'country' => 'it',
        'rules' => ['register_deadline_days' => null, 'register_deadline_working_days' => 10, 'retention_years' => 3, 'hazardous_threshold_t' => null],
        'rule_sources' => ['register_deadline_working_days' => ['basis' => 'art. 190 c. 3 D.Lgs. 152/2006', 'url' => 'https://www.normattiva.it/']],
    ]);

    $rows = collect(app(CountryRules::class)->rows($settings))->keyBy('key');

    expect($rows['register_deadline'])->toMatchArray(['value' => '10 Arbeitstage', 'default' => false, 'basis' => 'art. 190 c. 3 D.Lgs. 152/2006'])
        ->and($rows['retention_years']['value'])->toBe('3 Jahre')
        ->and($rows['hazardous_threshold_t'])->toMatchArray(['value' => 'gilt nicht', 'applies' => false])
        ->and($rows['separation_quota_pct'])->toMatchArray(['value' => '90 %', 'default' => true])
        ->and($rows['code_system']['value'])->toBe('EU-Abfallverzeichnis (6-stellig)');

    Livewire::test(WasteSettingsPage::class)
        ->assertSee('Länderregeln: Italien')
        ->assertSee('art. 190 c. 3 D.Lgs. 152/2006');
});

it('imports the legal sources of a country template', function () {
    loginAsAdmin();
    $path = sys_get_temp_dir().'/waste-templates-'.uniqid();
    File::ensureDirectoryExists($path);
    File::put($path.'/fr.json', json_encode([
        'fractions' => [['code' => '20 01 01', 'name' => 'Papiers et cartons', 'group' => 'paper', 'treatment' => 'R3']],
        'rules' => ['register_deadline_days' => null, 'register_deadline_months' => 1],
        'sources' => ['register_deadline_months' => ['basis' => 'R541-43 C. env.', 'url' => 'https://www.legifrance.gouv.fr/']],
        'evidence_types' => [['key' => 'register_extract', 'when' => 'always', 'basis' => 'R541-43 I']],
    ]));

    (new WasteTemplateImporter(app(WasteLookupDefaults::class), $path))->importTemplate(defaultOrganization(), 'fr');
    $settings = WasteSettings::forOrganization(defaultOrganization()->getKey());

    expect($settings->ruleSource('register_deadline_months'))->toBe(['basis' => 'R541-43 C. env.', 'url' => 'https://www.legifrance.gouv.fr/'])
        ->and($settings->evidenceRequirements())->toBe(['register_extract' => ['when' => 'always', 'basis' => 'R541-43 I', 'url' => '']]);

    File::deleteDirectory($path);
});

it('ships consistent country templates', function () {
    $files = glob(database_path('templates/waste/*.json')) ?: [];

    if ($files === []) {
        $this->markTestSkipped('Länder-Vorlagen sind nicht vorhanden (nicht versioniert).');
    }

    $knownRules = ['register_deadline_days', 'register_deadline_working_days', 'register_deadline_months', 'retention_years', 'hazardous_threshold_t', 'separation_quota_pct', 'quota_check_due', 'construction_small_quantity_m3', 'construction_small_quantity_t', 'construction_recovery_pct', 'asbestos_ban_year'];

    foreach ($files as $file) {
        $template = json_decode((string) file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
        $key = basename($file, '.json');

        expect(WasteTemplateImporter::TEMPLATES)->toHaveKey($key)
            ->and(array_diff(array_keys($template['rules']), $knownRules))->toBe([], "{$key}: unbekannte Regel")
            ->and($template['fractions'])->not->toBeEmpty()
            ->and(collect($template['fractions'])->pluck('code')->duplicates()->all())->toBe([], "{$key}: doppelte Abfallschlüssel");

        foreach ($template['evidence_types'] as $evidence) {
            foreach (['de', 'en', 'fr', 'hr', 'it', 'nl', 'pl', 'ro'] as $locale) {
                expect(trans()->has("waste.evidence_types.{$evidence['key']}", $locale, false))->toBeTrue("{$key}: Nachweisart {$evidence['key']} ohne Übersetzung ({$locale})");
            }

            expect($evidence['when'])->toMatch('/^(always|mixed|hazardous|construction|conditional|built_before_\d{4})$/');
        }

        foreach (['de', 'en'] as $locale) {
            expect(trans()->has("waste.templates.{$key}", $locale, false))->toBeTrue("{$key}: Vorlagenname fehlt ({$locale})");
        }
    }
});
