<?php

use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;

beforeEach(function (): void {
    Filament::setTenant(Organization::factory()->create(), isQuiet: true);
});

it('lists the users of the tenant with search and sorting', function () {
    $organization = Filament::getTenant();
    $anna = User::factory()->create(['name' => 'Anna Zimmer', 'email' => 'anna@example.com', 'locale' => 'de']);
    $bernd = User::factory()->create(['name' => 'Bernd Adler', 'email' => 'bernd@example.com']);
    $anna->organizations()->attach($organization);
    $bernd->organizations()->attach($organization);
    $fremd = User::factory()->create(['name' => 'Fremd']);
    $fremd->organizations()->attach(Organization::factory()->create());
    $self = actingAsApiUser(['users:read']);
    $self->update(['name' => 'Zora Schnittstelle']);

    $this->getJson('/api/v1/users')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.id', $anna->getKey())
        ->assertJsonStructure(['data' => [['id', 'name', 'email', 'locale', 'created_at', 'links' => ['self']]], 'links', 'meta'])
        ->assertJsonMissingPath('data.0.password')
        ->assertJsonMissingPath('data.0.remember_token');

    $this->getJson('/api/v1/users?filter[search]=bernd@')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $bernd->getKey());
    $this->getJson('/api/v1/users?filter[search]=zimmer')->assertJsonCount(1, 'data')->assertJsonPath('data.0.locale', 'de');
    $this->getJson('/api/v1/users?sort=-name')->assertJsonPath('data.0.id', $self->getKey());
    $this->getJson('/api/v1/users?filter[unknown]=x')->assertStatus(400);
});

it('shows a single user of the tenant and hides foreign users', function () {
    $user = User::factory()->create(['name' => 'Anna Zimmer']);
    $user->organizations()->attach(Filament::getTenant());
    $fremd = User::factory()->create();
    $fremd->organizations()->attach(Organization::factory()->create());

    actingAsApiUser(['users:read']);

    $this->getJson('/api/v1/users/'.$user->getKey())
        ->assertOk()
        ->assertJsonPath('data.name', 'Anna Zimmer')
        ->assertJsonMissingPath('data.password');

    $this->getJson('/api/v1/users/'.$fremd->getKey())->assertNotFound();
});

it('is read-only and requires the users ability', function () {
    $user = actingAsApiUser(['properties:read']);

    $this->getJson('/api/v1/users')->assertForbidden();

    actingAsApiUser(['users:read']);

    $this->getJson('/api/v1/users')->assertOk();
    $this->postJson('/api/v1/users', ['name' => 'X'])->assertMethodNotAllowed();
    $this->deleteJson('/api/v1/users/'.$user->getKey())->assertMethodNotAllowed();
});
