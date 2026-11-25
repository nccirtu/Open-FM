<?php

namespace App\Filament\Waste\Pages;

use App\Enums\WasteCollectionSource;
use App\Enums\WasteMissedReason;
use App\Models\Property;
use App\Models\StructureNode;
use App\Models\WasteContainer;
use App\Models\WastePickup;
use App\Services\Waste\PickupService;
use App\Support\Uploads;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

class ConfirmPickupsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?int $navigationSort = 15;

    protected static ?string $slug = 'leerungen-bestaetigen';

    protected string $view = 'filament.waste.pages.confirm-pickups';

    public const FILL_LEVELS = [25, 50, 75, 100];

    #[Url]
    public ?string $property = null;

    #[Url]
    public ?string $node = null;

    #[Url]
    public ?string $container = null;

    #[Url]
    public ?string $date = null;

    /**
     * @var array<int, string>
     */
    public array $selected = [];

    /**
     * @var array<string, int>
     */
    public array $fill = [];

    /**
     * @var array<string, mixed>|null
     */
    public ?array $filters = [];

    public static function getNavigationGroup(): ?string
    {
        return __('waste.navigation.operations');
    }

    public static function getNavigationLabel(): string
    {
        return __('waste.pages.confirm_pickups');
    }

    public function getTitle(): string
    {
        return __('waste.pages.confirm_pickups');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('confirm', WastePickup::class) ?? false;
    }

    public function mount(): void
    {
        $container = filled($this->container) ? WasteContainer::query()->find($this->container) : null;

        $this->filtersForm->fill([
            'property' => $container?->property_id ?? $this->property,
            'node' => $container?->structure_node_id ?? $this->node,
        ]);

        $this->selectAll();
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 1, 'sm' => 2])
                    ->schema([
                        Select::make('property')
                            ->label(__('property.model'))
                            ->options(fn (): array => Property::query()->whereHas('wasteContainers')->orderBy('property_number')->pluck('property_number', 'id')->all())
                            ->placeholder(__('waste.filters.all_properties'))
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('node', null);
                                $this->filtersChanged();
                            }),
                        Select::make('node')
                            ->label(__('waste.fields.collection_point'))
                            ->options(fn (): array => self::collectionPointOptions($this->filters['property'] ?? null))
                            ->placeholder(__('waste.filters.all_collection_points'))
                            ->live()
                            ->afterStateUpdated(fn () => $this->filtersChanged()),
                    ]),
            ])
            ->statePath('filters');
    }

    /**
     * @return Collection<int, WastePickup>
     */
    public function getPickups(): Collection
    {
        $today = CarbonImmutable::today();

        return WastePickup::query()
            ->with(['container.fraction', 'container.structureNode', 'property'])
            ->when(
                filled($this->date),
                fn (Builder $query) => $query->recent(0, CarbonImmutable::parse($this->date)),
                fn (Builder $query) => $query->recent(WastePickup::RECENT_DAYS, $today),
            )
            ->when(filled($this->filters['property'] ?? null), fn (Builder $query) => $query->where('property_id', $this->filters['property']))
            ->when(filled($this->filters['node'] ?? null), fn (Builder $query) => $query->whereHas('container', fn (Builder $containers) => $containers->where('structure_node_id', $this->filters['node'])))
            ->when(filled($this->container), fn (Builder $query) => $query->where('waste_container_id', $this->container))
            ->orderByDesc('due_on')
            ->get();
    }

    public function getScannedContainer(): ?WasteContainer
    {
        return filled($this->container) ? WasteContainer::query()->with(['fraction', 'structureNode', 'property'])->find($this->container) : null;
    }

    public function setFill(string $pickupId, int $percent): void
    {
        if (in_array($percent, self::FILL_LEVELS, true)) {
            $this->fill[$pickupId] = $percent;
        }
    }

    public function confirmSelectedAction(): Action
    {
        return Action::make('confirmSelected')
            ->label(fn (): string => trans_choice('waste.actions.confirm_selected', count($this->selected), ['count' => count($this->selected)]))
            ->icon('heroicon-o-check-circle')
            ->color('primary')
            ->size('lg')
            ->disabled(fn (): bool => $this->selected === [])
            ->authorize(fn (): bool => self::canAccess())
            ->action(function (): void {
                $service = app(PickupService::class);
                $pickups = $this->openPickups($this->selected);

                foreach ($pickups as $pickup) {
                    $service->confirm($pickup, WasteCollectionSource::Scan, $this->fill[$pickup->getKey()] ?? null);
                }

                Notification::make()
                    ->success()
                    ->title(trans_choice('waste.notifications.confirmed', $pickups->count(), ['count' => $pickups->count()]))
                    ->send();

                $this->selectAll();
            });
    }

    public function confirmPickupAction(): Action
    {
        return Action::make('confirmPickup')
            ->label(__('waste.actions.confirm_with_details'))
            ->icon('heroicon-o-scale')
            ->color('gray')
            ->modalHeading(fn (array $arguments): string => $this->openPickups([$arguments['pickup'] ?? ''])->first()?->container?->label() ?? __('waste.actions.confirm'))
            ->authorize(fn (): bool => self::canAccess())
            ->schema([
                ToggleButtons::make('fill_level_pct')
                    ->label(__('waste.fields.fill_level'))
                    ->options(collect(self::FILL_LEVELS)->mapWithKeys(fn (int $level): array => [$level => $level.' %'])->all())
                    ->inline(),
                TextInput::make('mass_kg')
                    ->label(__('waste.fields.mass_kg'))
                    ->helperText(__('waste.help.mass_optional'))
                    ->numeric()
                    ->minValue(0)
                    ->suffix('kg'),
                FileUpload::make('receipt')
                    ->label(__('waste.fields.receipt'))
                    ->image()
                    ->acceptedFileTypes(Uploads::images())
                    ->visibility('private')
                    ->directory('waste-receipts'),
                Textarea::make('note')
                    ->label(__('waste.fields.note'))
                    ->rows(2),
            ])
            ->action(function (array $data, array $arguments): void {
                $pickup = $this->openPickups([$arguments['pickup'] ?? ''])->firstOrFail();

                app(PickupService::class)->confirm(
                    $pickup,
                    WasteCollectionSource::Scan,
                    filled($data['fill_level_pct'] ?? null) ? (int) $data['fill_level_pct'] : null,
                    filled($data['mass_kg'] ?? null) ? (float) $data['mass_kg'] : null,
                    ['receipt' => $data['receipt'] ?? null, 'note' => $data['note'] ?? null],
                );

                Notification::make()->success()->title(trans_choice('waste.notifications.confirmed', 1, ['count' => 1]))->send();

                $this->selectAll();
            });
    }

    public function missedAction(): Action
    {
        return Action::make('missed')
            ->label(__('waste.actions.report_missed'))
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->modalHeading(__('waste.actions.report_missed'))
            ->modalDescription(__('waste.help.missed'))
            ->authorize(fn (): bool => self::canAccess())
            ->schema([
                Select::make('reason')
                    ->label(__('waste.fields.missed_reason'))
                    ->options(WasteMissedReason::class)
                    ->default(WasteMissedReason::DisposerAbsent->value)
                    ->required()
                    ->native(false),
                Textarea::make('note')
                    ->label(__('waste.fields.note'))
                    ->rows(2),
            ])
            ->action(function (array $data, array $arguments): void {
                $pickup = $this->openPickups([$arguments['pickup'] ?? ''])->firstOrFail();
                $reason = $data['reason'] instanceof WasteMissedReason ? $data['reason'] : WasteMissedReason::from($data['reason']);

                $extra = app(PickupService::class)->markMissed($pickup, $reason, $data['note'] ?? null);

                Notification::make()
                    ->warning()
                    ->title(__('waste.notifications.missed'))
                    ->body($extra !== null ? __('waste.notifications.extra_requested') : null)
                    ->send();

                $this->selectAll();
            });
    }

    public function requestPickupAction(): Action
    {
        return Action::make('requestPickup')
            ->label(__('waste.actions.report_full'))
            ->icon('heroicon-o-exclamation-triangle')
            ->color('warning')
            ->requiresConfirmation()
            ->modalDescription(__('waste.help.report_full'))
            ->authorize(fn (): bool => self::canAccess())
            ->action(function (): void {
                $container = $this->getScannedContainer();

                if ($container === null) {
                    return;
                }

                app(PickupService::class)->request($container);

                Notification::make()->success()->title(__('waste.notifications.requested'))->send();
            });
    }

    /**
     * @return array<string, string>
     */
    public static function collectionPointOptions(?string $propertyId): array
    {
        return StructureNode::query()
            ->when(filled($propertyId), fn (Builder $query) => $query->where('property_id', $propertyId))
            ->whereIn('id', WasteContainer::query()->whereNotNull('structure_node_id')->select('structure_node_id'))
            ->get()
            ->mapWithKeys(fn (StructureNode $node): array => [$node->getKey() => $node->fullPath()])
            ->sort()
            ->all();
    }

    protected function filtersChanged(): void
    {
        $this->property = $this->filters['property'] ?? null;
        $this->node = $this->filters['node'] ?? null;
        $this->container = null;
        $this->date = null;

        $this->selectAll();
    }

    protected function selectAll(): void
    {
        $this->selected = $this->getPickups()->modelKeys();
    }

    /**
     * @param  array<int, string>  $ids
     * @return Collection<int, WastePickup>
     */
    protected function openPickups(array $ids): Collection
    {
        return WastePickup::query()
            ->whereKey($ids)
            ->recent(31)
            ->get();
    }
}
