<?php

use App\Models\ClassificationNode;
use App\Models\Company;
use App\Models\CostEntry;
use App\Models\Organization;
use App\Models\Project;
use Filament\Facades\Filament;

beforeEach(function (): void {
    Filament::setTenant(Organization::factory()->create(), isQuiet: true);
});

it('lists cost entries with filters, sorting and includes', function () {
    $project = Project::factory()->create(['name' => 'Umbau Nord']);
    $position = $project->budgetPositions()->create(['name' => 'Elektro', 'budget' => 1000]);
    $costCenter = ClassificationNode::query()->create(['code' => '440', 'name' => 'Elektro']);
    $company = Company::factory()->create(['name' => 'Elektro Müller GmbH']);
    $treffer = $project->costEntries()->create(['budget_position_id' => $position->getKey(), 'cost_center_id' => $costCenter->getKey(), 'company_id' => $company->getKey(), 'kind' => 'actual', 'amount' => 4500, 'invoice_number' => 'RE-2026-001', 'vendor' => 'Elektro Müller', 'description' => 'Kabelwege EG', 'booked_on' => '2026-02-10']);
    $project->costEntries()->create(['kind' => 'estimate', 'amount' => 800, 'vendor' => 'Maler Schmidt', 'booked_on' => '2026-03-01']);
    Project::factory()->create()->costEntries()->create(['kind' => 'actual', 'amount' => 120, 'booked_on' => '2026-01-05']);

    actingAsApiUser(['projects:read']);

    $this->getJson('/api/v1/cost-entries')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.vendor', 'Maler Schmidt')
        ->assertJsonStructure(['data' => [['id', 'project_id', 'budget_position_id', 'kind', 'receipt_type', 'amount', 'booked_on', 'links' => ['self']]], 'links', 'meta']);

    $this->getJson('/api/v1/cost-entries?filter[project_id]='.$project->getKey())->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/cost-entries?filter[budget_position_id]='.$position->getKey())->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $treffer->getKey());
    $this->getJson('/api/v1/cost-entries?filter[cost_center_id]='.$costCenter->getKey())->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/cost-entries?filter[company_id]='.$company->getKey())->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/cost-entries?filter[kind]=estimate')->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/cost-entries?filter[booked_from]=2026-02-01')->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/cost-entries?filter[booked_to]=2026-02-10')->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/cost-entries?filter[booked_from]=2026-02-01&filter[booked_to]=2026-02-28')->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/cost-entries?filter[search]=RE-2026')->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/cost-entries?filter[search]=schmidt')->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/cost-entries?filter[search]=kabelwege')->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/cost-entries?sort=amount')->assertJsonPath('data.0.amount', '120.00');
    $this->getJson('/api/v1/cost-entries?sort=booked_on')->assertJsonPath('data.0.amount', '120.00');

    $this->getJson('/api/v1/cost-entries?include=project,budgetPosition,costCenter,company&filter[search]=RE-2026')
        ->assertOk()
        ->assertJsonPath('data.0.project.name', 'Umbau Nord')
        ->assertJsonPath('data.0.budget_position.name', 'Elektro')
        ->assertJsonPath('data.0.cost_center.code', '440')
        ->assertJsonPath('data.0.company.name', 'Elektro Müller GmbH');
});

it('shows a cost entry and hides entries of other organizations', function () {
    $entry = Project::factory()->create()->costEntries()->create(['amount' => 10, 'booked_on' => '2026-01-01']);
    $fremd = inOrganization(Organization::factory()->create(), fn (): CostEntry => Project::factory()->create()->costEntries()->create(['amount' => 20, 'booked_on' => '2026-01-01']));

    actingAsApiUser(['projects:read']);

    $this->getJson('/api/v1/cost-entries')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $entry->getKey());
    $this->getJson('/api/v1/cost-entries/'.$entry->getKey().'?include=project')->assertOk()->assertJsonPath('data.project.id', $entry->project_id);
    $this->getJson('/api/v1/cost-entries/'.$entry->getKey().'?include=nope')->assertStatus(400);
    $this->getJson('/api/v1/cost-entries/'.$fremd->getKey())->assertNotFound();
});

