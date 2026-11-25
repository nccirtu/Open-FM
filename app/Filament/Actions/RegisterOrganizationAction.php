<?php

namespace App\Filament\Actions;

use App\Models\Organization;
use App\Models\User;
use App\Services\Classification\ClassificationTemplateImporter;
use App\Services\MaintenanceCatalogImporter;
use App\Services\OrganizationRoleProvisioner;
use App\Services\Waste\WasteLookupDefaults;
use App\Services\Waste\WasteTemplateImporter;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class RegisterOrganizationAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'registerOrganization';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('app.organizations.register'))
            ->icon(Heroicon::Plus)
            ->visible(fn (): bool => auth()->user()?->can('create', Organization::class) ?? false)
            ->modalHeading(__('app.organizations.register'))
            ->modalSubmitActionLabel('Anlegen')
            ->modalWidth(Width::Medium)
            ->schema([
                TextInput::make('name')
                    ->label(__('app.organizations.name'))
                    ->required()
                    ->maxLength(255),
                Select::make('default_locale')
                    ->label(__('app.organizations.default_locale'))
                    ->options(Organization::LOCALES)
                    ->default('de')
                    ->required()
                    ->native(false),
                Select::make('maintenance_catalog')
                    ->label(__('maintenance.catalogs.import_label'))
                    ->helperText(__('maintenance.catalogs.import_help'))
                    ->options(MaintenanceCatalogImporter::countryOptions())
                    ->placeholder(__('maintenance.catalogs.import_none'))
                    ->nullable()
                    ->native(false),
                Select::make('classification_template')
                    ->label(__('classifications.import.label'))
                    ->helperText(__('classifications.import.help'))
                    ->options(ClassificationTemplateImporter::templateOptions())
                    ->placeholder(__('classifications.import.none'))
                    ->nullable()
                    ->native(false),
                Select::make('waste_template')
                    ->label(__('waste.templates.import_label'))
                    ->helperText(__('waste.templates.import_help'))
                    ->options(WasteTemplateImporter::templateOptions())
                    ->placeholder(__('waste.templates.import_none'))
                    ->nullable()
                    ->native(false),
            ])
            ->action(function (array $data, $livewire): void {
                $organization = static::register($data, auth()->user());

                $livewire->redirect(Filament::getUrl($organization));
            });
    }

    /**
     * @param  array{name: string, default_locale: string, maintenance_catalog?: ?string, classification_template?: ?string, waste_template?: ?string}  $data
     */
    public static function register(array $data, User $user): Organization
    {
        $maintenanceCatalog = $data['maintenance_catalog'] ?? null;
        unset($data['maintenance_catalog']);

        $classificationTemplate = $data['classification_template'] ?? null;
        unset($data['classification_template']);

        $wasteTemplate = $data['waste_template'] ?? null;
        unset($data['waste_template']);

        $data['slug'] = static::generateUniqueSlug($data['name']);

        $organization = Organization::create($data);

        $organization->users()->attach($user);

        $admin = app(OrganizationRoleProvisioner::class)->provision($organization);

        $previousTeamId = getPermissionsTeamId();
        setPermissionsTeamId($organization->getKey());

        try {
            $user->assignRole($admin);
        } finally {
            setPermissionsTeamId($previousTeamId);
        }

        if ($maintenanceCatalog !== null) {
            app(MaintenanceCatalogImporter::class)->import($organization, $maintenanceCatalog);
        }

        if ($classificationTemplate !== null) {
            app(ClassificationTemplateImporter::class)->importTemplate($organization, $classificationTemplate);
        }

        app(WasteLookupDefaults::class)->seed($organization);

        if ($wasteTemplate !== null) {
            app(WasteTemplateImporter::class)->importTemplate($organization, $wasteTemplate);
        }

        return $organization;
    }

    protected static function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'mandant';
        $slug = $base;
        $suffix = 2;

        while (Organization::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
