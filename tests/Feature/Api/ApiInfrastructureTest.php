<?php

use App\Models\Asset;
use App\Models\Organization;
use App\Models\User;
use App\Support\OpenFm;
use Filament\Facades\Filament;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    Filament::setTenant(Organization::factory()->create(), isQuiet: true);
});

it('rejects unauthenticated requests', function () {
    $this->getJson('/api/v1/assets')->assertUnauthorized();
});

it('rejects tokens whose user belongs to no organization', function () {
    Sanctum::actingAs(User::factory()->create(), ['assets:read']);

    $this->getJson('/api/v1/assets')->assertForbidden();
});

it('enforces read and write abilities per resource', function () {
    actingAsApiUser(['assets:read']);

    $this->getJson('/api/v1/assets')->assertOk();
    $this->postJson('/api/v1/asset-types', ['name' => 'Pumpe'])->assertForbidden();

    actingAsApiUser(['assets:write'])->forceFill(['is_super_admin' => true])->save();

    $this->getJson('/api/v1/assets')->assertOk();
    $this->postJson('/api/v1/asset-types', ['name' => 'Pumpe'])->assertCreated();
});

it('rate limits requests per token', function () {
    config()->set('api.rate_limit_per_minute', 2);
    actingAsApiUser(['assets:read']);

    $this->getJson('/api/v1/assets')->assertOk()->assertHeader('X-RateLimit-Limit', '2');
    $this->getJson('/api/v1/assets')->assertOk();
    $this->getJson('/api/v1/assets')->assertTooManyRequests();
});

it('caps the page size and paginates with meta and links', function () {
    Asset::factory()->count(3)->create();
    actingAsApiUser(['assets:read']);

    $this->getJson('/api/v1/assets?per_page=2')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonPath('meta.total', 3)
        ->assertJsonStructure(['links' => ['first', 'last', 'prev', 'next']]);

    $this->getJson('/api/v1/assets?per_page=1000')
        ->assertOk()
        ->assertJsonPath('meta.per_page', config('api.max_per_page'));
});

it('rejects unknown filters, sorts and includes with a validation error', function () {
    actingAsApiUser(['assets:read']);

    $this->getJson('/api/v1/assets?filter[unknown]=x')->assertStatus(400);
    $this->getJson('/api/v1/assets?sort=unknown')->assertStatus(400);
    $this->getJson('/api/v1/assets?include=unknown')->assertStatus(400);
});

it('serves the OpenAPI documentation to admins only', function () {
    $this->get('/docs/api')->assertForbidden();

    loginAsAdmin();

    $this->get('/docs/api')->assertOk();
    $this->get('/docs/api.json')
        ->assertOk()
        ->assertJsonPath('info.title', 'Open-FM API')
        ->assertJsonPath('info.version', OpenFm::VERSION)
        ->assertJsonStructure(['paths' => ['/assets', '/asset-types']]);
});

it('applies the tenant scope before route model binding on the very first request', function () {
    $fremd = inOrganization(Organization::factory()->create(), fn (): Asset => Asset::factory()->create());

    actingAsApiUser(['assets:read']);

    $this->getJson('/api/v1/assets/'.$fremd->getKey())->assertNotFound();
});

it('links the API documentation in the admin navigation', function () {
    loginAsAdmin();

    $this->get(Filament::getPanel('admin')->getUrl())
        ->assertOk()
        ->assertSee(__('app.api_docs'))
        ->assertSee('/docs/api');
});
