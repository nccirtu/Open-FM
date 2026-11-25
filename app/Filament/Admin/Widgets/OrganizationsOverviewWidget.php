<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\TicketStatus;
use App\Filament\Admin\Resources\OrganizationResource;
use App\Models\Organization;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OrganizationsOverviewWidget extends TableWidget
{
    public static int $gridW = 8;

    public static int $gridH = 13;

    public static function canView(): bool
    {
        return auth()->user() instanceof User;
    }

    public function getHeading(): ?string
    {
        return __('admin_dashboard.organizations.heading');
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('admin_dashboard.organizations.heading'))
            ->modelLabel(__('admin_dashboard.organizations.model'))
            ->pluralModelLabel(__('admin_dashboard.organizations.heading'))
            ->query(fn (): Builder => $this->organizations())
            ->defaultSort('name')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->recordUrl(fn (Model $record): string => OrganizationResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('name')
                    ->label(__('app.organizations.name'))
                    ->description(fn (Organization $record): string => $record->slug)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('users_count')
                    ->label(__('app.organizations.users'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('properties_count')
                    ->label(__('admin_dashboard.organizations.properties'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('assets_count')
                    ->label(__('admin_dashboard.organizations.assets'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('open_tickets_count')
                    ->label(__('admin_dashboard.organizations.open_tickets'))
                    ->numeric()
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'warning' : 'gray')
                    ->sortable(),
                TextColumn::make('last_change')
                    ->label(__('admin_dashboard.organizations.last_change'))
                    ->state(fn (Organization $record): ?string => collect([$record->last_property_change, $record->last_asset_change, $record->last_ticket_change])->filter()->max())
                    ->since()
                    ->placeholder('—'),
            ]);
    }

    protected function organizations(): Builder
    {
        $user = auth()->user();

        return Organization::query()
            ->when(
                $user instanceof User && ! $user->isSuperAdmin(),
                fn (Builder $query) => $query->whereIn('organizations.id', $user->administeredOrganizationIds() ?? []),
            )
            ->withCount([
                'users',
                'properties' => fn (Builder $query) => $query->withoutGlobalScopes(),
                'assets' => fn (Builder $query) => $query->withoutGlobalScopes(),
                'tickets as open_tickets_count' => fn (Builder $query) => $query->withoutGlobalScopes()->whereIn('status', TicketStatus::open()),
            ])
            ->withMax(['properties as last_property_change' => fn (Builder $query) => $query->withoutGlobalScopes()], 'updated_at')
            ->withMax(['assets as last_asset_change' => fn (Builder $query) => $query->withoutGlobalScopes()], 'updated_at')
            ->withMax(['tickets as last_ticket_change' => fn (Builder $query) => $query->withoutGlobalScopes()], 'updated_at');
    }
}
