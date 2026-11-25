<?php

namespace App\Filament\Waste\Resources\WasteSchedules;

use App\Enums\WasteConfirmationChannel;
use App\Enums\WasteIntervalUnit;
use App\Enums\WastePickupStatus;
use App\Enums\WasteScheduleSource;
use App\Filament\Waste\Pages\ConfirmPickupsPage;
use App\Filament\Waste\Resources\WasteSchedules\Pages\ManageWasteSchedules;
use App\Models\WasteContainer;
use App\Models\WasteSchedule;
use App\Services\Waste\IcsCalendarParser;
use App\Services\Waste\PickupGenerator;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class WasteScheduleResource extends Resource
{
    protected static ?string $model = WasteSchedule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static ?int $navigationSort = 25;

    public static function getNavigationGroup(): ?string
    {
        return __('waste.navigation.inventory');
    }

    public static function getModelLabel(): string
    {
        return __('waste.models.schedule.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('waste.models.schedule.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('property_id')
                    ->label(__('property.model'))
                    ->relationship('property', 'property_number')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Set $set): void {
                        $set('structure_node_id', null);
                        $set('containers', []);
                    }),
                Select::make('structure_node_id')
                    ->label(__('waste.fields.collection_point'))
                    ->helperText(__('waste.help.collection_point'))
                    ->options(fn (Get $get): array => ConfirmPickupsPage::collectionPointOptions($get('property_id')))
                    ->live()
                    ->afterStateUpdated(fn (Set $set, ?string $state) => filled($state)
                        ? $set('containers', WasteContainer::query()->active()->where('structure_node_id', $state)->pluck('id')->all())
                        : null),
                Select::make('containers')
                    ->label(__('waste.models.container.plural'))
                    ->relationship('containers', 'number', fn (Builder $query, Get $get) => $query->where('property_id', $get('property_id'))->orderBy('number'))
                    ->getOptionLabelFromRecordUsing(fn (WasteContainer $record): string => $record->label())
                    ->multiple()
                    ->preload()
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('name')
                    ->label(__('waste.fields.name'))
                    ->helperText(__('waste.help.schedule_name'))
                    ->maxLength(255)
                    ->columnSpanFull(),
                ToggleButtons::make('source')
                    ->label(__('waste.fields.schedule_source'))
                    ->options(WasteScheduleSource::class)
                    ->default(WasteScheduleSource::Rule->value)
                    ->inline()
                    ->live()
                    ->required()
                    ->columnSpanFull(),
                Grid::make(2)
                    ->columnSpanFull()
                    ->visible(fn (Get $get): bool => self::source($get) === WasteScheduleSource::Rule)
                    ->schema([
                        ToggleButtons::make('interval_unit')
                            ->label(__('waste.fields.rhythm'))
                            ->options(WasteIntervalUnit::class)
                            ->default(WasteIntervalUnit::Week->value)
                            ->inline()
                            ->live()
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('interval_value')
                            ->label(__('waste.fields.interval_value'))
                            ->helperText(fn (Get $get): string => __('waste.help.interval_value.'.(self::unit($get)?->value ?? 'week')))
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(52)
                            ->default(1)
                            ->required()
                            ->visible(fn (Get $get): bool => self::unit($get)?->hasInterval() ?? true),
                        Select::make('weekday')
                            ->label(__('waste.fields.weekday'))
                            ->options(fn (): array => collect(range(1, 7))->mapWithKeys(fn (int $day): array => [$day => __("waste.weekdays.{$day}")])->all())
                            ->required(fn (Get $get): bool => in_array(self::unit($get), [WasteIntervalUnit::Week, WasteIntervalUnit::Month], true))
                            ->visible(fn (Get $get): bool => in_array(self::unit($get), [WasteIntervalUnit::Week, WasteIntervalUnit::Month], true)),
                        Select::make('week_of_month')
                            ->label(__('waste.fields.week_of_month'))
                            ->options(fn (): array => collect([1, 2, 3, 4, -1])->mapWithKeys(fn (int $week): array => [$week => __("waste.week_of_month.{$week}")])->all())
                            ->default(1)
                            ->required(fn (Get $get): bool => self::unit($get) === WasteIntervalUnit::Month)
                            ->visible(fn (Get $get): bool => self::unit($get) === WasteIntervalUnit::Month),
                    ]),
                Grid::make(2)
                    ->columnSpanFull()
                    ->visible(fn (Get $get): bool => self::source($get) === WasteScheduleSource::Ics)
                    ->schema([
                        FileUpload::make('ics_file')
                            ->label(__('waste.fields.ics_file'))
                            ->helperText(fn (?WasteSchedule $record): string => __('waste.help.ics_file', ['count' => count($record?->ics_dates ?? [])]))
                            ->acceptedFileTypes(['text/calendar', 'text/plain', 'application/octet-stream'])
                            ->storeFiles(false)
                            ->dehydrated(),
                        TextInput::make('ics_filter')
                            ->label(__('waste.fields.ics_filter'))
                            ->helperText(__('waste.help.ics_filter'))
                            ->maxLength(255),
                    ]),
                DatePicker::make('starts_on')
                    ->label(__('waste.fields.starts_on'))
                    ->native(false)
                    ->default(now())
                    ->required(),
                DatePicker::make('ends_on')
                    ->label(__('waste.fields.ends_on'))
                    ->helperText(__('waste.help.ends_on'))
                    ->native(false)
                    ->afterOrEqual('starts_on'),
                TimePicker::make('window_from')
                    ->label(__('waste.fields.window_from'))
                    ->seconds(false)
                    ->visible(fn (Get $get): bool => self::source($get) !== WasteScheduleSource::OnDemand),
                TimePicker::make('window_to')
                    ->label(__('waste.fields.window_to'))
                    ->seconds(false)
                    ->after('window_from')
                    ->visible(fn (Get $get): bool => self::source($get) !== WasteScheduleSource::OnDemand),
                Toggle::make('needs_staging')
                    ->label(__('waste.fields.needs_staging'))
                    ->helperText(__('waste.help.needs_staging'))
                    ->visible(fn (Get $get): bool => self::source($get) !== WasteScheduleSource::OnDemand),
                CheckboxList::make('confirmation_channels')
                    ->label(__('waste.fields.confirmation_channels'))
                    ->helperText(__('waste.help.confirmation_channels'))
                    ->options(WasteConfirmationChannel::class)
                    ->default([WasteConfirmationChannel::Scan->value, WasteConfirmationChannel::Data->value])
                    ->columns(3)
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label(__('waste.fields.is_active'))
                    ->default(true),
                Textarea::make('notes')
                    ->label(__('waste.fields.notes'))
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['property', 'structureNode', 'containers.fraction'])
                ->withMin(['pickups as next_pickup_on' => fn (Builder $pickups) => $pickups
                    ->where('status', WastePickupStatus::Planned->value)
                    ->whereDate('due_on', '>=', CarbonImmutable::today()->toDateString())], 'due_on'))
            ->columns([
                TextColumn::make('name')
                    ->label(__('waste.models.container.plural'))
                    ->state(fn (WasteSchedule $record): string => filled($record->name) ? $record->name : $record->containers->map->number->implode(', '))
                    ->description(fn (WasteSchedule $record): ?string => $record->structureNode?->fullPath())
                    ->searchable(),
                TextColumn::make('property.property_number')
                    ->label(__('property.model'))
                    ->sortable(),
                TextColumn::make('rule')
                    ->label(__('waste.fields.rhythm'))
                    ->state(fn (WasteSchedule $record): string => $record->ruleLabel()),
                TextColumn::make('starts_on')
                    ->label(__('waste.fields.validity'))
                    ->formatStateUsing(fn (WasteSchedule $record): string => $record->starts_on->format('d.m.Y').' – '.($record->ends_on?->format('d.m.Y') ?? __('waste.fields.open_ended')))
                    ->sortable(),
                TextColumn::make('next_pickup_on')
                    ->label(__('waste.fields.next_pickup'))
                    ->date('D d.m.Y')
                    ->placeholder('—'),
                IconColumn::make('needs_staging')
                    ->label(__('waste.fields.needs_staging'))
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label(__('waste.fields.is_active'))
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('property_id')
                    ->label(__('property.model'))
                    ->relationship('property', 'property_number')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('source')
                    ->label(__('waste.fields.schedule_source'))
                    ->options(WasteScheduleSource::class),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->modalWidth(Width::ThreeExtraLarge)
                        ->mutateDataUsing(fn (array $data): array => self::prepareData($data))
                        ->after(fn (WasteSchedule $record) => self::generate($record)),
                    Action::make('generatePickups')
                        ->label(__('waste.actions.generate_pickups'))
                        ->icon('heroicon-o-arrow-path')
                        ->authorize(fn (WasteSchedule $record): bool => auth()->user()?->can('update', $record) ?? false)
                        ->action(fn (WasteSchedule $record) => self::generate($record)),
                    DeleteAction::make()
                        ->before(fn (WasteSchedule $record) => $record->pickups()
                            ->where('status', WastePickupStatus::Planned->value)
                            ->whereDate('due_on', '>=', CarbonImmutable::today()->toDateString())
                            ->delete()),
                ]),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function prepareData(array $data): array
    {
        $file = $data['ics_file'] ?? null;
        unset($data['ics_file']);

        $source = $data['source'] ?? null;
        $source = $source instanceof WasteScheduleSource ? $source : WasteScheduleSource::tryFrom((string) $source);

        if ($source !== WasteScheduleSource::Ics) {
            return $data;
        }

        $file = is_array($file) ? collect($file)->first() : $file;

        if ($file instanceof TemporaryUploadedFile) {
            $dates = app(IcsCalendarParser::class)->dates((string) $file->get(), $data['ics_filter'] ?? null);

            if ($dates === []) {
                throw ValidationException::withMessages(['ics_file' => __('waste.errors.ics_empty')]);
            }

            $data['ics_dates'] = $dates;
        }

        return $data;
    }

    public static function generate(WasteSchedule $schedule): void
    {
        $created = app(PickupGenerator::class)->generate($schedule);

        Notification::make()
            ->success()
            ->title(trans_choice('waste.notifications.pickups_generated', $created, ['count' => $created]))
            ->send();
    }

    private static function source(Get $get): ?WasteScheduleSource
    {
        $state = $get('source');

        return $state instanceof WasteScheduleSource ? $state : WasteScheduleSource::tryFrom((string) $state);
    }

    private static function unit(Get $get): ?WasteIntervalUnit
    {
        $state = $get('interval_unit');

        return $state instanceof WasteIntervalUnit ? $state : WasteIntervalUnit::tryFrom((string) $state);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageWasteSchedules::route('/'),
        ];
    }
}
