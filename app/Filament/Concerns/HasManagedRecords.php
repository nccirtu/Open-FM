<?php

namespace App\Filament\Concerns;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

trait HasManagedRecords
{
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->visibleTo(auth()->user());
    }

    public static function managerSelect(string $label): Select
    {
        return Select::make('manager_id')
            ->label($label)
            ->options(fn (): array => static::userOptions())
            ->default(fn (): ?string => Filament::auth()->id())
            ->searchable()
            ->native(false)
            ->disabled(fn (?Model $record): bool => ! static::canAssignManager($record));
    }

    public static function canAssignManager(?Model $record = null): bool
    {
        return Gate::allows('assignManager', $record ?? static::getModel());
    }

    /**
     * @return array<string, string>
     */
    public static function userOptions(): array
    {
        $tenant = Filament::getTenant();

        return User::query()
            ->when($tenant, fn (Builder $query) => $query->whereHas('organizations', fn (Builder $query) => $query->whereKey($tenant->getKey())))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
