<?php

use App\Models\ClassificationNode;
use App\Models\Organization;
use Filament\Facades\Filament;

beforeEach(function (): void {
    Filament::setTenant(Organization::factory()->create(), isQuiet: true);
});

it('lists classification nodes with filters, sorting and includes', function () {
    $root = ClassificationNode::query()->create(['code' => '400', 'name' => 'Technische Anlagen', 'sort' => 2]);
    $child = ClassificationNode::query()->create(['code' => '440', 'name' => 'Elektrische Anlagen', 'parent_id' => $root->getKey(), 'sort' => 1]);
    ClassificationNode::query()->create(['code' => '300', 'name' => 'Bauwerk', 'sort' => 3]);

    actingAsApiUser(['classification:read']);

    $this->getJson('/api/v1/classification-nodes')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.code', '440')
        ->assertJsonStructure(['data' => [['id', 'parent_id', 'code', 'name', 'sort', 'links' => ['self']]], 'links', 'meta']);

    $this->getJson('/api/v1/classification-nodes?filter[parent_id]='.$root->getKey())->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $child->getKey());
    $this->getJson('/api/v1/classification-nodes?filter[code]=300')->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/classification-nodes?filter[name]=anlagen')->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/classification-nodes?filter[search]=44')->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/classification-nodes?filter[search]=bauw')->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/classification-nodes?sort=code')->assertJsonPath('data.0.code', '300');
    $this->getJson('/api/v1/classification-nodes?sort=-name')->assertJsonPath('data.0.code', '400');

    $this->getJson('/api/v1/classification-nodes?include=parent,children&filter[code]=400')
        ->assertOk()
        ->assertJsonPath('data.0.parent', null)
        ->assertJsonPath('data.0.children.0.code', '440');
});

it('shows a single classification node with its parent', function () {
    $root = ClassificationNode::query()->create(['code' => '400', 'name' => 'Technische Anlagen']);
    $child = ClassificationNode::query()->create(['code' => '440', 'name' => 'Elektro', 'parent_id' => $root->getKey()]);

    actingAsApiUser(['classification:read']);

    $this->getJson('/api/v1/classification-nodes/'.$child->getKey().'?include=parent')
        ->assertOk()
        ->assertJsonPath('data.code', '440')
        ->assertJsonPath('data.parent.code', '400');

    $this->getJson('/api/v1/classification-nodes/'.$child->getKey().'?include=nope')->assertStatus(400);
});

it('hides classification nodes of other organizations', function () {
    $node = ClassificationNode::query()->create(['code' => '400', 'name' => 'Technische Anlagen']);
    $fremd = inOrganization(Organization::factory()->create(), fn (): ClassificationNode => ClassificationNode::query()->create(['code' => '400', 'name' => 'Fremd']));

    actingAsApiUser(['classification:read']);

    $this->getJson('/api/v1/classification-nodes')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $node->getKey());
    $this->getJson('/api/v1/classification-nodes/'.$fremd->getKey())->assertNotFound();
});

it('is read-only and requires the classification ability', function () {
    $node = ClassificationNode::query()->create(['code' => '400', 'name' => 'Technische Anlagen']);

    actingAsApiUser(['properties:read']);

    $this->getJson('/api/v1/classification-nodes')->assertForbidden();

    actingAsApiUser(['classification:read']);

    $this->getJson('/api/v1/classification-nodes')->assertOk();
    $this->postJson('/api/v1/classification-nodes', ['code' => '500', 'name' => 'X'])->assertMethodNotAllowed();
    $this->deleteJson('/api/v1/classification-nodes/'.$node->getKey())->assertMethodNotAllowed();
});
