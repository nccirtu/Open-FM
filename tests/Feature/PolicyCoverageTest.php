<?php

use App\Enums\ChangeOrderStatus;
use App\Enums\TaskStatus;
use App\Enums\TicketStatus;
use App\Filament\Fm\Pages\AssetCategoriesPage;
use App\Filament\Fm\Resources\CompanyResource;
use App\Filament\Fm\Resources\CompanyResource\Pages\ViewCompany;
use App\Filament\Fm\Resources\TicketResource;
use App\Filament\Fm\Resources\TicketResource\Pages\ListTickets;
use App\Filament\Integrations\GanttChart\ProjectGanttWidget;
use App\Filament\Integrations\MediaManager\MediaManagerPage;
use App\Filament\Integrations\StructureTree\StructureTreeWidget;
use App\Filament\Pm\Pages\CostCentersPage;
use App\Filament\RelationManagers\CompanyCostEntriesRelationManager;
use App\Filament\RelationManagers\TicketsRelationManager;
use App\Filament\Resources\ProjectResource\Pages\ProjectBoard;
use App\Filament\Resources\ProjectResource\Pages\ViewProject;
use App\Filament\Resources\ProjectResource\RelationManagers\ChangeOrdersRelationManager;
use App\Filament\Resources\ProjectResource\RelationManagers\MilestonesRelationManager;
use App\Filament\Resources\PropertyResource\Pages\BimPage;
use App\Filament\Resources\PropertyResource\Pages\ViewProperty;
use App\Filament\Resources\TaskResource\Pages\ListTasks;
use App\Models\AppSetting;
use App\Models\AssetType;
use App\Models\AssetTypeClassification;
use App\Models\BimModel;
use App\Models\BudgetPosition;
use App\Models\ChangeOrder;
use App\Models\ClassificationNode;
use App\Models\Company;
use App\Models\CompanyAddress;
use App\Models\CompanyContact;
use App\Models\CostEntry;
use App\Models\Milestone;
use App\Models\Plan;
use App\Models\Plugin;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\ProjectTemplate;
use App\Models\Property;
use App\Models\Role;
use App\Models\StructureNode;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Slimani\MediaManager\Models\File;
use Slimani\MediaManager\Models\Folder;

/**
 * @param  list<string>  $revoked
 */
function coverageUser(string $role, array $revoked = [], ?Company $company = null): User
{
    $organization = defaultOrganization();

    $roleModel = Role::query()->where('name', $role)->where('organization_id', $organization->getKey())->firstOrFail();

    if ($revoked !== []) {
        $roleModel = Role::query()->create([
            'name' => $role.' (eingeschränkt '.fake()->unique()->numerify('####').')',
            'guard_name' => 'web',
            'organization_id' => $organization->getKey(),
        ]);
        $roleModel->syncPermissions(Role::query()->where('name', $role)->where('organization_id', $organization->getKey())->firstOrFail()->permissions->reject(fn ($permission): bool => in_array($permission->name, $revoked, true)));
    }

    $user = User::factory()->create();
    $user->organizations()->syncWithoutDetaching([$organization->getKey()]);
    $user->forceFill(['company_id' => $company?->getKey()])->saveQuietly();
    $user->assignRole($roleModel);

    return $user->fresh();
}

function coveragePartner(Company $company): User
{
    return coverageUser('Dienstleister', company: $company);
}

it('has a policy for every application model', function () {
    $models = collect(glob(app_path('Models/*.php')))
        ->map(fn (string $file): string => 'App\\Models\\'.basename($file, '.php'))
        ->filter(fn (string $class): bool => is_subclass_of($class, Model::class) && ! (new ReflectionClass($class))->isAbstract())
        ->push(File::class, Folder::class);

    $withoutPolicy = $models->reject(fn (string $class): bool => Gate::getPolicyFor($class) !== null)->values()->all();

    expect($withoutPolicy)->toBe([]);
});

it('has a policy for every model used by a resource in any panel', function () {
    $withoutPolicy = collect(Filament::getPanels())
        ->flatMap(fn ($panel): array => $panel->getResources())
        ->map(fn (string $resource): string => $resource::getModel())
        ->unique()
        ->reject(fn (string $model): bool => Gate::getPolicyFor($model) !== null)
        ->values()
        ->all();

    expect($withoutPolicy)->toBe([]);
});

