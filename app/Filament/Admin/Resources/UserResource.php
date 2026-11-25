<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages\EditUser;
use App\Filament\Admin\Resources\UserResource\Pages\ListUsers;
use App\Models\Property;
use App\Models\User;
use App\Services\Gdpr\PersonalDataExporter;
use App\Services\Gdpr\UserAnonymizer;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\Response;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = 'Benutzer';

    protected static ?string $modelLabel = 'Benutzer';

    protected static ?string $pluralModelLabel = 'Benutzer';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): ?string
    {
        return __('app.admin_groups.management');
    }

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        $actor = auth()->user();

        return $actor instanceof User && $record instanceof User && $record->isManageableBy($actor);
    }

    public static function getEloquentQuery(): Builder
    {
        $actor = auth()->user();

        if (! $actor instanceof User) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        $organizationIds = $actor->administeredOrganizationIds();

        return parent::getEloquentQuery()
            ->when($organizationIds !== null, fn (Builder $query) => $query
                ->where('users.is_super_admin', false)
                ->whereHas('organizations', fn (Builder $organizations) => $organizations->whereIn('organizations.id', $organizationIds)));
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label(__('app.users.name'))
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->label(__('app.users.email'))
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            Select::make('organizations')
                ->label(__('app.users.organizations'))
                ->relationship('organizations', 'name')
                ->multiple()
                ->preload()
                ->required()
                ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false),
            Select::make('properties')
                ->label(__('tickets.reporter_stores'))
                ->helperText(__('tickets.reporter_stores_help'))
                ->relationship('properties', 'property_number')
                ->getOptionLabelFromRecordUsing(fn (Property $property): string => trim($property->property_number.' '.($property->city ?? '')))
                ->multiple()
                ->searchable()
                ->preload(),
            Select::make('company_id')
                ->label(__('tickets.fields.company'))
                ->helperText(__('tickets.service_partner_help'))
                ->relationship('company', 'name')
                ->searchable()
                ->preload()
                ->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('app.users.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('app.users.email'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('organizations.name')
                    ->label(__('app.users.organizations'))
                    ->badge(),
                IconColumn::make('email_verified_at')
                    ->label(__('app.users.verified'))
                    ->boolean()
                    ->getStateUsing(fn (User $record): bool => $record->email_verified_at !== null),
                TextColumn::make('created_at')
                    ->label(__('app.users.created'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    self::gdprExportAction(),
                    self::anonymizeAction(),
                ]),
            ]);
    }

    private static function gdprExportAction(): Action
    {
        return Action::make('gdprExport')
            ->label(__('gdpr.actions.export'))
            ->icon(Heroicon::OutlinedDocumentArrowDown)
            ->color('gray')
            ->visible(fn (User $record): bool => self::canEdit($record))
            ->action(fn (User $record): Response => Pdf::loadView('pdfs.gdpr-export', [
                'user' => $record,
                'groups' => app(PersonalDataExporter::class)->export($record),
            ])->download('dsgvo-auskunft-'.$record->getKey().'.pdf'));
    }

    private static function anonymizeAction(): Action
    {
        return Action::make('anonymize')
            ->label(__('gdpr.actions.anonymize'))
            ->icon(Heroicon::OutlinedNoSymbol)
            ->color('danger')
            ->visible(fn (User $record): bool => self::canEdit($record))
            ->requiresConfirmation()
            ->modalDescription(__('gdpr.actions.anonymize_confirm'))
            ->action(function (User $record): void {
                app(UserAnonymizer::class)->anonymize($record);

                Notification::make()->title(__('gdpr.actions.anonymized'))->success()->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
