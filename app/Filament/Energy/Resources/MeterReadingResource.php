<?php

namespace App\Filament\Energy\Resources;

use App\Enums\MeterReadingSource;
use App\Enums\MeterReadingStatus;
use App\Filament\Energy\Resources\MeterReadingResource\Pages\ListMeterReadings;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Services\Energy\MeterReadingRecorder;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MeterReadingResource extends Resource
{
    protected static ?string $model = MeterReading::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 20;

    public static function getModelLabel(): string
    {
        return __('energy.reading.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('energy.reading.models');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return self::readingsTable($table)
            ->recordActions([
                DeleteAction::make()
                    ->using(fn (MeterReading $record) => app(MeterReadingRecorder::class)->delete($record)),
            ]);
    }

    public static function readingsTable(Table $table, bool $withMeter = true): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['meter.property', 'creator']))
            ->defaultSort('read_at', 'desc')
            ->columns([
                TextColumn::make('meter.name')
                    ->label(__('energy.meter.model'))
                    ->description(fn (MeterReading $record): ?string => $record->meter?->property?->property_number)
                    ->searchable()
                    ->visible($withMeter),
                TextColumn::make('read_at')
                    ->label(__('energy.fields.read_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('value')
                    ->label(__('energy.fields.value'))
                    ->formatStateUsing(fn (MeterReading $record): string => number_format((float) $record->value, 3, ',', '.').' '.($record->meter?->unit ?? ''))
                    ->alignEnd(),
                TextColumn::make('source')
                    ->label(__('energy.fields.source'))
                    ->badge()
                    ->color('gray'),
                TextColumn::make('status')
                    ->label(__('energy.fields.status'))
                    ->badge()
                    ->description(fn (MeterReading $record): ?string => $record->flag ? __('energy.plausibility.short.'.$record->flag) : null),
                ImageColumn::make('photo')
                    ->label(__('energy.fields.photo'))
                    ->visibility('private')
                    ->square()
                    ->toggleable(),
                TextColumn::make('creator.name')
                    ->label(__('energy.fields.created_by'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('note')
                    ->label(__('energy.fields.note'))
                    ->limit(40)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('property')
                    ->label(__('property.model'))
                    ->relationship('meter.property', 'property_number')
                    ->searchable()
                    ->preload()
                    ->visible($withMeter),
                SelectFilter::make('meter_id')
                    ->label(__('energy.meter.model'))
                    ->options(fn (): array => Meter::query()->where('kind', '!=', 'virtual')->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->visible($withMeter),
                SelectFilter::make('source')
                    ->label(__('energy.fields.source'))
                    ->options(MeterReadingSource::class),
                SelectFilter::make('status')
                    ->label(__('energy.fields.status'))
                    ->options(MeterReadingStatus::class),
                Filter::make('read_at')
                    ->schema([
                        DatePicker::make('from')->label(__('energy.filters.from'))->native(false),
                        DatePicker::make('until')->label(__('energy.filters.until'))->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('read_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('read_at', '<=', $date)))
                    ->columns(2),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMeterReadings::route('/'),
        ];
    }
}
