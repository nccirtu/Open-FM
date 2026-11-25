<?php

namespace App\Filament\Waste\Pages;

use App\Models\Property;
use App\Models\WasteCollection;
use App\Services\Waste\WasteEsgReport;
use App\Support\Csv;
use App\Support\WasteFilters;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Url;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WasteEsgReportPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartBar;

    protected static ?int $navigationSort = 54;

    protected static ?string $slug = 'esg-berichte';

    protected string $view = 'filament.waste.pages.esg-report';

    #[Url]
    public ?int $year = null;

    #[Url]
    public ?string $property = null;

    #[Url]
    public string $kind = WasteFilters::KIND_ALL;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $filters = [];

    public static function getNavigationGroup(): ?string
    {
        return __('waste.navigation.analysis');
    }

    public static function getNavigationLabel(): string
    {
        return __('waste.pages.esg_report');
    }

    public function getTitle(): string
    {
        return __('waste.pages.esg_report');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', WasteCollection::class) ?? false;
    }

    public function mount(): void
    {
        $this->year ??= now()->year - (now()->month <= 3 ? 1 : 0);
        $this->filtersForm->fill(['year' => $this->year, 'property' => $this->property, 'kind' => $this->kind]);
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 1, 'sm' => 3])
                    ->schema([
                        Select::make('year')
                            ->label(__('waste.fields.year'))
                            ->options(fn (): array => collect(range(now()->year, now()->year - 5))->mapWithKeys(fn (int $year): array => [$year => (string) $year])->all())
                            ->selectablePlaceholder(false)
                            ->live()
                            ->afterStateUpdated(fn (?string $state) => $this->year = (int) $state),
                        Select::make('property')
                            ->label(__('property.model'))
                            ->options(fn (): array => Property::query()->orderBy('property_number')->pluck('property_number', 'id')->all())
                            ->placeholder(__('waste.filters.all_properties'))
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn (?string $state) => $this->property = $state),
                        Select::make('kind')
                            ->label(__('waste.filters.kind'))
                            ->options(WasteFilters::kindOptions())
                            ->selectablePlaceholder(false)
                            ->live()
                            ->afterStateUpdated(fn (?string $state) => $this->kind = $state ?? WasteFilters::KIND_ALL),
                    ]),
            ])
            ->statePath('filters');
    }

    /**
     * @return array<string, mixed>
     */
    public function getReport(): array
    {
        return app(WasteEsgReport::class)->build((int) $this->year, $this->property, kind: $this->kind);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label(__('waste.actions.export_csv'))
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(function (WasteEsgReport $esg): StreamedResponse {
                    $property = filled($this->property) ? Property::query()->find($this->property)?->property_number : null;

                    return Csv::download(
                        'abfall-esg-'.$this->year.($property ? '-'.str($property)->slug() : '').'.csv',
                        [__('waste.esg.framework'), __('waste.esg.metric'), __('waste.esg.unit'), __('waste.esg.value')],
                        $esg->csvRows($this->getReport()),
                    );
                }),
        ];
    }
}