it('runs every panel with strict authorization', function () {
    foreach (Filament::getPanels() as $panel) {
        expect($panel->isAuthorizationStrict())->toBeTrue("Panel {$panel->getId()}");
    }
});

it('grants the derived project, company, property and media abilities per role', function (string $model) {
    loginAsAdmin();

    $matrix = [
        'Admin' => true,
        'User' => true,
        'FM' => true,
        'PM' => true,
        'Melder' => false,
        'Dienstleister' => false,
    ];

    foreach ($matrix as $role => $expected) {
        $user = coverageUser($role);

        expect(Gate::forUser($user)->allows('viewAny', $model))->toBe($expected, "{$role} viewAny {$model}")
            ->and(Gate::forUser($user)->allows('create', $model))->toBe($expected, "{$role} create {$model}");
    }
})->with([
    Task::class,
    ProjectPhase::class,
    Milestone::class,
    BudgetPosition::class,
    ChangeOrder::class,
    CostEntry::class,
    ProjectTemplate::class,
    CompanyAddress::class,
    CompanyContact::class,
    StructureNode::class,
    BimModel::class,
    File::class,
    Folder::class,
]);

it('lets only the own company through for service partners and nobody else', function () {
    loginAsAdminInFmPanel();

    $own = Company::factory()->create();
    $foreign = Company::factory()->create();
    $partner = coveragePartner($own);
    $fm = coverageUser('FM');
    $melder = coverageUser('Melder');

    expect($partner->can('view', $own))->toBeTrue()
        ->and($partner->can('view', $foreign))->toBeFalse()
        ->and($partner->can('viewAny', Company::class))->toBeFalse()
        ->and($partner->can('update', $own))->toBeFalse()
        ->and($partner->can('delete', $own))->toBeFalse()
        ->and($partner->can('create', Company::class))->toBeFalse()
        ->and($fm->can('view', $foreign))->toBeTrue()
        ->and($fm->can('update', $foreign))->toBeTrue()
        ->and($melder->can('viewAny', Company::class))->toBeFalse()
        ->and($melder->can('view', $foreign))->toBeFalse();

    $this->actingAs($partner);

    expect(CompanyResource::getEloquentQuery()->pluck('id')->all())->toBe([$own->getKey()]);
});

it('hides foreign cost entries, documents and tickets from service partners', function () {
    loginAsAdminInFmPanel();

    $own = Company::factory()->create();
    $foreign = Company::factory()->create();
    $property = Property::factory()->create();
    $ownTicket = Ticket::factory()->create(['company_id' => $own->getKey(), 'property_id' => $property->getKey()]);
    $foreignTicket = Ticket::factory()->create(['company_id' => $foreign->getKey(), 'property_id' => $property->getKey()]);
    $foreignCost = $foreign->costEntries()->create(['kind' => 'actual', 'amount' => 99, 'booked_on' => '2026-01-01', 'company_id' => $foreign->getKey()]);
    $partner = coveragePartner($own);

    expect(CompanyCostEntriesRelationManager::canViewForRecord($foreign, ViewCompany::class))->toBeTrue();

    $this->actingAs($partner);

    expect(CompanyCostEntriesRelationManager::canViewForRecord($foreign, ViewCompany::class))->toBeFalse()
        ->and($partner->can('viewAny', CostEntry::class))->toBeFalse()
        ->and($partner->can('view', $foreignCost))->toBeFalse()
        ->and($partner->can('viewAny', File::class))->toBeFalse()
        ->and($partner->can('viewAny', Folder::class))->toBeFalse()
        ->and(MediaManagerPage::canAccess())->toBeFalse();

    Livewire::test(TicketsRelationManager::class, ['ownerRecord' => $property, 'pageClass' => ViewProperty::class])
        ->assertCanSeeTableRecords([$ownTicket])
        ->assertCanNotSeeTableRecords([$foreignTicket]);
});

it('keeps tickets of service partners without a company hidden', function () {
    loginAsAdminInFmPanel();

    Ticket::factory()->create(['company_id' => null]);
    $partner = coveragePartner(Company::factory()->create());
    $partner->forceFill(['company_id' => null])->saveQuietly();

    $this->actingAs($partner->fresh());

    expect(Ticket::query()->count())->toBe(1)
        ->and(TicketResource::getEloquentQuery()->count())->toBe(0);
});

