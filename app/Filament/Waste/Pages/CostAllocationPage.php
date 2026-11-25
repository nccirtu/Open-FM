<?php

namespace App\Filament\Waste\Pages;

use App\Models\Property;
use App\Models\WasteContainer;
use App\Services\Waste\WasteCostAllocation;
use App\Support\Csv;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Attributes\Url;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CostAllocationPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static ?int $navigationSort = 58;

    protected static ?string $slug = 'kostenumlage';

    protected string $view = 'filament.waste.pages.cost-allocation';

    #[Url]
    public ?string $property = null;

    #[Url]
    public ?int $year = null;

    #[Url]
    public string $method = 'area';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $filters = [];

    /**
     * @var array<string, mixed>|null
     */
    private ?array $allocation = null;

    public static function getNavigationGroup(): ?string
    {
        return __('waste.navigation.analysis');
    }

    public static function getNavigationLabel(): string
    {
        return __('waste.pages.cost_allocation');
    }

    public function getTitle(): string
    {
        return __('waste.pages.cost_allocation');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', WasteContainer::class) ?? false;
    }

    public function mount(): void
    {
        $this->year ??= now()->year - 1;
        $this->property ??= Property::query()->whereHas('wasteContainers')->orderBy('property_number')->value('id');
        $this->filtersForm->fill(['property' => $this->property, 'year' => $this->year, 'method' => $this->method]);
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 1, 'sm' => 3])
                    ->schema([
                        Select::make('property')
                            ->label(__('property.model'))
                            ->options(fn (): array => Property::query()->whereHas('wasteContainers')->orderBy('property_number')->pluck('property_number', 'id')->all())
                            ->searchable()
                            ->selectablePlaceholder(false)
                            ->live()
                            ->afterStateUpdated(fn (?string $state) => $this->applyFilter('property', $state)),
                        Select::make('year')
                            ->label(__('waste.fields.year'))
                            ->options(fn (): array => collect(range(now()->year, now()->year - 5))->mapWithKeys(fn (int $year): array => [$year => (string) $year])->all())
                            ->selectablePlaceholder(false)
                            ->live()
                            ->afterStateUpdated(fn (?string $state) => $this->applyFilter('year', (int) $state)),
                        ToggleButtons::make('method')
                            ->label(__('waste.allocation.method'))
                            ->options(collect(WasteCostAllocation::METHODS)->mapWithKeys(fn (string $method): array => [$method => __('waste.allocation.methods.'.$method)])->all())
                            ->inline()
                            ->live()
                            ->afterStateUpdated(fn (?string $state) => $this->applyFilter('method', $state ?? 'area')),
                    ]),
            ])
            ->statePath('filters');
    }

    public function applyFilter(string $name, mixed $value): void
    {
        abort_unless(in_array($name, ['property', 'year', 'method'], true), 403);

        $this->{$name} = $value;
        $this->allocation = null;
        $this->resetTable();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getAllocation(): ?array
    {
        $property = filled($this->property) ? Property::query()->find($this->property) : null;

        if ($property === null) {
            return null;
        }

        $from = CarbonImmutable::create((int) $this->year)->startOfYear();

        return $this->allocation ??= app(WasteCostAllocation::class)->allocate($property, $from, $from->endOfYear(), $this->method);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => ($this->getAllocation()['rows'] ?? collect())->all())
            ->paginated(false)
            ->emptyStateHeading(__('waste.allocation.empty'))
            ->emptyStateDescription(__('waste.allocation.empty_help'))
            ->columns([
                TextColumn::make('unit')
                    ->label(__('waste.allocation.unit'))
                    ->state(fn (array $record): string => $record['unit']->name)
                    ->weight('medium'),
                TextColumn::make('area')
                    ->label(__('waste.allocation.area'))
                    ->numeric(decimalPlaces: 2, locale: 'de')
                    ->suffix(' m²')
                    ->placeholder('—'),
                TextColumn::make('share')
                    ->label(__('waste.allocation.share'))
                    ->numeric(decimalPlaces: 2, locale: 'de')
                    ->suffix(' %'),
                TextColumn::make('amount')
                    ->label(__('waste.allocation.amount'))
                    ->money('EUR', locale: 'de'),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label(__('waste.actions.export_csv'))
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->disabled(fn (): bool => $this->getAllocation() === null)
                ->action(fn (): StreamedResponse => Csv::download(
                    'abfall-umlage-'.str(Property::query()->find($this->property)?->property_number)->slug().'-'.$this->year.'.csv',
                    [__('waste.allocation.unit'), __('waste.allocation.area'), __('waste.allocation.share'), __('waste.allocation.amount')],
                    $this->getAllocation()['rows']->map(fn (array $row): array => [$row['unit']->name, $row['area'] ?? '', round($row['share'], 2), $row['amount']])->values(),
                )),
        ];
    }
}
