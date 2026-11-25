<?php

namespace App\Filament\Waste\Pages;

use App\Models\Property;
use App\Models\WasteContainer;
use App\Services\Waste\WasteRightsizing;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

class RightsizingPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?int $navigationSort = 56;

    protected static ?string $slug = 'behaelteroptimierung';

    protected string $view = 'filament.waste.pages.rightsizing';

    #[Url]
    public ?string $property = null;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $filters = [];

    /**
     * @var Collection<string, array<string, mixed>>|null
     */
    private ?Collection $rows = null;

    public static function getNavigationGroup(): ?string
    {
        return __('waste.navigation.analysis');
    }

    public static function getNavigationLabel(): string
    {
        return __('waste.pages.rightsizing');
    }

    public function getTitle(): string
    {
        return __('waste.pages.rightsizing');
    }

    public function getSubheading(): ?string
    {
        return __('waste.rightsizing.subheading', ['days' => WasteRightsizing::DAYS]);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', WasteContainer::class) ?? false;
    }

    public function mount(): void
    {
        $this->filtersForm->fill(['property' => $this->property]);
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
                            ->afterStateUpdated(function (?string $state): void {
                                $this->property = $state;
                                $this->rows = null;
                                $this->resetTable();
                            }),
                    ]),
            ])
            ->statePath('filters');
    }

    /**
     * @return Collection<string, array<string, mixed>>
     */
    public function getRows(): Collection
    {
        return $this->rows ??= app(WasteRightsizing::class)->recommendations($this->property);
    }

    public function getYearlySaving(): float
    {
        return (float) $this->getRows()->sum('saving');
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (?array $filters): array => $this->getRows()
                ->when(filled($filters['recommendation']['value'] ?? null), fn (Collection $rows): Collection => $rows->where('recommendation', $filters['recommendation']['value']))
                ->sortBy(fn (array $row): int => ['increase' => 0, 'reduce' => 1, 'ok' => 2, 'no_data' => 3][$row['recommendation']])
                ->all())
            ->paginated(false)
            ->emptyStateHeading(__('waste.rightsizing.empty'))
            ->columns([
                TextColumn::make('number')
                    ->label(__('waste.models.container.singular'))
                    ->state(fn (array $record): string => $record['container']->number)
                    ->description(fn (array $record): ?string => $record['container']->property?->property_number)
                    ->weight('medium'),
                TextColumn::make('fraction')
                    ->label(__('waste.models.fraction.singular'))
                    ->state(fn (array $record): ?string => $record['container']->fraction?->name)
                    ->badge()
                    ->color(fn (array $record): array => Color::hex($record['container']->fraction?->displayColor() ?? '#94a3b8')),
                TextColumn::make('volume')
                    ->label(__('waste.fields.volume'))
                    ->state(fn (array $record): string => number_format($record['container']->volume_l, 0, ',', '.').' l')
                    ->description(fn (array $record): ?string => $record['container']->schedules->first()?->ruleLabel()),
                TextColumn::make('pickups')
                    ->label(__('waste.invoice.pickups'))
                    ->numeric()
                    ->description(fn (array $record): ?string => $record['extra'] > 0 ? trans_choice('waste.rightsizing.extra', $record['extra'], ['count' => $record['extra']]) : null),
                TextColumn::make('average_fill')
                    ->label(__('waste.rightsizing.average_fill'))
                    ->suffix(' %')
                    ->placeholder('—')
                    ->description(fn (array $record): string => trans_choice('waste.rightsizing.samples', $record['samples'], ['count' => $record['samples']])),
                TextColumn::make('recommendation')
                    ->label(__('waste.rightsizing.recommendation'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('waste.rightsizing.status.'.$state))
                    ->color(fn (string $state): string => match ($state) {
                        'increase' => 'danger',
                        'reduce' => 'warning',
                        'ok' => 'success',
                        default => 'gray',
                    })
                    ->description(fn (array $record): ?string => $record['suggested_volume'] !== null
                        ? __('waste.rightsizing.suggested_volume', ['volume' => number_format($record['suggested_volume'], 0, ',', '.')])
                        : null),
                TextColumn::make('saving')
                    ->label(__('waste.rightsizing.saving'))
                    ->money('EUR', locale: 'de')
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('recommendation')
                    ->label(__('waste.rightsizing.recommendation'))
                    ->options(collect(['increase', 'reduce', 'ok', 'no_data'])->mapWithKeys(fn (string $key): array => [$key => __('waste.rightsizing.status.'.$key)])->all()),
            ]);
    }
}