it('does not let service partners reassign a ticket to another company', function () {
    loginAsAdminInFmPanel();

    $own = Company::factory()->create();
    $foreign = Company::factory()->create();
    $ticket = Ticket::factory()->create(['company_id' => $own->getKey(), 'status' => TicketStatus::Reported]);
    $partner = coveragePartner($own);

    $this->actingAs($partner);

    Livewire::test(ListTickets::class)
        ->callAction(TestAction::make('process')->table($ticket), ['status' => TicketStatus::InProgress->value, 'company_id' => $foreign->getKey()]);

    expect($ticket->fresh()->company_id)->toBe($own->getKey());
});

it('blocks the cost center and asset category pages for roles without the matching rights', function () {
    loginAsAdminInFmPanel();

    $partner = coveragePartner(Company::factory()->create());
    $melder = coverageUser('Melder');
    $fm = coverageUser('FM');

    foreach ([$partner, $melder] as $outsider) {
        $this->actingAs($outsider);

        expect(CostCentersPage::canAccess())->toBeFalse()
            ->and($outsider->can('import', ClassificationNode::class))->toBeFalse();

        Livewire::test(CostCentersPage::class)->assertForbidden();
    }

    $this->actingAs($partner);
    Livewire::test(AssetCategoriesPage::class)->assertForbidden();
    expect(AssetCategoriesPage::canAccess())->toBeFalse();

    $this->actingAs($fm);
    expect(CostCentersPage::canAccess())->toBeTrue()
        ->and(AssetCategoriesPage::canAccess())->toBeTrue();
});

it('lets catalogs be read by asset users and written by super admins only', function () {
    loginAsAdminInFmPanel();

    $admin = coverageUser('Admin');
    $user = coverageUser('User');
    $melder = coverageUser('Melder');
    $partner = coveragePartner(Company::factory()->create());
    $super = coverageUser('Admin');
    $super->forceFill(['is_super_admin' => true])->save();

    foreach ([AssetType::class, ClassificationNode::class, AssetTypeClassification::class] as $model) {
        expect($user->can('viewAny', $model))->toBeTrue($model)
            ->and($melder->can('viewAny', $model))->toBeTrue($model)
            ->and($partner->can('viewAny', $model))->toBeFalse($model);
    }

    expect($admin->can('create', AssetType::class))->toBeFalse()
        ->and($user->can('update', new AssetType))->toBeFalse()
        ->and($super->fresh()->can('create', AssetType::class))->toBeTrue()
        ->and($melder->can('create', ClassificationNode::class))->toBeFalse()
        ->and($user->can('create', ClassificationNode::class))->toBeTrue();

    foreach ([Plugin::class, AppSetting::class, Plan::class] as $model) {
        expect($admin->can('viewAny', $model))->toBeFalse($model)
            ->and($user->can('viewAny', $model))->toBeFalse($model)
            ->and($super->fresh()->can('viewAny', $model))->toBeTrue($model)
            ->and($super->fresh()->can('create', $model))->toBeTrue($model);
    }
});

it('derives child abilities from the project incl. manager and all rights', function () {
    loginAsAdmin();

    $user = coverageUser('User', revoked: ['view_all_project', 'update_all_project', 'delete_all_project']);
    $own = Project::factory()->managedBy($user)->create();
    $foreign = Project::factory()->create();
    $ownTask = Task::factory()->create(['project_id' => $own->getKey()]);
    $foreignTask = Task::factory()->create(['project_id' => $foreign->getKey()]);
    $assigned = Task::factory()->create(['project_id' => $foreign->getKey(), 'assignee_id' => $user->getKey()]);
    $foreignPhase = $foreign->phases()->create(['name' => 'Phase', 'sort' => 1]);
    $ownMilestone = $own->milestones()->create(['name' => 'Abnahme', 'due_date' => '2026-12-01', 'sort' => 1]);

    expect($user->can('view', $ownTask))->toBeTrue()
        ->and($user->can('update', $ownTask))->toBeTrue()
        ->and($user->can('delete', $ownTask))->toBeTrue()
        ->and($user->can('view', $foreignTask))->toBeFalse()
        ->and($user->can('update', $foreignTask))->toBeFalse()
        ->and($user->can('view', $assigned))->toBeTrue()
        ->and($user->can('update', $assigned))->toBeFalse()
        ->and($user->can('update', $foreignPhase))->toBeFalse()
        ->and($user->can('view', $foreignPhase))->toBeFalse()
        ->and($user->can('update', $ownMilestone))->toBeTrue();

    $this->actingAs($user);

    Livewire::test(ListTasks::class)
        ->set('activeTab', 'all')
        ->assertCanSeeTableRecords([$ownTask, $assigned])
        ->assertCanNotSeeTableRecords([$foreignTask]);
});

