<?php

namespace App\Filament\Waste\Resources\WasteEvidence;

use App\Enums\WasteEvidenceStatus;
use App\Filament\Waste\Pages\WasteDocumentationPage;
use App\Filament\Waste\Resources\WasteEvidence\Pages\ManageWasteEvidence;
use App\Models\WasteEvidence;
use App\Models\WasteSettings;
use App\Support\Uploads;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WasteEvidenceResource extends Resource
{
    protected static ?string $model = WasteEvidence::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static ?int $navigationSort = 62;

    protected static ?string $slug = 'nachweise';

    public static function getNavigationGroup(): ?string
    {
        return __('waste.navigation.compliance');
    }

    public static function getModelLabel(): string
    {
        return __('waste.models.evidence.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('waste.models.evidence.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('property_id')
                    ->label(__('property.model'))
                    ->relationship('property', 'property_number')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('year')
                    ->label(__('waste.fields.year'))
                    ->numeric()
                    ->minValue(2000)
                    ->maxValue(2100)
                    ->default(now()->year)
                    ->required(),
                Select::make('type')
                    ->label(__('waste.fields.evidence_type'))
                    ->options(fn (): array => collect(WasteSettings::forOrganization(null)->evidenceTypes())->mapWithKeys(fn (string $type): array => [$type => WasteEvidence::labelFor($type)])->all())
                    ->required(),
                ToggleButtons::make('status')
                    ->label(__('waste.fields.status'))
                    ->options(WasteEvidenceStatus::class)
                    ->default(WasteEvidenceStatus::Available)
                    ->inline()
                    ->required(),
                Select::make('company_id')
                    ->label(__('waste.fields.company'))
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),
                DatePicker::make('valid_until')
                    ->label(__('waste.fields.valid_until'))
                    ->native(false),
                FileUpload::make('attachments')
                    ->label(__('waste.fields.attachments'))
                    ->multiple()
                    ->visibility('private')
                    ->directory('waste-evidence')
                    ->acceptedFileTypes(Uploads::documents())
                    ->openable()
                    ->downloadable()
                    ->columnSpanFull(),
                Textarea::make('note')
                    ->label(__('waste.fields.note'))
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['property', 'company']))
            ->defaultSort('year', 'desc')
            ->columns([
                TextColumn::make('property.property_number')
                    ->label(__('property.model'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('year')
                    ->label(__('waste.fields.year'))
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('waste.fields.evidence_type'))
                    ->formatStateUsing(fn (string $state): string => WasteEvidence::labelFor($state))
                    ->description(fn (WasteEvidence $record): ?string => $record->company?->name),
                TextColumn::make('status')
                    ->label(__('waste.fields.status'))
                    ->badge(),
                TextColumn::make('valid_until')
                    ->label(__('waste.fields.valid_until'))
                    ->date('d.m.Y')
                    ->color(fn (WasteEvidence $record): ?string => $record->valid_until?->isBefore(CarbonImmutable::today()->addDays(60)) ? 'danger' : null)
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('attachments')
                    ->label(__('waste.fields.attachments'))
                    ->state(fn (WasteEvidence $record): int => count($record->attachments ?? []))
                    ->suffix(' ×')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('property_id')
                    ->label(__('property.model'))
                    ->relationship('property', 'property_number')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->label(__('waste.fields.status'))
                    ->options(WasteEvidenceStatus::class),
                Filter::make('expiring')
                    ->label(__('waste.filters.expiring'))
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('valid_until')->whereDate('valid_until', '<=', CarbonImmutable::today()->addDays(60)->toDateString())),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('openDocumentation')
                        ->label(__('waste.pages.documentation'))
                        ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                        ->url(fn (WasteEvidence $record): string => WasteDocumentationPage::getUrl(['property' => $record->property_id, 'year' => $record->year])),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageWasteEvidence::route('/'),
        ];
    }
}
