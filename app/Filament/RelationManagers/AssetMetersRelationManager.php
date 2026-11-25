<?php

namespace App\Filament\RelationManagers;

use App\Filament\Energy\Resources\MeterResource;
use App\Models\Meter;
use App\Services\Energy\EnergyReport;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AssetMetersRelationManager extends RelationManager
{
    protected static string $relationship = 'measuringMeters';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('energy.tab');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->can('viewAny', Meter::class) ?? false;
    }

    public function table(Table $table): Table
    {
        $year = CarbonImmutable::today()->startOfYear();
        $report = app(EnergyReport::class);

        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('energy.meter.model'))
                    ->description(fn (Meter $record): ?string => $record->number)
                    ->weight('medium'),
                TextColumn::make('medium')
                    ->label(__('energy.fields.medium'))
                    ->badge(),
                TextColumn::make('share')
                    ->label(__('energy.fields.share'))
                    ->suffix(' %')
                    ->numeric(decimalPlaces: 0),
                TextColumn::make('allocated')
                    ->label(__('energy.fields.consumption_year', ['year' => $year->year]))
                    ->state(fn (Meter $record): string => number_format($report->consumption([$record], $year, CarbonImmutable::today()) * (float) $record->pivot->share / 100, 0, ',', '.').' '.$record->evaluationUnit())
                    ->alignEnd(),
            ])
            ->recordActions([
                Action::make('open')
                    ->label(__('filament-actions::view.single.label'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Meter $record): string => MeterResource::getUrl('view', ['record' => $record], panel: 'energy')),
            ]);
    }
}
