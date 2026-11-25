<?php

namespace App\Filament\Waste\Pages;

use App\Models\Property;
use App\Models\WasteContainer;
use App\Services\Waste\WasteInvoiceCheck;
use App\Support\Csv;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceCheckPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static ?int $navigationSort = 52;

    protected static ?string $slug = 'rechnungspruefung';

    protected string $view = 'filament.waste.pages.invoice-check';

    #[Url]
    public ?string $month = null;

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
        return __('waste.pages.invoice_check');
    }

    public function getTitle(): string
    {
        return __('waste.pages.invoice_check');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', WasteContainer::class) ?? false;
    }

    public function mount(): void
    {
        $this->month ??= CarbonImmutable::today()->subMonthNoOverflow()->format('Y-m');
        $this->filtersForm->fill(['month' => $this->month, 'property' => $this->property]);
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 1, 'sm' => 2])
                    ->schema([
                        Select::make('month')
                            ->label(__('waste.fields.month'))
                            ->options(fn (): array => collect(range(0, 23))
                                ->mapWithKeys(fn (int $offset): array => [
                                    CarbonImmutable::today()->startOfMonth()->subMonths($offset)->format('Y-m') => CarbonImmutable::today()->startOfMonth()->subMonths($offset)->locale(app()->getLocale())->isoFormat('MMMM YYYY'),
                                ])
                                ->all())
                            ->selectablePlaceholder(false)
                            ->live()
                            ->afterStateUpdated(fn (?string $state) => $this->applyFilters(month: $state)),
                        Select::make('property')
                            ->label(__('property.model'))
                            ->options(fn (): array => Property::query()->whereHas('wasteContainers')->orderBy('property_number')->pluck('property_number', 'id')->all())
                            ->placeholder(__('waste.filters.all_properties'))
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn (?string $state) => $this->applyFilters(property: $state ?? '')),
                    ]),
            ])
            ->statePath('filters');
    }

    public function applyFilters(?string $month = null, ?string $property = null): void
    {
        $this->month = $month ?? $this->month;
        $this->property = $property !== null ? ($property === '' ? null : $property) : $this->property;
        $this->rows = null;
        $this->resetTable();
    }

    /**
     * @return Collection<string, array<string, mixed>>
     */
    public function getRows(): Collection
    {
        return $this->rows ??= app(WasteInvoiceCheck::class)->rows($this->monthDate(), $this->property);
    }

    /**
     * @return array{expected: float, actual: float, difference: float, deviations: int}
     */
    public function getTotals(): array
    {
        $rows = $this->getRows();
        $priced = $rows->whereNotNull('expected');

        return [
            'expected' => (float) $priced->sum('expected'),
            'actual' => (float) $priced->sum('actual'),
            'difference' => (float) $priced->sum('difference'),
            'deviations' => $rows->whereIn('status', ['over', 'under', 'missing'])->count(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => $this->getRows()->all())
            ->paginated(false)
            ->emptyStateHeading(__('waste.invoice.empty'))
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
                TextColumn::make('company')
                    ->label(__('waste.fields.company'))
                    ->state(fn (array $record): ?string => $record['container']->company?->name)
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('pickups')
                    ->label(__('waste.invoice.pickups'))
                    ->numeric(),
                TextColumn::make('tonnes')
                    ->label(__('waste.fields.mass_t'))
                    ->numeric(decimalPlaces: 3, locale: 'de'),
                TextColumn::make('expected')
                    ->label(__('waste.invoice.expected'))
                    ->money('EUR', locale: 'de')
                    ->placeholder('—'),
                TextColumn::make('actual')
                    ->label(__('waste.invoice.actual'))
                    ->money('EUR', locale: 'de'),
                TextColumn::make('difference')
                    ->label(__('waste.invoice.difference'))
                    ->money('EUR', locale: 'de')
                    ->color(fn (?float $state): ?string => $state === null ? null : ($state > 0 ? 'danger' : ($state < 0 ? 'warning' : null)))
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label(__('waste.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('waste.invoice.status.'.$state))
                    ->color(fn (string $state): string => match ($state) {
                        'ok' => 'success',
                        'over' => 'danger',
                        'under', 'missing' => 'warning',
                        default => 'gray',
                    }),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label(__('waste.actions.export_csv'))
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(fn (): StreamedResponse => Csv::download(
                    'rechnungspruefung-'.$this->monthDate()->format('Y-m').'.csv',
                    [
                        __('waste.models.container.singular'),
                        __('property.model'),
                        __('waste.models.fraction.singular'),
                        __('waste.fields.company'),
                        __('waste.invoice.pickups'),
                        __('waste.fields.mass_t'),
                        __('waste.invoice.expected'),
                        __('waste.invoice.actual'),
                        __('waste.invoice.difference'),
                        __('waste.fields.status'),
                    ],
                    $this->getRows()->map(fn (array $row): array => [
                        $row['container']->number,
                        $row['container']->property?->property_number,
                        $row['container']->fraction?->name,
                        $row['container']->company?->name,
                        $row['pickups'],
                        $row['tonnes'],
                        $row['expected'],
                        $row['actual'],
                        $row['difference'],
                        __('waste.invoice.status.'.$row['status']),
                    ])->values(),
                )),
        ];
    }

    private function monthDate(): CarbonImmutable
    {
        return rescue(fn (): CarbonImmutable => CarbonImmutable::createFromFormat('!Y-m', (string) $this->month), CarbonImmutable::today()->startOfMonth(), report: false);
    }
}
