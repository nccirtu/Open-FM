<?php

use App\Enums\WasteCollectionSource;
use App\Models\Organization;
use App\Models\Property;
use App\Models\WasteContainerType;
use App\Models\WasteFraction;
use App\Models\WasteFractionGroup;
use App\Models\WasteSettings;
use App\Models\WasteTreatment;
use App\Models\WasteTreatmentCategory;
use App\Services\Waste\HazardousWasteRegister;
use App\Services\Waste\IcsCalendarParser;
use App\Services\Waste\WasteCollectionRecorder;
use App\Services\Waste\WasteDocumentation;
use App\Services\Waste\WasteLookupDefaults;
use App\Services\Waste\WasteTemplateImporter;
use Carbon\CarbonImmutable;

it('seeds editable master data per tenant in its language without overwriting changes', function () {
    loginAsAdmin();
    $organization = Organization::factory()->create(['default_locale' => 'en']);
    $defaults = app(WasteLookupDefaults::class);

    $defaults->seed($organization);

    $recycling = WasteTreatmentCategory::query()->withoutGlobalScopes()->where('organization_id', $organization->getKey())->where('key', 'recycling')->sole();
    $recycling->update(['name' => 'Stoffliche Verwertung']);
    WasteContainerType::query()->withoutGlobalScopes()->where('organization_id', $organization->getKey())->where('key', 'bin')->sole()->update(['name' => 'MGB 1.100 l']);

    $defaults->seed($organization);

    $mixed = WasteFractionGroup::query()->withoutGlobalScopes()->where('organization_id', $organization->getKey())->where('key', 'mixed')->sole();
    $r3 = WasteTreatment::query()->withoutGlobalScopes()->where('organization_id', $organization->getKey())->where('code', 'R3')->sole();

    expect(WasteTreatment::query()->withoutGlobalScopes()->where('organization_id', $organization->getKey())->count())->toBe(29)
        ->and($recycling->refresh()->name)->toBe('Stoffliche Verwertung')
        ->and($r3->waste_treatment_category_id)->toBe($recycling->getKey())
        ->and($mixed->counts_as_separated)->toBeFalse()
        ->and($mixed->name)->toBe(__('waste.defaults.fraction_groups.mixed', locale: 'en'))
        ->and(WasteContainerType::query()->withoutGlobalScopes()->where('organization_id', $organization->getKey())->where('key', 'bin')->value('name'))->toBe('MGB 1.100 l');
});

it('imports the fractions of a country template idempotently with group and treatment', function () {
    loginAsAdmin();
    $organization = defaultOrganization();
    $importer = new WasteTemplateImporter(app(WasteLookupDefaults::class), base_path('tests/Fixtures/waste'));

    expect($importer->importTemplate($organization, 'de'))->toBe(3)
        ->and($importer->importTemplate($organization, 'de'))->toBe(0)
        ->and($importer->importTemplate($organization, 'xx'))->toBe(0);

    $mixed = WasteFraction::query()->where('code', '20 03 01')->sole();
    $hazardous = WasteFraction::query()->where('code', '20 01 21*')->sole();

    expect($mixed->group?->key)->toBe('mixed')
        ->and($mixed->treatment?->code)->toBe('R1')
        ->and($hazardous->is_hazardous)->toBeTrue()
        ->and(WasteSettings::forOrganization($organization->getKey()))
        ->country->toBe('de')
        ->rule('register_deadline_days')->toBe(10)
        ->evidenceTypes()->toContain('acceptor_declaration');
});

it('switches off rules that a country template sets to null', function () {
    loginAsAdminInWastePanel();
    $organization = defaultOrganization();
    (new WasteTemplateImporter(app(WasteLookupDefaults::class), base_path('tests/Fixtures/waste')))->importTemplate($organization, 'at');
    $property = Property::factory()->create();
    $collection = app(WasteCollectionRecorder::class)->recordForProperty($property, WasteFraction::query()->where('code', '20 01 01')->sole(), CarbonImmutable::parse('2020-01-10'), 10, WasteCollectionSource::Import);
    $register = app(HazardousWasteRegister::class);
    $doc = app(WasteDocumentation::class)->for($property, (int) now()->year);

    expect($doc['separation'])->toMatchArray(['target' => null, 'met' => null, 'due' => null])
        ->and(collect($doc['items'])->pluck('type')->all())->toBe(['waste_management_concept', 'waste_contract'])
        ->and($register->deadline($collection))->toBeNull()
        ->and($register->status($collection))->toBe('open')
        ->and($register->retainUntil($collection)->toDateString())->toBe('2027-01-10');
});

it('reads pickup dates from an ICS calendar filtered by a keyword', function () {
    $ics = implode("\r\n", [
        'BEGIN:VCALENDAR',
        'BEGIN:VEVENT',
        'DTSTART;VALUE=DATE:20261013',
        'SUMMARY:Papier',
        '  und Pappe',
        'END:VEVENT',
        'BEGIN:VEVENT',
        'DTSTART;TZID=Europe/Berlin:20261014T060000',
        'SUMMARY:Restmüll',
        'END:VEVENT',
        'BEGIN:VEVENT',
        'DTSTART:20261027T050000Z',
        'SUMMARY:Altpapier',
        'END:VEVENT',
        'END:VCALENDAR',
    ]);

    $parser = new IcsCalendarParser;

    expect($parser->dates($ics, 'papier'))->toBe(['2026-10-13', '2026-10-27'])
        ->and($parser->dates($ics))->toBe(['2026-10-13', '2026-10-14', '2026-10-27']);
});
