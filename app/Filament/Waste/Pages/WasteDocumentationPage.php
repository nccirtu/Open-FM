<?php

namespace App\Filament\Waste\Pages;

use App\Enums\WasteEvidenceStatus;
use App\Models\Company;
use App\Models\Property;
use App\Models\WasteEvidence;
use App\Services\Waste\WasteDocumentation;
use App\Support\Uploads;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Url;

class WasteDocumentationPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?int $navigationSort = 60;

    protected static ?string $slug = 'dokumentation';

    protected string $view = 'filament.waste.pages.documentation';

    #[Url]
    public ?string $property = null;

    #[Url]
    public ?int $year = null;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $filters = [];

    public static function getNavigationGroup(): ?string
    {
        return __('waste.navigation.compliance');
    }

    public static function getNavigationLabel(): string
    {
        return __('waste.pages.documentation');
    }

    public function getTitle(): string
    {
        return __('waste.pages.documentation');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', WasteEvidence::class) ?? false;
    }

    public function mount(): void
    {
        $this->year ??= now()->year;
        $this->property ??= Property::query()->whereHas('wasteContainers')->orderBy('property_number')->value('id');

        $this->filtersForm->fill(['property' => $this->property, 'year' => $this->year]);
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
                            ->searchable()
                            ->selectablePlaceholder(false)
                            ->live()
                            ->afterStateUpdated(fn (?string $state) => $this->property = $state),
                        Select::make('year')
                            ->label(__('waste.fields.year'))
                            ->options(fn (): array => collect(range(now()->year + 1, now()->year - 5))->mapWithKeys(fn (int $year): array => [$year => (string) $year])->all())
                            ->selectablePlaceholder(false)
                            ->live()
                            ->afterStateUpdated(fn (?string $state) => $this->year = (int) $state),
                    ]),
            ])
            ->statePath('filters');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDocumentation(): ?array
    {
        $property = filled($this->property) ? Property::query()->find($this->property) : null;

        return $property !== null ? app(WasteDocumentation::class)->for($property, (int) $this->year) : null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label(__('waste.actions.export_pdf'))
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('gray')
                ->visible(fn (): bool => filled($this->property))
                ->url(fn (): string => route('waste.documentation.pdf', ['property' => $this->property, 'year' => $this->year]), shouldOpenInNewTab: true),
        ];
    }

    public function editEvidenceAction(): Action
    {
        return Action::make('editEvidence')
            ->label(__('waste.actions.edit_evidence'))
            ->icon(Heroicon::OutlinedPencilSquare)
            ->iconButton()
            ->authorize(fn (): bool => auth()->user()?->can('create', WasteEvidence::class) ?? false)
            ->modalHeading(fn (array $arguments): string => WasteEvidence::labelFor((string) ($arguments['type'] ?? '')))
            ->modalWidth('2xl')
            ->fillForm(function (array $arguments): array {
                $evidence = $this->evidence((string) $arguments['type']);

                return [
                    'status' => $evidence?->status ?? WasteEvidenceStatus::Available,
                    'valid_until' => $evidence?->valid_until,
                    'company_id' => $evidence?->company_id,
                    'attachments' => $evidence?->attachments ?? [],
                    'note' => $evidence?->note,
                ];
            })
            ->schema([
                ToggleButtons::make('status')
                    ->label(__('waste.fields.status'))
                    ->options(WasteEvidenceStatus::class)
                    ->inline()
                    ->required(),
                Grid::make(2)->schema([
                    DatePicker::make('valid_until')
                        ->label(__('waste.fields.valid_until'))
                        ->native(false),
                    Select::make('company_id')
                        ->label(__('waste.fields.company'))
                        ->options(fn (): array => Company::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable(),
                ]),
                FileUpload::make('attachments')
                    ->label(__('waste.fields.attachments'))
                    ->multiple()
                    ->visibility('private')
                    ->directory('waste-evidence')
                    ->acceptedFileTypes(Uploads::documents())
                    ->openable()
                    ->downloadable(),
                Textarea::make('note')
                    ->label(__('waste.fields.note'))
                    ->rows(3),
            ])
            ->action(function (array $arguments, array $data): void {
                $evidence = $this->evidence((string) $arguments['type']) ?? new WasteEvidence([
                    'property_id' => $this->property,
                    'year' => $this->year,
                    'type' => (string) $arguments['type'],
                ]);

                $evidence->fill([
                    'status' => $data['status'],
                    'valid_until' => $data['valid_until'] ?? null,
                    'company_id' => $data['company_id'] ?? null,
                    'attachments' => array_values($data['attachments'] ?? []),
                    'note' => $data['note'] ?? null,
                ])->save();

                Notification::make()->success()->title(__('waste.notifications.evidence_saved'))->send();
            });
    }

    private function evidence(string $type): ?WasteEvidence
    {
        return WasteEvidence::query()
            ->where('property_id', $this->property)
            ->where('year', $this->year)
            ->where('type', $type)
            ->first();
    }
}