it('refuses kanban moves for users who may not update the project', function () {
    loginAsAdmin();

    $user = coverageUser('User', revoked: ['update_all_project']);
    $project = Project::factory()->create();
    $task = Task::factory()->create(['project_id' => $project->getKey(), 'status' => TaskStatus::Todo]);

    $this->actingAs($user);

    expect($user->can('view', $project))->toBeTrue()
        ->and($user->can('update', $project))->toBeFalse();

    Livewire::test(ProjectBoard::class, ['record' => $project->getKey()])
        ->call('moveCard', $task->getKey(), TaskStatus::Done->value)
        ->assertForbidden();

    expect($task->fresh()->status)->toBe(TaskStatus::Todo);

    $this->actingAs(coverageUser('User'));
    Livewire::test(ProjectBoard::class, ['record' => $project->getKey()])
        ->call('moveCard', $task->getKey(), TaskStatus::Done->value)
        ->assertSuccessful();

    expect($task->fresh()->status)->toBe(TaskStatus::Done);
});

it('refuses gantt edits for users who may not update the project', function () {
    requiresPlugin('gantt-chart');
    loginAsAdmin();

    $user = coverageUser('User', revoked: ['update_all_project']);
    $project = Project::factory()->create(['start_date' => '2026-09-01', 'end_date' => '2026-10-30']);
    $task = Task::factory()->create(['project_id' => $project->getKey(), 'start_date' => '2026-09-01', 'due_date' => '2026-09-05']);

    $this->actingAs($user);

    Livewire::test(ProjectGanttWidget::class, ['project' => $project])
        ->call('moveItem', 'task-'.$task->getKey(), '2026-09-10', '2026-09-12')
        ->assertForbidden();

    expect($task->fresh()->start_date->toDateString())->toBe('2026-09-01');

    Livewire::test(ProjectGanttWidget::class, ['project' => $project])
        ->callAction('editItem', ['title' => 'Manipuliert'], ['key' => 'task-'.$task->getKey()])
        ->assertForbidden();

    expect($task->fresh()->title)->not->toBe('Manipuliert');
});

it('refuses structure tree changes for users who may not update the property', function () {
    requiresPlugin('structure-tree');
    loginAsAdminInFmPanel();

    $user = coverageUser('User', revoked: ['update_all_property']);
    $property = Property::factory()->create();
    $node = StructureNode::factory()->create(['property_id' => $property->getKey(), 'name' => 'Halle']);

    $this->actingAs($user);

    expect($user->can('view', $property))->toBeTrue()
        ->and($user->can('update', $property))->toBeFalse();

    Livewire::test(StructureTreeWidget::class, ['property' => $property])
        ->assertActionHidden('createNode')
        ->assertActionHidden('deleteNode', ['node' => $node->getKey()])
        ->assertActionHidden('editNode', ['node' => $node->getKey()])
        ->call('moveNode', $node->getKey(), null, [$node->getKey()])
        ->assertForbidden();

    expect(StructureNode::query()->whereKey($node->getKey())->exists())->toBeTrue();

    $this->actingAs(coverageUser('User'));

    Livewire::test(StructureTreeWidget::class, ['property' => $property])
        ->assertActionVisible('createNode')
        ->assertActionVisible('deleteNode', ['node' => $node->getKey()])
        ->call('moveNode', $node->getKey(), null, [$node->getKey()])
        ->assertSuccessful();
});

