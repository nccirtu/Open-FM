<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Enums\WasteCollectionSource;
use App\Models\Company;
use App\Models\Project;
use App\Models\WasteCollection;
use App\Models\WasteFraction;
use App\Models\WasteTreatment;
use App\Services\Waste\ConstructionWasteSummary;
use App\Services\Waste\WasteCollectionRecorder;
use App\Support\Uploads;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class WasteCollectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'wasteCollections';

    public function isReadOnly(): bool
    {
        return false;
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->can('viewAny', WasteCollection::class) ?? false;
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('waste.construction.title');
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('waste.construction.title'))
            ->description(fn (): string => $this->summaryText())
            ->modifyQueryUsing(fn ($query) => $query->with(['fraction', 'treatment', 'company']))
            ->defaultSort('collected_at', 'desc')
            ->emptyStateHeading(__('waste.construction.empty'))
            ->columns([
                TextColumn::make('collected_at')
                    ->label(__('waste.fields.collected_at'))
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('fraction.name')
                    ->label(__('waste.models.fraction.singular'))
                    ->description(fn (WasteCollection $record): ?string => $record->fraction?->code)
                    ->badge()
                    ->color(fn (WasteCollection $record): array => Color::hex($record->fraction?->displayColor() ?? '#94a3b8')),
                TextColumn::make('mass_kg')
                    ->label(__('waste.fields.mass_kg'))
                    ->numeric(decimalPlaces: 0, locale: 'de')
                    ->suffix(' kg')
                    ->summarize(Sum::make()->numeric(decimalPlaces: 0, locale: 'de')->suffix(' kg')),
                TextColumn::make('volume_m3')
                    ->label(__('waste.fields.volume_m3'))
                    ->numeric(decimalPlaces: 1, locale: 'de')
                    ->suffix(' m³')
                    ->placeholder('—')
                    ->summarize(Sum::make()->numeric(decimalPlaces: 1, locale: 'de')->suffix(' m³')),
                TextColumn::make('treatment.code')
                    ->label(__('waste.models.treatment.singular'))
                    ->tooltip(fn (WasteCollection $record): ?string => $record->treatment?->name)
                    ->placeholder('—'),
                TextColumn::make('company.name')
                    ->label(__('waste.fields.company'))
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('document_number')
                    ->label(__('waste.fields.document_number'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                $this->recordAction(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('delete')
                        ->label(__('filament-actions::delete.single.label'))
                        ->icon(Heroicon::OutlinedTrash)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->authorize(fn (WasteCollection $record): bool => auth()->user()?->can('delete', $record) ?? false)
                        ->action(fn (WasteCollection $record) => app(WasteCollectionRecorder::class)->delete($record)),
                ]),
            ]);
    }

    private function recordAction(): Action
    {
        return Action::make('recordConstructionWaste')
            ->label(__('waste.construction.record'))
            ->icon(Heroicon::OutlinedPlus)
            ->authorize(fn (): bool => auth()->user()?->can('create', WasteCollection::class) ?? false)
            ->disabled(fn (): bool => $this->getOwnerRecord()->property_id === null)
            ->tooltip(fn (): ?string => $this->getOwnerRecord()->property_id === null ? __('waste.construction.needs_property') : null)
            ->modalWidth('2xl')
            ->schema([
                Grid::make(2)->schema([
                    Select::make('fraction')
                        ->label(__('waste.models.fraction.singular'))
                        ->options(fn (): array => WasteFraction::query()->active()->orderBy('sort')->get()->mapWithKeys(fn (WasteFraction $fraction): array => [$fraction->getKey() => $fraction->label()])->all())
                        ->searchable()
                        ->required(),
                    DateTimePicker::make('collected_at')
                        ->label(__('waste.fields.collected_at'))
                        ->seconds(false)
                        ->default(now())
                        ->required(),
                    TextInput::make('mass_kg')
                        ->label(__('waste.fields.mass_kg'))
                        ->numeric()
                        ->minValue(0)
                        ->suffix('kg')
                        ->required(),
                    TextInput::make('volume_m3')
                        ->label(__('waste.fields.volume_m3'))
                        ->numeric()
                        ->minValue(0)
                        ->suffix('m³'),
                    Select::make('waste_treatment_id')
                        ->label(__('waste.models.treatment.singular'))
                        ->helperText(__('waste.help.treatment_default'))
                        ->options(fn (): array => WasteTreatment::query()->orderBy('sort')->get()->mapWithKeys(fn (WasteTreatment $treatment): array => [$treatment->getKey() => $treatment->label()])->all())
                        ->searchable(),
                    Select::make('company_id')
                        ->label(__('waste.fields.company'))
                        ->options(fn (): array => Company::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable(),
                    TextInput::make('document_number')
                        ->label(__('waste.fields.weighing_slip'))
                        ->maxLength(100),
                    FileUpload::make('receipt')
                        ->label(__('waste.fields.receipt'))
                        ->visibility('private')
                        ->directory('waste-receipts')
                        ->acceptedFileTypes(Uploads::documents()),
                ]),
                Textarea::make('note')
                    ->label(__('waste.fields.note'))
                    ->rows(2),
            ])
            ->action(function (array $data): void {
                /** @var Project $project */
                $project = $this->getOwnerRecord();

                app(WasteCollectionRecorder::class)->recordForProperty(
                    $project->property,
                    WasteFraction::query()->findOrFail($data['fraction']),
                    CarbonImmutable::parse($data['collected_at']),
                    (float) $data['mass_kg'],
                    WasteCollectionSource::Manual,
                    array_filter([
                        'project_id' => $project->getKey(),
                        'company_id' => $data['company_id'] ?? null,
                        'waste_treatment_id' => $data['waste_treatment_id'] ?? null,
                        'volume_m3' => filled($data['volume_m3'] ?? null) ? (float) $data['volume_m3'] : null,
                        'document_number' => $data['document_number'] ?? null,
                        'receipt' => $data['receipt'] ?? null,
                        'note' => $data['note'] ?? null,
                    ], fn (mixed $value): bool => $value !== null),
                );

                Notification::make()->success()->title(__('waste.notifications.collection_saved'))->send();
            });
    }

    private function summaryText(): string
    {
        /** @var Project $project */
        $project = $this->getOwnerRecord();
        $summary = app(ConstructionWasteSummary::class)->for($project);
        $parts = [];

        if ($summary['recovery_rate'] !== null && $summary['target'] === null) {
            $parts[] = __('waste.construction.recovery_rate_no_target', ['rate' => number_format($summary['recovery_rate'], 0, ',', '.')]);
        } elseif ($summary['recovery_rate'] !== null) {
            $parts[] = __('waste.construction.recovery_rate', [
                'rate' => number_format($summary['recovery_rate'], 0, ',', '.'),
                'target' => number_format($summary['target'], 0, ',', '.'),
                'status' => $summary['met'] ? __('waste.documentation.met') : __('waste.documentation.not_met'),
            ]);
        }

        $volume = number_format($summary['volume_m3'], 1, ',', '.').' m³ · '.number_format($summary['mass_t'], 1, ',', '.').' t';
        $limit = match (true) {
            $summary['small_quantity_t'] !== null => number_format($summary['small_quantity_t'], 0, ',', '.').' t',
            $summary['small_quantity_m3'] !== null => number_format($summary['small_quantity_m3'], 0, ',', '.').' m³',
            default => null,
        };

        $parts[] = match (true) {
            $limit === null => __('waste.construction.volume', ['volume' => $volume]),
            $summary['small_quantity'] => __('waste.construction.small_quantity', ['volume' => $volume, 'limit' => $limit]),
            default => __('waste.construction.documentation_required', ['volume' => $volume, 'limit' => $limit]),
        };

        if ($summary['asbestos_risk']) {
            $parts[] = __('waste.construction.asbestos', ['year' => $summary['construction_year'], 'ban' => $summary['asbestos_year']]);
        }

        return implode(' · ', $parts);
    }
}
