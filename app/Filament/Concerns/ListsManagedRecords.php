<?php

namespace App\Filament\Concerns;

use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

trait ListsManagedRecords
{
    abstract protected function mineTabLabel(): string;

    abstract protected function allTabLabel(): string;

    public function getTabs(): array
    {
        if (! $this->canSeeAllManagedRecords()) {
            return [];
        }

        return [
            'mine' => Tab::make($this->mineTabLabel())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->managedBy(auth()->id()))
                ->badge(fn (): int => $this->managedRecordsQuery()->count()),
            'all' => Tab::make($this->allTabLabel()),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        if (! $this->canSeeAllManagedRecords()) {
            return null;
        }

        return $this->managedRecordsQuery()->exists() ? 'mine' : 'all';
    }

    protected function canSeeAllManagedRecords(): bool
    {
        $model = static::getResource()::getModel();

        return auth()->user()?->can('view_all_'.$model::permissionSubject()) ?? false;
    }

    protected function managedRecordsQuery(): Builder
    {
        return static::getResource()::getEloquentQuery()->managedBy(auth()->id());
    }
}
