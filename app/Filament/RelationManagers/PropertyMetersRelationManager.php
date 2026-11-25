<?php

namespace App\Filament\RelationManagers;

use App\Filament\Energy\Resources\MeterResource;
use App\Models\Meter;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PropertyMetersRelationManager extends RelationManager
{
    protected static string $relationship = 'meters';

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
        return MeterResource::table($table)
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['parent', 'latestReading'])
                ->withSum(['dailyValues as consumption_year' => fn (Builder $values) => $values->whereYear('date', now()->year)], 'consumption'))
            ->filters([])
            ->toolbarActions([])
            ->headerActions([
                Action::make('openEnergy')
                    ->label(__('energy.actions.open_in_energy'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (): string => MeterResource::getUrl('index', panel: 'energy')),
            ])
            ->recordActions([
                Action::make('open')
                    ->label(__('filament-actions::view.single.label'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Meter $record): string => MeterResource::getUrl('view', ['record' => $record], panel: 'energy')),
            ]);
    }
}
