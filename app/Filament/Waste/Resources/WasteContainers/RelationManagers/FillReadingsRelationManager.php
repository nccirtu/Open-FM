<?php

namespace App\Filament\Waste\Resources\WasteContainers\RelationManagers;

use App\Models\WasteContainer;
use App\Services\Waste\WasteSensorService;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class FillReadingsRelationManager extends RelationManager
{
    protected static string $relationship = 'fillReadings';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('waste.models.fill_reading.plural');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('measured_at', 'desc')
            ->emptyStateHeading(__('waste.sensor.empty'))
            ->emptyStateDescription(__('waste.sensor.empty_help'))
            ->columns([
                TextColumn::make('measured_at')
                    ->label(__('waste.fields.collected_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('fill_level_pct')
                    ->label(__('waste.fields.fill_level'))
                    ->suffix(' %')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 90 => 'danger',
                        $state >= 75 => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('temperature_c')
                    ->label(__('waste.sensor.temperature'))
                    ->suffix(' °C')
                    ->placeholder('—'),
                IconColumn::make('pickup_detected')
                    ->label(__('waste.sensor.pickup_detected'))
                    ->boolean(),
                TextColumn::make('sensor_id')
                    ->label(__('waste.sensor.sensor_id'))
                    ->fontFamily('mono')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Action::make('recordReading')
                    ->label(__('waste.sensor.record'))
                    ->icon(Heroicon::OutlinedSignal)
                    ->authorize(fn (): bool => auth()->user()?->can('update', $this->getOwnerRecord()) ?? false)
                    ->schema([
                        DateTimePicker::make('measured_at')
                            ->label(__('waste.fields.collected_at'))
                            ->seconds(false)
                            ->default(now())
                            ->required(),
                        TextInput::make('fill_level_pct')
                            ->label(__('waste.fields.fill_level'))
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->required(),
                        Toggle::make('pickup_detected')
                            ->label(__('waste.sensor.pickup_detected')),
                    ])
                    ->action(function (array $data): void {
                        /** @var WasteContainer $container */
                        $container = $this->getOwnerRecord();
                        $result = app(WasteSensorService::class)->record(
                            $container,
                            CarbonImmutable::parse($data['measured_at']),
                            (int) $data['fill_level_pct'],
                            pickupDetected: (bool) ($data['pickup_detected'] ?? false),
                        );

                        Notification::make()
                            ->success()
                            ->title(__('waste.sensor.recorded'))
                            ->body(match (true) {
                                $result['collection'] !== null => __('waste.sensor.pickup_booked'),
                                $result['request'] !== null => __('waste.sensor.pickup_requested'),
                                default => null,
                            })
                            ->send();
                    }),
            ]);
    }
}
