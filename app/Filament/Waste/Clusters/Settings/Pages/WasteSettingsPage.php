<?php

namespace App\Filament\Waste\Clusters\Settings\Pages;

use App\Filament\Waste\Clusters\Settings\SettingsCluster;
use App\Models\WasteSettings;
use App\Services\Waste\CountryRules;
use App\Services\Waste\WasteTemplateImporter;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class WasteSettingsPage extends Page
{
    protected static ?string $cluster = SettingsCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'allgemein';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('waste.pages.general_settings');
    }

    public function getTitle(): string
    {
        return __('waste.pages.general_settings');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('update', WasteSettings::class) ?? false;
    }

    public function mount(): void
    {
        $settings = WasteSettings::forOrganization(Filament::getTenant()?->getKey());

        $this->form->fill($settings->only([
            'cutoff_days', 'cutoff_time', 'horizon_weeks',
            'default_fill_pct', 'staging_reminder_time', 'request_reminder_days',
        ]));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('waste.settings.confirmation'))
                    ->description(__('waste.settings.confirmation_help'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('cutoff_days')
                            ->label(__('waste.fields.cutoff_days'))
                            ->helperText(__('waste.help.cutoff_days'))
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(14)
                            ->required(),
                        TimePicker::make('cutoff_time')
                            ->label(__('waste.fields.cutoff_time'))
                            ->seconds(false)
                            ->required(),
                        TextInput::make('default_fill_pct')
                            ->label(__('waste.fields.default_fill_level'))
                            ->helperText(__('waste.help.default_fill_level'))
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->required(),
                    ]),
                Section::make(__('waste.settings.planning'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('horizon_weeks')
                            ->label(__('waste.fields.horizon_weeks'))
                            ->helperText(__('waste.help.horizon_weeks'))
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(52)
                            ->required(),
                        TimePicker::make('staging_reminder_time')
                            ->label(__('waste.fields.staging_reminder_time'))
                            ->helperText(__('waste.help.staging_reminder_time'))
                            ->seconds(false)
                            ->required(),
                        TextInput::make('request_reminder_days')
                            ->label(__('waste.fields.request_reminder_days'))
                            ->helperText(__('waste.help.request_reminder_days'))
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(30)
                            ->required(),
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
                        ->label(__('waste.actions.save'))
                        ->submit('save'),
                ])]),
            Section::make(fn (): string => __('waste.settings.country_rules', ['country' => $this->countryLabel()]))
                ->description(__('waste.settings.country_rules_help'))
                ->collapsible()
                ->schema([
                    View::make('filament.waste.settings.country-rules')
                        ->viewData(fn (): array => [
                            'rows' => app(CountryRules::class)->rows($this->settings()),
                            'country' => $this->settings()->country,
                        ]),
                ]),
        ]);
    }

    private function settings(): WasteSettings
    {
        return WasteSettings::forOrganization(Filament::getTenant()?->getKey());
    }

    private function countryLabel(): string
    {
        $country = $this->settings()->country;

        return filled($country) ? __('waste.templates.'.$country) : __('waste.templates.import_none');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $settings = WasteSettings::forOrganization(Filament::getTenant()?->getKey());

        $settings->fill([
            ...$data,
            'cutoff_time' => substr((string) $data['cutoff_time'], 0, 5),
            'staging_reminder_time' => substr((string) $data['staging_reminder_time'], 0, 5),
        ]);
        $settings->save();

        Notification::make()->success()->title(__('waste.notifications.settings_saved'))->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importTemplate')
                ->label(__('waste.templates.import_action'))
                ->icon('heroicon-o-globe-europe-africa')
                ->color('gray')
                ->modalDescription(__('waste.templates.import_help'))
                ->schema([
                    Select::make('template')
                        ->label(__('waste.templates.import_label'))
                        ->options(fn (): array => WasteTemplateImporter::templateOptions())
                        ->required()
                        ->native(false),
                ])
                ->action(function (array $data): void {
                    $count = app(WasteTemplateImporter::class)->importTemplate(Filament::getTenant(), $data['template']);

                    Notification::make()
                        ->success()
                        ->title(trans_choice('waste.templates.imported', $count, ['count' => $count]))
                        ->send();
                }),
        ];
    }
}
