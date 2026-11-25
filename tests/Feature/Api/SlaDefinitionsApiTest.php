<?php

use App\Models\Organization;
use App\Models\SlaDefinition;
use Filament\Facades\Filament;

beforeEach(function (): void {
    Filament::setTenant(Organization::factory()->create(), isQuiet: true);
});

it('lists SLA definitions with filters and sorting', function () {
    $p1 = SlaDefinition::factory()->create(['priority' => 'p1', 'response_hours' => 2, 'resolution_hours' => 8]);
    SlaDefinition::factory()->create(['priority' => 'p2', 'response_hours' => 8, 'resolution_hours' => 48]);
    SlaDefinition::factory()->create(['priority' => 'p3', 'response_hours' => 24, 'resolution_hours' => 120, 'is_active' => false]);

    actingAsApiUser(['tickets:read']);

    $this->getJson('/api/v1/sla-definitions')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.priority', 'p1')
        ->assertJsonStructure(['data' => [['id', 'priority', 'response_hours', 'resolution_hours', 'is_active', 'links' => ['self']]], 'meta']);

    $this->getJson('/api/v1/sla-definitions?filter[priority]=p2')->assertJsonCount(1, 'data')->assertJsonPath('data.0.resolution_hours', 48);
    $this->getJson('/api/v1/sla-definitions?filter[is_active]=false')->assertJsonCount(1, 'data')->assertJsonPath('data.0.priority', 'p3');
    $this->getJson('/api/v1/sla-definitions?sort=-response_hours')->assertJsonPath('data.0.priority', 'p3');

    $this->getJson('/api/v1/sla-definitions/'.$p1->getKey())->assertOk()->assertJsonPath('data.response_hours', 2);
});

it('hides SLA definitions of other organizations', function () {
    SlaDefinition::factory()->create(['priority' => 'p1']);
    $fremd = inOrganization(Organization::factory()->create(), fn (): SlaDefinition => SlaDefinition::factory()->create(['priority' => 'p1']));

    actingAsApiUser(['tickets:read']);

    $this->getJson('/api/v1/sla-definitions')->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/sla-definitions/'.$fremd->getKey())->assertNotFound();
});

it('is read-only and requires the tickets ability', function () {
    $definition = SlaDefinition::factory()->create();

    actingAsApiUser(['tickets:write']);

    $this->postJson('/api/v1/sla-definitions', ['priority' => 'p2'])->assertMethodNotAllowed();
    $this->patchJson('/api/v1/sla-definitions/'.$definition->getKey(), ['response_hours' => 1])->assertMethodNotAllowed();
    $this->deleteJson('/api/v1/sla-definitions/'.$definition->getKey())->assertMethodNotAllowed();

    actingAsApiUser(['maintenance:read']);

    $this->getJson('/api/v1/sla-definitions')->assertForbidden();
});
