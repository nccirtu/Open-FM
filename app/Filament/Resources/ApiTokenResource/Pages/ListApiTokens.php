<?php

namespace App\Filament\Resources\ApiTokenResource\Pages;

use App\Filament\Resources\ApiTokenResource;
use App\Models\ApiToken;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;

class ListApiTokens extends ListRecords
{
    protected static string $resource = ApiTokenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('apiDocs')
                ->label(__('app.api_docs'))
                ->icon('heroicon-o-book-open')
                ->color('gray')
                ->url('/docs/api', shouldOpenInNewTab: true),
            Action::make('createToken')
                ->label(__('app.api_tokens.create'))
                ->icon('heroicon-o-plus')
                ->visible(fn (): bool => auth()->user()?->can('create', ApiToken::class) ?? false)
                ->modalHeading(__('app.api_tokens.create_heading'))
                ->modalSubmitActionLabel('Token erstellen')
                ->schema([
                    TextInput::make('name')
                        ->label(__('app.api_tokens.name_hint'))
                        ->required()
                        ->maxLength(255),
                    CheckboxList::make('abilities')
                        ->label(__('app.api_tokens.abilities'))
                        ->options(ApiToken::ABILITIES)
                        ->default(array_keys(ApiToken::ABILITIES))
                        ->required(),
                    DateTimePicker::make('expires_at')
                        ->label(__('app.api_tokens.expires_at'))
                        ->native(false)
                        ->seconds(false)
                        ->minDate(now())
                        ->when(
                            ApiToken::maxLifetimeDays() > 0,
                            fn (DateTimePicker $field): DateTimePicker => $field
                                ->required()
                                ->default(now()->addDays(ApiToken::maxLifetimeDays())->startOfMinute())
                                ->maxDate(now()->addDays(ApiToken::maxLifetimeDays()))
                                ->helperText(__('app.api_tokens.expires_at_limited', ['days' => ApiToken::maxLifetimeDays()])),
                            fn (DateTimePicker $field): DateTimePicker => $field
                                ->helperText(__('app.api_tokens.expires_at_unlimited')),
                        ),
                ])
                ->action(function (array $data): void {
                    /** @var User $user */
                    $user = auth()->user();

                    $token = $user->createToken(
                        $data['name'],
                        $data['abilities'],
                        filled($data['expires_at'] ?? null) ? Carbon::parse($data['expires_at']) : null,
                    );

                    Notification::make()
                        ->title(__('app.api_tokens.created_notification'))
                        ->body(new HtmlString(
                            '<p class="mb-2">Bitte <strong>jetzt kopieren</strong> – der Token wird aus Sicherheitsgründen nur einmal angezeigt:</p>'
                            .'<code class="block break-all rounded bg-gray-100 p-2 text-xs dark:bg-gray-800">'
                            .e($token->plainTextToken)
                            .'</code>'
                        ))
                        ->persistent()
                        ->success()
                        ->send();
                }),
        ];
    }
}
