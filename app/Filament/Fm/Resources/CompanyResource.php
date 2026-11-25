<?php

namespace App\Filament\Fm\Resources;

use App\Enums\CompanyRole;
use App\Filament\Fm\Resources\CompanyResource\Pages\CreateCompany;
use App\Filament\Fm\Resources\CompanyResource\Pages\EditCompany;
use App\Filament\Fm\Resources\CompanyResource\Pages\ListCompanies;
use App\Filament\Fm\Resources\CompanyResource\Pages\ViewCompany;
use App\Filament\Fm\Resources\CompanyResource\RelationManagers\AddressesRelationManager;
use App\Filament\Fm\Resources\CompanyResource\RelationManagers\ContactsRelationManager;
use App\Filament\RelationManagers\CompanyCostEntriesRelationManager;
use App\Filament\RelationManagers\ContractsRelationManager;
use App\Filament\RelationManagers\FolderFilesRelationManager;
use App\Filament\RelationManagers\MaintenancePlansRelationManager;
use App\Filament\RelationManagers\TicketsRelationManager;
use App\Filament\Resources\PropertyResource\RelationManagers\AssetsRelationManager;
use App\Models\Company;
use App\Support\ServicePartner;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?int $navigationSort = 10;

    public static function getModelLabel(): string
    {
        return __('companies.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('companies.models');
    }

    public static function getNavigationLabel(): string
    {
        return __('companies.navigation');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_groups.partners');
    }

    public static function getEloquentQuery(): Builder
    {
        return ServicePartner::restrict(parent::getEloquentQuery(), auth()->user(), 'id');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('companies.sections.master'))
                ->columns(2)
                ->schema(self::masterComponents()),
            Section::make(__('companies.sections.finance'))
                ->columns(2)
                ->collapsible()
                ->schema([
                    TextInput::make('vat_id')->label(__('companies.fields.vat_id'))->maxLength(30),
                    TextInput::make('tax_number')->label(__('companies.fields.tax_number'))->maxLength(30),
                    TextInput::make('iban')->label(__('companies.fields.iban'))->maxLength(40),
                    TextInput::make('bic')->label(__('companies.fields.bic'))->maxLength(20),
                    Textarea::make('notes')->label(__('companies.fields.notes'))->rows(3)->columnSpanFull(),
                ]),
        ]);
    }

    /**
     * @return array<int, Component>
     */
    public static function masterComponents(CompanyRole $defaultRole = CompanyRole::Supplier): array
    {
        return [
            TextInput::make('name')
                ->label(__('companies.fields.name'))
                ->required()
                ->maxLength(255)
                ->unique(table: Company::class, column: 'name', ignoreRecord: true, modifyRuleUsing: fn (Unique $rule) => $rule->where('organization_id', Filament::getTenant()?->getKey())),
            Select::make('role')->label(__('companies.fields.role'))->options(CompanyRole::class)->default($defaultRole)->required()->native(false),
            TextInput::make('email')->label(__('companies.fields.email'))->email()->maxLength(255),
            TextInput::make('phone')->label(__('companies.fields.phone'))->tel()->maxLength(50),
            TextInput::make('website')->label(__('companies.fields.website'))->url()->rule('url:http,https')->maxLength(255),
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('companies.sections.master'))
                ->columns(3)
                ->schema([
                    TextEntry::make('name')->label(__('companies.fields.name'))->weight('bold'),
                    TextEntry::make('role')->label(__('companies.fields.role'))->badge(),
                    TextEntry::make('website')->label(__('companies.fields.website'))->url(fn (?string $state): ?string => Str::sanitizeUrl($state), shouldOpenInNewTab: true)->placeholder('—'),
                    TextEntry::make('email')->label(__('companies.fields.email'))->url(fn (?string $state): ?string => $state ? 'mailto:'.$state : null)->placeholder('—'),
                    TextEntry::make('phone')->label(__('companies.fields.phone'))->placeholder('—'),
                    TextEntry::make('primary_contact')
                        ->label(__('companies.fields.primary_contact'))
                        ->state(fn (Company $record): ?string => $record->primaryContact?->fullName())
                        ->placeholder('—'),
                    TextEntry::make('primary_address')
                        ->label(__('companies.fields.primary_address'))
                        ->state(fn (Company $record): ?string => $record->primaryAddress ? trim(implode(', ', array_filter([$record->primaryAddress->street, trim($record->primaryAddress->zip_code.' '.$record->primaryAddress->city), $record->primaryAddress->country]))) : null)
                        ->placeholder('—')
                        ->columnSpanFull(),
                ]),
            Section::make(__('companies.sections.finance'))
                ->columns(2)
                ->collapsible()
                ->schema([
                    TextEntry::make('vat_id')->label(__('companies.fields.vat_id'))->placeholder('—'),
                    TextEntry::make('tax_number')->label(__('companies.fields.tax_number'))->placeholder('—'),
                    TextEntry::make('iban')->label(__('companies.fields.iban'))->placeholder('—')->copyable(),
                    TextEntry::make('bic')->label(__('companies.fields.bic'))->placeholder('—'),
                    TextEntry::make('notes')->label(__('companies.fields.notes'))->placeholder('—')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('companies.fields.name'))->searchable()->sortable()->weight('medium'),
                TextColumn::make('role')->label(__('companies.fields.role'))->badge(),
                TextColumn::make('primaryAddress.city')->label(__('companies.fields.primary_address'))->placeholder('—')->description(fn (Company $record): ?string => $record->primaryAddress?->street),
                TextColumn::make('primaryContact.last_name')->label(__('companies.fields.primary_contact'))->state(fn (Company $record): ?string => $record->primaryContact?->fullName())->placeholder('—'),
                TextColumn::make('email')->label(__('companies.fields.email'))->placeholder('—')->searchable(),
                TextColumn::make('phone')->label(__('companies.fields.phone'))->placeholder('—'),
                TextColumn::make('assets_count')->label(__('companies.fields.assets'))->counts('assets'),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('role')->label(__('companies.filters.role'))->options(CompanyRole::class),
            ])
            ->recordActions([ActionGroup::make([ViewAction::make(), EditAction::make(), DeleteAction::make()])])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getRelations(): array
    {
        return [
            AddressesRelationManager::class,
            ContactsRelationManager::class,
            AssetsRelationManager::class,
            TicketsRelationManager::class,
            ContractsRelationManager::class,
            MaintenancePlansRelationManager::class,
            CompanyCostEntriesRelationManager::class,
            FolderFilesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompanies::route('/'),
            'create' => CreateCompany::route('/create'),
            'view' => ViewCompany::route('/{record}'),
            'edit' => EditCompany::route('/{record}/edit'),
        ];
    }
}
