<?php

use App\Filament\Resources\ApiTokenResource\Pages\ListApiTokens;
use App\Models\ApiToken;
use Livewire\Livewire;

beforeEach(function () {
    loginAsAdmin('admin@example.com');
});

it('creates an API token with the chosen abilities via the Filament page', function () {
    Livewire::test(ListApiTokens::class)
        ->mountAction('createToken')
        ->setActionData(['name' => 'Partner X', 'expires_at' => now()->addDays(30)->format('Y-m-d H:i:s'), 'abilities' => ['assets:read']])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    $token = ApiToken::query()->where('name', 'Partner X')->firstOrFail();

    expect($token->abilities)->toBe(['assets:read']);
    expect($token->expires_at)->not->toBeNull();
});

it('requires a name and at least one ability', function () {
    Livewire::test(ListApiTokens::class)
        ->callAction('createToken', data: ['name' => '', 'abilities' => []])
        ->assertHasActionErrors(['name', 'abilities']);

    expect(ApiToken::query()->count())->toBe(0);
});