it('refuses change order decisions and milestone updates without project update rights', function () {
    loginAsAdmin();

    $user = coverageUser('User', revoked: ['update_all_project']);
    $project = Project::factory()->create();
    $order = $project->changeOrders()->create(['title' => 'Mehraufwand', 'amount' => 500, 'status' => ChangeOrderStatus::Pending, 'requested_on' => '2026-09-01']);
    $milestone = $project->milestones()->create(['name' => 'Abnahme', 'due_date' => '2026-12-01', 'sort' => 1]);

    $this->actingAs($user);

    expect($user->can('decide', $order))->toBeFalse();

    Livewire::test(ChangeOrdersRelationManager::class, ['ownerRecord' => $project, 'pageClass' => ViewProject::class])
        ->assertActionHidden(TestAction::make('approve')->table($order))
        ->assertActionHidden(TestAction::make('reject')->table($order))
        ->assertActionHidden(TestAction::make('create')->table());

    Livewire::test(MilestonesRelationManager::class, ['ownerRecord' => $project, 'pageClass' => ViewProject::class])
        ->assertActionHidden(TestAction::make('complete')->table($milestone))
        ->assertActionHidden(TestAction::make('create')->table());

    expect($order->fresh()->status)->toBe(ChangeOrderStatus::Pending);

    $this->actingAs(coverageUser('User'));

    Livewire::test(ChangeOrdersRelationManager::class, ['ownerRecord' => $project, 'pageClass' => ViewProject::class])
        ->callAction(TestAction::make('approve')->table($order))
        ->assertHasNoActionErrors();

    expect($order->fresh()->status)->toBe(ChangeOrderStatus::Approved);
});

it('refuses the bim page without view rights on the property', function () {
    loginAsAdminInFmPanel();

    $user = coverageUser('User', revoked: ['view_all_property']);
    $foreign = Property::factory()->create();

    $this->actingAs($user);

    Livewire::test(BimPage::class, ['record' => $foreign->getKey()])->assertNotFound();

    $viewer = coverageUser('User', revoked: ['update_all_property']);
    $property = Property::factory()->create();

    $this->actingAs($viewer);

    Livewire::test(BimPage::class, ['record' => $property->getKey()])->assertSuccessful();
});

it('derives property and company children from their parent', function () {
    loginAsAdminInFmPanel();

    $user = coverageUser('User', revoked: ['view_all_property', 'update_all_property']);
    $own = Property::factory()->managedBy($user)->create();
    $foreign = Property::factory()->create();
    $ownNode = StructureNode::factory()->create(['property_id' => $own->getKey()]);
    $foreignNode = StructureNode::factory()->create(['property_id' => $foreign->getKey()]);
    $company = Company::factory()->create();
    $address = $company->addresses()->create(['street' => 'Hauptstr. 1', 'city' => 'Köln']);

    expect($user->can('view', $ownNode))->toBeTrue()
        ->and($user->can('update', $ownNode))->toBeTrue()
        ->and($user->can('view', $foreignNode))->toBeFalse()
        ->and($user->can('update', $foreignNode))->toBeFalse()
        ->and($user->can('delete', $foreignNode))->toBeFalse()
        ->and($user->can('update', $address))->toBeTrue();

    $melder = coverageUser('Melder');

    expect($melder->can('view', $address))->toBeFalse()
        ->and($melder->can('update', $address))->toBeFalse();
});

it('lets only roles with document rights manage media files and folders', function () {
    loginAsAdminInFmPanel();

    $file = File::create(['name' => 'plan.pdf', 'size' => 1, 'extension' => 'pdf', 'mime_type' => 'application/pdf']);
    $folder = Folder::create(['name' => 'Objekte']);
    $partner = coveragePartner(Company::factory()->create());

    foreach (['Admin', 'User', 'FM', 'PM'] as $role) {
        $user = coverageUser($role);

        expect($user->can('view', $file))->toBeTrue($role)
            ->and($user->can('delete', $file))->toBeTrue($role)
            ->and($user->can('delete', $folder))->toBeTrue($role);
    }

    foreach ([coverageUser('Melder'), $partner] as $outsider) {
        expect($outsider->can('view', $file))->toBeFalse()
            ->and($outsider->can('delete', $file))->toBeFalse()
            ->and($outsider->can('delete', $folder))->toBeFalse()
            ->and($outsider->can('update', $folder))->toBeFalse();
    }
});

it('hides the bim upload for users who may not update the property', function () {
    requiresPlugin('bim-viewer');
    loginAsAdminInFmPanel();

    $property = Property::factory()->create();
    $this->actingAs(coverageUser('User', revoked: ['update_all_property']));

    Livewire::test(BimPage::class, ['record' => $property->getKey()])
        ->assertActionHidden('upload');

    $this->actingAs(coverageUser('User'));

    Livewire::test(BimPage::class, ['record' => $property->getKey()])
        ->assertActionVisible('upload');
});
