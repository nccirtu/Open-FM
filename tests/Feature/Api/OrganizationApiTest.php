<?php

use App\Models\Organization;
use Filament\Facades\Filament;

beforeEach(function (): void {
    Filament::setTenant(Organization::factory()->create(['name' => 'Demo GmbH', 'slug' => 'demo-gmbh', 'default_locale' => 'en']), isQuiet: true);
});

it('shows the organization of the token', function () {
    Organization::factory()->create(['name' => 'Andere']);

    actingAsApiUser(['organization:read']);

    $this->getJson('/api/v1/organization')
        ->assertOk()
        ->assertJsonPath('data.id', Filament::getTenant()->getKey())
        ->assertJsonPath('data.name', 'Demo GmbH')
        ->assertJsonPath('data.slug', 'demo-gmbh')
        ->assertJsonPath('data.default_locale', 'en')
        ->assertJsonStructure(['data' => ['id', 'name', 'slug', 'default_locale', 'created_at', 'links' => ['self']]])
        ->assertJsonMissingPath('data.allowed_email_domains');
});

it('requires the organization ability', function () {
    actingAsApiUser(['properties:read']);

    $this->getJson('/api/v1/organization')->assertForbidden();
});
