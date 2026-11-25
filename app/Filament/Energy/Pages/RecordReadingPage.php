<?php

namespace App\Filament\Energy\Pages;

use App\Enums\MeterReadingSource;
use App\Enums\MeterReadingStatus;
use App\Filament\Energy\Actions\RecordReadingAction;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Services\Energy\MeterReadingRecorder;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Livewire\Attributes\Url;

class RecordReadingPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?int $navigationSort = 15;

    protected static ?string $slug = 'ablesen';

    #[Url]
    public ?string $meter = null;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('energy.pages.record_readings');
    }

    public function getTitle(): string
    {
        return __('energy.pages.record_readings');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('create', MeterReading::class) ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'meter_id' => $this->meter,
            'read_at' => now()->startOfMinute(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Select::make('meter_id')
                            ->label(__('energy.meter.model'))
                            ->options(fn (): array => self::meterOptions())
                            ->searchable()
                            ->required()
                            ->live(),
                        ...RecordReadingAction::schema(null, fn (): ?Meter => $this->selectedMeter()),
                    ]),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([Actions::make([
                    Action::make('save')
                        ->label(__('energy.actions.save_reading'))
                        ->submit('save'),
                ])]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $meter = Meter::query()->findOrFail($data['meter_id']);

        $reading = app(MeterReadingRecorder::class)->record(
            $meter,
            CarbonImmutable::parse($data['read_at']),
            (float) $data['value'],
            MeterReadingSource::Manual,
            ['photo' => $data['photo'] ?? null, 'note' => $data['note'] ?? null],
        );

        $reading->status === MeterReadingStatus::Suspicious
            ? Notification::make()->warning()->title(__('energy.notifications.saved_suspicious'))->body($meter->name)->send()
            : Notification::make()->success()->title(__('energy.notifications.saved'))->body($meter->name)->send();

        $this->meter = null;
        $this->form->fill(['read_at' => $data['read_at']]);
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function meterOptions(): array
    {
        return Meter::query()
            ->with('property')
            ->where('kind', '!=', 'virtual')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Meter $meter): string => (string) $meter->property?->property_number)
            ->sortKeys()
            ->map(fn ($meters): array => $meters->mapWithKeys(fn (Meter $meter): array => [
                $meter->getKey() => $meter->name.($meter->number ? ' ('.$meter->number.')' : ''),
            ])->all())
            ->all();
    }

    private function selectedMeter(): ?Meter
    {
        $id = $this->data['meter_id'] ?? null;

        return filled($id) ? Meter::query()->with('latestReading')->find($id) : null;
    }
}
