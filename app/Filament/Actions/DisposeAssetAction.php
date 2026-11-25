<?php

namespace App\Filament\Actions;

use App\Enums\AssetServiceStatus;
use App\Enums\WasteCollectionSource;
use App\Models\Asset;
use App\Models\Company;
use App\Models\WasteCollection;
use App\Models\WasteFraction;
use App\Models\WasteTreatment;
use App\Services\Waste\WasteCollectionRecorder;
use App\Support\Uploads;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;

class DisposeAssetAction
{
    public static function make(): Action
    {
        return Action::make('disposeAsset')
            ->label(__('waste.disposal.action'))
            ->icon(Heroicon::OutlinedArchiveBoxXMark)
            ->color('danger')
            ->modalHeading(fn (Asset $record): string => __('waste.disposal.heading', ['asset' => $record->name]))
            ->modalDescription(__('waste.disposal.description'))
            ->modalWidth('2xl')
            ->visible(fn (Asset $record): bool => $record->service_status !== AssetServiceStatus::Disposed && $record->property_id !== null)
            ->authorize(fn (Asset $record): bool => (auth()->user()?->can('update', $record) ?? false) && (auth()->user()?->can('create', WasteCollection::class) ?? false))
            ->schema([
                Grid::make(2)->schema([
                    Select::make('fraction')
                        ->label(__('waste.models.fraction.singular'))
                        ->options(fn (): array => WasteFraction::query()->active()->orderBy('sort')->get()->mapWithKeys(fn (WasteFraction $fraction): array => [$fraction->getKey() => $fraction->label()])->all())
                        ->searchable()
                        ->required(),
                    DatePicker::make('collected_at')
                        ->label(__('waste.disposal.date'))
                        ->default(today())
                        ->native(false)
                        ->required(),
                    TextInput::make('mass_kg')
                        ->label(__('waste.fields.mass_kg'))
                        ->numeric()
                        ->minValue(0)
                        ->suffix('kg')
                        ->required(),
                    Select::make('company_id')
                        ->label(__('waste.fields.company'))
                        ->options(fn (): array => Company::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable(),
                    Select::make('waste_treatment_id')
                        ->label(__('waste.models.treatment.singular'))
                        ->helperText(__('waste.help.treatment_default'))
                        ->options(fn (): array => WasteTreatment::query()->orderBy('sort')->get()->mapWithKeys(fn (WasteTreatment $treatment): array => [$treatment->getKey() => $treatment->label()])->all())
                        ->searchable(),
                    TextInput::make('certificate_number')
                        ->label(__('waste.disposal.certificate'))
                        ->maxLength(100),
                ]),
                Toggle::make('batteries_removed')
                    ->label(__('waste.disposal.batteries_removed'))
                    ->helperText(__('waste.disposal.batteries_help')),
                FileUpload::make('receipt')
                    ->label(__('waste.fields.receipt'))
                    ->visibility('private')
                    ->directory('waste-receipts')
                    ->acceptedFileTypes(Uploads::documents()),
                Textarea::make('note')
                    ->label(__('waste.fields.note'))
                    ->rows(2),
            ])
            ->action(function (Asset $record, array $data): void {
                $date = CarbonImmutable::parse($data['collected_at']);

                DB::transaction(function () use ($record, $data, $date): void {
                    app(WasteCollectionRecorder::class)->recordForProperty(
                        $record->property,
                        WasteFraction::query()->findOrFail($data['fraction']),
                        $date->setTime(12, 0),
                        (float) $data['mass_kg'],
                        WasteCollectionSource::Manual,
                        array_filter([
                            'asset_id' => $record->getKey(),
                            'company_id' => $data['company_id'] ?? null,
                            'waste_treatment_id' => $data['waste_treatment_id'] ?? null,
                            'certificate_number' => $data['certificate_number'] ?? null,
                            'batteries_removed' => (bool) ($data['batteries_removed'] ?? false),
                            'receipt' => $data['receipt'] ?? null,
                            'note' => $data['note'] ?? __('waste.disposal.note', ['asset' => $record->name]),
                        ], fn (mixed $value): bool => $value !== null),
                    );

                    $record->update([
                        'service_status' => AssetServiceStatus::Disposed,
                        'decommissioned_at' => $record->decommissioned_at ?? $date,
                    ]);
                });

                Notification::make()->success()->title(__('waste.disposal.done'))->send();
            });
    }
}
