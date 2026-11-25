<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Auth\Notifications\ResetPassword;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->inviteAction(),
        ];
    }

    public static function targetPanelIdFor(User $user): string
    {
        foreach (['pm', 'fm', 'contracts', 'portal', 'admin'] as $panelId) {
            if ($user->canAccessPanel(Filament::getPanel($panelId))) {
                return $panelId;
            }
        }

        return 'pm';
    }

    protected function inviteAction(): Action
    {
        return Action::make('invite')
            ->label(__('app.users.invite'))
            ->icon('heroicon-o-envelope')
            ->visible(fn (): bool => auth()->user()?->administersAnyOrganization() ?? false)
            ->modalHeading(__('app.users.invite'))
            ->modalSubmitActionLabel('Einladung senden')
            ->schema([
                TextInput::make('name')
                    ->label(__('app.users.name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label(__('app.users.email'))
                    ->email()
                    ->required()
                    ->unique(table: 'users', column: 'email')
                    ->maxLength(255),
                Select::make('organizations')
                    ->label(__('app.users.organizations'))
                    ->options(fn (): array => Organization::query()
                        ->administeredByCurrentUser()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->multiple()
                    ->required(),
                Select::make('role')
                    ->label(__('app.users.role_per_tenant'))
                    ->options(fn (): array => Role::query()
                        ->whereIn('organization_id', Organization::query()->administeredByCurrentUser()->select('organizations.id'))
                        ->distinct()
                        ->orderBy('name')
                        ->pluck('name', 'name')
                        ->all())
                    ->default('User')
                    ->required()
                    ->native(false),
            ])
            ->action(function (array $data): void {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Str::password(32),
                ]);

                $user->forceFill(['email_verified_at' => now()])->save();

                $user->organizations()->attach($data['organizations']);

                $previousTeamId = getPermissionsTeamId();

                try {
                    foreach ($data['organizations'] as $organizationId) {
                        setPermissionsTeamId($organizationId);

                        $role = Role::query()
                            ->where('name', $data['role'])
                            ->where('organization_id', $organizationId)
                            ->first();

                        if ($role !== null) {
                            $user->assignRole($role);
                        }
                    }
                } finally {
                    setPermissionsTeamId($previousTeamId);
                }

                $token = Password::broker()->createToken($user);

                $notification = new ResetPassword($token);
                $notification->url = Filament::getPanel(self::targetPanelIdFor($user->fresh()))
                    ->getResetPasswordUrl($token, $user);

                $user->notify($notification);

                Notification::make()
                    ->title(__('app.users.invitation_sent'))
                    ->body("{$user->email} hat eine E-Mail mit einem Link zum Setzen des Passworts erhalten.")
                    ->success()
                    ->send();
            });
    }
}
