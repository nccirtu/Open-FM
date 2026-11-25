<?php

namespace App\Filament\Waste\Resources\WastePickups\Pages;

use App\Enums\WastePickupStatus;
use App\Filament\Waste\Resources\WastePickups\WastePickupResource;
use App\Models\WasteContainer;
use App\Models\WastePickup;
use App\Services\Waste\PickupService;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListWastePickups extends ListRecords
{
    protected static string $resource = WastePickupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('requestPickup')
                ->label(__('waste.actions.request_pickup'))
                ->icon('heroicon-o-hand-raised')
                ->authorize(fn (): bool => auth()->user()?->can('confirm', WastePickup::class) ?? false)
                ->schema([
                    Select::make('container')
                        ->label(__('waste.models.container.singular'))
                        ->options(fn (): array => WasteContainer::query()->active()->with('fraction')->orderBy('number')->get()->mapWithKeys(fn (WasteContainer $container): array => [$container->getKey() => $container->label()])->all())
                        ->searchable()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    app(PickupService::class)->request(WasteContainer::query()->findOrFail($data['container']));

                    Notification::make()->success()->title(__('waste.notifications.requested'))->send();
                }),
        ];
    }

    public function getTabs(): array
    {
        $today = CarbonImmutable::today()->toDateString();

        return [
            'recent' => Tab::make(__('waste.tabs.recent'))
                ->modifyQueryUsing(fn (Builder $query) => $query->recent()),
            'planned' => Tab::make(__('waste.tabs.planned'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', WastePickupStatus::Planned->value)->whereDate('due_on', '>', $today)),
            'requested' => Tab::make(__('waste.tabs.requested'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', WastePickupStatus::Requested->value)),
            'done' => Tab::make(__('waste.tabs.done'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', WastePickupStatus::Confirmed->value)),
            'exceptions' => Tab::make(__('waste.tabs.exceptions'))
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [WastePickupStatus::Missed->value, WastePickupStatus::Cancelled->value]))
                ->badge(fn (): ?int => WastePickup::query()->where('status', WastePickupStatus::Missed->value)->whereDate('due_on', '>=', CarbonImmutable::today()->subDays(30)->toDateString())->count() ?: null)
                ->badgeColor('danger'),
            'all' => Tab::make(__('waste.tabs.all')),
        ];
    }
}
