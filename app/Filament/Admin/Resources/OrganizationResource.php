<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\OrganizationResource\Pages\CreateOrganization;
use App\Filament\Admin\Resources\OrganizationResource\Pages\EditOrganization;
use App\Filament\Admin\Resources\OrganizationResource\Pages\ListOrganizations;
use App\Models\Organization;
use App\Services\Classification\ClassificationTemplateImporter;
use App\Services\MaintenanceCatalogImporter;
use App\Services\Waste\WasteTemplateImporter;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrganizationResource extends Resource
{
    protected static ?string $model = Organization::class;

    protected static ?string $navigationLabel = 'Mandanten';

    protected static ?string $modelLabel = 'Mandant';

    protected static ?string $pluralModelLabel = 'Mandanten';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return __('app.admin_groups.management');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->administeredByCurrentUser();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('app.organizations.master_data'))
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label(__('app.organizations.name'))
                        ->required()
                        ->maxLength(255),
                    TextInput::make('slug')
                        ->label(__('app.organizations.slug'))
                        ->required()
                        ->alphaDash()
                        ->unique(ignoreRecord: true)
                        ->helperText(__('app.organizations.slug_helper'))
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
                        ->native(false)
                        ->dehydrated(false)
                        ->visible(fn (string $operation): bool => $operation === 'create'),
                    Select::make('classification_template')
                        ->label(__('classifications.import.label'))
                        ->helperText(__('classifications.import.help'))
                        ->options(ClassificationTemplateImporter::templateOptions())
                        ->placeholder(__('classifications.import.none'))
                        ->nullable()
                        ->native(false)
                        ->dehydrated(false)
                        ->visible(fn (string $operation): bool => $operation === 'create'),
                    Select::make('waste_template')
                        ->label(__('waste.templates.import_label'))
                        ->helperText(__('waste.templates.import_help'))
                        ->options(WasteTemplateImporter::templateOptions())
                        ->placeholder(__('waste.templates.import_none'))
                        ->nullable()
                        ->native(false)
                        ->dehydrated(false)
                        ->visible(fn (string $operation): bool => $operation === 'create'),
                ]),
            Section::make(__('app.organizations.access_integrations'))
                ->columns(2)
                ->schema([
                    TagsInput::make('allowed_email_domains')
                        ->label(__('app.organizations.allowed_email_domains'))
                        ->placeholder(__('app.organizations.allowed_email_domains_placeholder'))
                        ->helperText('Nur Benutzer mit diesen E-Mail-Domains dürfen sich anmelden. Leer lassen, um alle Domains zu erlauben.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('app.organizations.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label(__('app.organizations.slug'))
                    ->searchable(),
                TextColumn::make('default_locale')
                    ->label(__('app.organizations.locale'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Organization::LOCALES[$state] ?? $state),
                TextColumn::make('users_count')
                    ->label(__('app.organizations.users'))
                    ->counts('users'),
                TextColumn::make('created_at')
                    ->label(__('app.organizations.created'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrganizations::route('/'),
            'create' => CreateOrganization::route('/create'),
            'edit' => EditOrganization::route('/{record}/edit'),
        ];
    }
}