it('creates, updates and deletes cost entries with tenant-scoped validation', function () {
    $project = Project::factory()->create();
    $position = $project->budgetPositions()->create(['name' => 'Elektro', 'budget' => 1000]);
    $fremdePosition = Project::factory()->create()->budgetPositions()->create(['name' => 'Anderes', 'budget' => 1]);
    $company = Company::factory()->create();
    $fremdesProjekt = inOrganization(Organization::factory()->create(), fn (): Project => Project::factory()->create());
    $fremdeFirma = inOrganization(Organization::factory()->create(), fn (): Company => Company::factory()->create());

    $user = actingAsApiUser(['projects:write']);

    $this->postJson('/api/v1/cost-entries', ['project_id' => $fremdesProjekt->getKey(), 'company_id' => $fremdeFirma->getKey(), 'amount' => 10, 'booked_on' => '2026-01-01'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['project_id', 'company_id']);

    $this->postJson('/api/v1/cost-entries', ['project_id' => $project->getKey(), 'budget_position_id' => $fremdePosition->getKey(), 'receipt_type' => 'nope', 'kind' => 'nope'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['budget_position_id', 'receipt_type', 'kind', 'amount', 'booked_on']);

    $response = $this->postJson('/api/v1/cost-entries', [
        'project_id' => $project->getKey(),
        'budget_position_id' => $position->getKey(),
        'company_id' => $company->getKey(),
        'kind' => 'actual',
        'receipt_type' => 'expense',
        'amount' => 1190,
        'tax_rate' => 19,
        'net_amount' => 1000,
        'invoice_number' => 'RE-1',
        'booked_on' => '2026-02-10',
        'due_on' => '2026-03-10',
        'vendor' => 'Elektro Müller',
        'costable_type' => 'App\\Models\\Ticket',
        'costable_id' => 'x',
    ])->assertCreated()
        ->assertJsonPath('data.amount', '1190.00')
        ->assertJsonPath('data.kind', 'actual')
        ->assertJsonPath('data.booked_on', '2026-02-10')
        ->assertJsonPath('data.costable_type', null)
        ->assertJsonPath('data.budget_position.id', $position->getKey())
        ->assertJsonPath('data.project.id', $project->getKey());

    $entry = CostEntry::query()->findOrFail($response->json('data.id'));

    expect($entry->organization_id)->toBe(Filament::getTenant()->getKey())
        ->and($entry->created_by)->toBe($user->getKey());

    $this->patchJson('/api/v1/cost-entries/'.$entry->getKey(), ['budget_position_id' => $fremdePosition->getKey()])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['budget_position_id']);

    $this->patchJson('/api/v1/cost-entries/'.$entry->getKey(), ['kind' => 'estimate', 'amount' => 999.5, 'receipt_type' => 'credit_note'])
        ->assertOk()
        ->assertJsonPath('data.kind', 'estimate')
        ->assertJsonPath('data.amount', '999.50')
        ->assertJsonPath('data.receipt_type', 'credit_note')
        ->assertJsonPath('data.invoice_number', 'RE-1');

    $this->deleteJson('/api/v1/cost-entries/'.$entry->getKey())->assertNoContent();

    expect(CostEntry::query()->find($entry->getKey()))->toBeNull();
});

it('enforces abilities and tenant isolation on writes', function () {
    $entry = Project::factory()->create()->costEntries()->create(['amount' => 10, 'booked_on' => '2026-01-01']);
    $fremd = inOrganization(Organization::factory()->create(), fn (): CostEntry => Project::factory()->create()->costEntries()->create(['amount' => 20, 'booked_on' => '2026-01-01']));

    actingAsApiUser(['projects:read']);

    $this->postJson('/api/v1/cost-entries', ['project_id' => $entry->project_id, 'amount' => 1, 'booked_on' => '2026-01-01'])->assertForbidden();
    $this->patchJson('/api/v1/cost-entries/'.$entry->getKey(), ['amount' => 1])->assertForbidden();
    $this->deleteJson('/api/v1/cost-entries/'.$entry->getKey())->assertForbidden();

    actingAsApiUser(['projects:write']);

    $this->patchJson('/api/v1/cost-entries/'.$fremd->getKey(), ['amount' => 1])->assertNotFound();
    $this->deleteJson('/api/v1/cost-entries/'.$fremd->getKey())->assertNotFound();

    expect((float) $fremd->fresh()->amount)->toBe(20.0);
});
