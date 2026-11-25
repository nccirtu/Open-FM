<?php

namespace App\Filament\Waste\Widgets;

use App\Enums\WastePickupStatus;
use App\Filament\Waste\Pages\ConfirmPickupsPage;
use App\Filament\Waste\Resources\WastePickups\WastePickupResource;
use App\Filament\Waste\Resources\WasteSchedules\WasteScheduleResource;
use App\Models\Property;
use App\Models\WasteContainer;
use App\Models\WasteFraction;
use App\Models\WastePickup;
use App\Models\WasteSchedule;
use App\Services\Waste\PickupService;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use OpenFm\FilamentCalendar\CalendarEvent;
use OpenFm\FilamentCalendar\Enums\CalendarEventVariant;
use OpenFm\FilamentCalendar\Widgets\CalendarWidget;

class PickupCalendarWidget extends CalendarWidget
{
    protected static ?int $sort = 1;

    public static int $gridW = 12;

    public static int $gridH = 16;

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', WastePickup::class) ?? false;
    }

    protected function getFilterComponents(): array
    {
        return [
            Select::make('property_id')
                ->label(__('property.model'))
                ->options(fn (): array => Property::query()->whereHas('wasteContainers')->orderBy('property_number')->pluck('property_number', 'id')->all())
                ->placeholder(__('waste.filters.all_properties'))
                ->searchable()
                ->live(),
            Select::make('waste_fraction_id')
                ->label(__('waste.models.fraction.singular'))
                ->options(fn (): array => WasteFraction::query()->active()->orderBy('sort')->pluck('name', 'id')->all())
                ->placeholder(__('waste.filters.all_fractions'))
                ->live(),
            Select::make('status')
                ->label(__('waste.fields.status'))
                ->options(WastePickupStatus::class)
                ->placeholder(__('waste.filters.all_statuses'))
                ->live(),
        ];
    }

    public function getEvents(CarbonImmutable $start, CarbonImmutable $end): iterable
    {
        $pickups = WastePickup::query()
            ->with(['container.fraction', 'property'])
            ->where(fn (Builder $query) => $query
                ->whereBetween('due_on', [$start->toDateString(), $end->toDateString()])
                ->orWhere(fn (Builder $query) => $query
                    ->whereNull('due_on')
                    ->where('status', WastePickupStatus::Requested->value)
                    ->whereBetween('requested_at', [$start, $end->endOfDay()])))
            ->when(filled($this->filters['property_id'] ?? null), fn (Builder $query) => $query->where('property_id', $this->filters['property_id']))
            ->when(filled($this->filters['waste_fraction_id'] ?? null), fn (Builder $query) => $query->whereHas('container', fn (Builder $containers) => $containers->where('waste_fraction_id', $this->filters['waste_fraction_id'])))
            ->when(filled($this->filters['status'] ?? null), fn (Builder $query) => $query->where('status', $this->filters['status'] instanceof WastePickupStatus ? $this->filters['status']->value : $this->filters['status']))
            ->get();

        $showProperty = blank($this->filters['property_id'] ?? null);

        return $pickups
            ->groupBy(fn (WastePickup $pickup): string => implode('|', [
                $pickup->property_id,
                $pickup->container?->waste_fraction_id,
                $this->day($pickup)->toDateString(),
                $pickup->status->value,
            ]))
            ->map(fn (Collection $group): CalendarEvent => $this->event($group->values(), $showProperty))
            ->values();
    }

    public function getDateClickAction(): ?string
    {
        return auth()->user()?->can('confirm', WastePickup::class) ? 'planExtra' : null;
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make('createSchedule')
                ->label(__('waste.actions.create_schedule'))
                ->model(WasteSchedule::class)
                ->modalWidth(Width::ThreeExtraLarge)
                ->schema(fn (Schema $schema): Schema => WasteScheduleResource::form($schema))
                ->mutateDataUsing(fn (array $data): array => WasteScheduleResource::prepareData($data))
                ->after(fn (WasteSchedule $record) => WasteScheduleResource::generate($record))
                ->authorize(fn (): bool => auth()->user()?->can('create', WasteSchedule::class) ?? false),
        ];
    }

    public function showPickupsAction(): Action
    {
        return Action::make('showPickups')
            ->modalHeading(fn (array $arguments): string => $this->pickups($arguments)->first()?->container?->fraction?->name ?? __('waste.models.pickup.plural'))
            ->modalDescription(fn (array $arguments): ?string => $this->pickups($arguments)->first()?->property?->property_number)
            ->modalContent(fn (array $arguments) => view('filament.waste.calendar.pickups', ['pickups' => $this->pickups($arguments)]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('waste.actions.close'))
            ->extraModalFooterActions(function (Action $action): array {
                $pickups = $this->pickups($action->getArguments());
                $first = $pickups->first();

                return [
                    Action::make('openConfirmation')
                        ->label(__('waste.actions.open_confirmation'))
                        ->icon('heroicon-o-check-circle')
                        ->visible($first !== null && $pickups->contains(fn (WastePickup $pickup): bool => $pickup->isCompletedByPlan() || $pickup->isDue()))
                        ->url(fn (): string => ConfirmPickupsPage::getUrl(['property' => $first?->property_id, 'date' => $first?->due_on?->toDateString()])),
                    Action::make('openList')
                        ->label(__('waste.actions.open_pickups_list'))
                        ->color('gray')
                        ->url(fn (): string => WastePickupResource::getUrl('index')),
                ];
            });
    }

    public function planExtraAction(): Action
    {
        return Action::make('planExtra')
            ->label(__('waste.actions.plan_extra'))
            ->modalHeading(fn (array $arguments): string => __('waste.actions.plan_extra_on', ['date' => CarbonImmutable::parse($arguments['date'] ?? 'today')->format('d.m.Y')]))
            ->authorize(fn (): bool => auth()->user()?->can('confirm', WastePickup::class) ?? false)
            ->schema([
                Select::make('containers')
                    ->label(__('waste.models.container.plural'))
                    ->options(fn (): array => WasteContainer::query()
                        ->active()
                        ->with('fraction')
                        ->when(filled($this->filters['property_id'] ?? null), fn (Builder $query) => $query->where('property_id', $this->filters['property_id']))
                        ->orderBy('number')
                        ->get()
                        ->mapWithKeys(fn (WasteContainer $container): array => [$container->getKey() => $container->label()])
                        ->all())
                    ->multiple()
                    ->searchable()
                    ->required(),
            ])
            ->action(function (array $data, array $arguments): void {
                $date = CarbonImmutable::parse($arguments['date'] ?? 'today');
                $service = app(PickupService::class);

                WasteContainer::query()->whereKey($data['containers'])->get()
                    ->each(fn (WasteContainer $container) => $service->scheduleExtra($container, $date));

                Notification::make()->success()->title(__('waste.notifications.planned'))->send();
            });
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return Collection<int, WastePickup>
     */
    private function pickups(array $arguments): Collection
    {
        return WastePickup::query()
            ->with(['container.fraction', 'container.structureNode', 'property', 'collection'])
            ->whereKey((array) ($arguments['ids'] ?? []))
            ->get();
    }

    /**
     * @param  Collection<int, WastePickup>  $group
     */
    private function event(Collection $group, bool $showProperty): CalendarEvent
    {
        /** @var WastePickup $first */
        $first = $group->first();
        $fraction = $first->container?->fraction;
        $volumes = $group->map(fn (WastePickup $pickup): ?int => $pickup->container?->volume_l)->filter()->unique();

        $title = trim(($fraction?->name ?? '').' · '.($volumes->count() === 1
            ? $group->count().' × '.number_format((int) $volumes->first(), 0, ',', '.').' l'
            : trans_choice('waste.calendar.containers', $group->count(), ['count' => $group->count()])));

        [$icon, $variant] = match ($first->status) {
            WastePickupStatus::Confirmed => ['heroicon-m-check-circle', CalendarEventVariant::Default],
            WastePickupStatus::Missed => ['heroicon-m-x-circle', CalendarEventVariant::Strikethrough],
            WastePickupStatus::Requested => ['heroicon-m-hand-raised', CalendarEventVariant::Dashed],
            WastePickupStatus::Cancelled => ['heroicon-m-minus-circle', CalendarEventVariant::Strikethrough],
            WastePickupStatus::Planned => [null, CalendarEventVariant::Default],
        };

        return CalendarEvent::make(md5($group->modelKeys() === [] ? '' : implode(',', $group->modelKeys())))
            ->title($title)
            ->start($this->day($first))
            ->color($fraction?->displayColor())
            ->icon($icon)
            ->variant($variant)
            ->badge($showProperty ? $first->property?->property_number : null)
            ->description($first->statusLabel().' · '.$group->map(fn (WastePickup $pickup): ?string => $pickup->container?->number)->filter()->implode(', '))
            ->action('showPickups', ['ids' => $group->modelKeys()]);
    }

    private function day(WastePickup $pickup): CarbonImmutable
    {
        return CarbonImmutable::parse($pickup->due_on ?? $pickup->requested_at ?? now())->startOfDay();
    }
}
