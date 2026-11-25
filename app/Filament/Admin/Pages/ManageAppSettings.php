<?php

namespace App\Filament\Admin\Pages;

use App\Services\AppSettings;
use App\Support\Uploads;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageAppSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return __('app.admin_groups.settings');
    }

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('settings.title');
    }

    public function getTitle(): string
    {
        return __('settings.title');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public function mount(): void
    {
        $this->form->fill(AppSettings::all());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('settings.sections.general'))
                    ->description(__('settings.sections.general_help'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('app_name')->label(__('settings.fields.app_name'))->required()->maxLength(100),
                    ]),
                Section::make(__('settings.sections.branding'))
                    ->description(__('settings.sections.branding_help'))
                    ->columns(2)
                    ->schema([
                        FileUpload::make('logo_light')
                            ->label(__('settings.fields.logo_light'))
                            ->helperText(__('settings.fields.logo_light_help'))
                            ->disk(AppSettings::DISK)
                            ->directory(AppSettings::DIRECTORY)
                            ->image()
                            ->acceptedFileTypes(Uploads::images())
                            ->maxSize(2048),
                        FileUpload::make('logo_dark')
                            ->label(__('settings.fields.logo_dark'))
                            ->helperText(__('settings.fields.logo_dark_help'))
                            ->disk(AppSettings::DISK)
                            ->directory(AppSettings::DIRECTORY)
                            ->image()
                            ->acceptedFileTypes(Uploads::images())
                            ->maxSize(2048),
                        FileUpload::make('favicon')
                            ->label(__('settings.fields.favicon'))
                            ->disk(AppSettings::DISK)
                            ->directory(AppSettings::DIRECTORY)
                            ->acceptedFileTypes(Uploads::icons())
                            ->maxSize(512),
                        TextInput::make('logo_height')
                            ->label(__('settings.fields.logo_height'))
                            ->helperText(__('settings.fields.logo_height_help'))
                            ->default(AppSettings::DEFAULTS['logo_height'])
                            ->regex('/^\d+(\.\d+)?(px|rem|em)$/')
                            ->maxLength(10),
                    ]),
                Section::make(__('settings.sections.mail'))
                    ->description(__('settings.sections.mail_help'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('mail_from_name')->label(__('settings.fields.mail_from_name'))->maxLength(100),
                        TextInput::make('mail_from_address')->label(__('settings.fields.mail_from_address'))->email()->maxLength(255),
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
                ->footer([Actions::make($this->getFormActions())]),
        ]);
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }

    public function save(): void
    {
        AppSettings::set($this->form->getState());
        AppSettings::apply();

        Notification::make()->title(__('settings.saved'))->success()->send();
    }
}
