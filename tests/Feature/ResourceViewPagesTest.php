<?php

use App\Filament\Contracts\Resources\ContractResource\Pages\EditContract;
use App\Filament\Contracts\Resources\ContractResource\Pages\ViewContract;
use App\Filament\Fm\Resources\CompanyResource\Pages\EditCompany;
use App\Filament\Fm\Resources\CompanyResource\Pages\ViewCompany;
use App\Filament\Fm\Resources\MaintenanceOrderResource\Pages\EditMaintenanceOrder;
use App\Filament\Fm\Resources\MaintenanceOrderResource\Pages\ViewMaintenanceOrder;
use App\Filament\Fm\Resources\MaintenancePlanResource\Pages\EditMaintenancePlan;
use App\Filament\Fm\Resources\MaintenancePlanResource\Pages\ViewMaintenancePlan;
use App\Filament\RelationManagers\CompanyCostEntriesRelationManager;
use App\Filament\RelationManagers\PropertyCostEntriesRelationManager;
use App\Filament\Resources\AssetResource\Pages\EditAsset;
use App\Filament\Resources\AssetResource\Pages\ViewAsset;
use App\Filament\Resources\PropertyResource\Pages\EditProperty;
use App\Filament\Resources\PropertyResource\Pages\ViewProperty;
use App\Models\Asset;
use App\Models\Company;
use App\Models\Contract;
use App\Models\CostEntry;
use App\Models\MaintenanceOrder;
use App\Models\MaintenancePlan;
use App\Models\Project;
use App\Models\Property;
use App\Models\Ticket;
use Livewire\Livewire;

it('renders a view page with relation tabs and keeps the edit page to the form', function (string $model, string $viewPage, string $editPage, string $login) {
    $login();

    $record = $model::factory()->create();

    $view = Livewire::test($viewPage, ['record' => $record->getKey()])->assertOk()->assertSee(__('app.details_tab'));

    expect($view->instance()->hasCombinedRelationManagerTabsWithContent())->toBeTrue();

    $edit = Livewire::test($editPage, ['record' => $record->getKey()])->assertOk();

    expect($edit->instance()->getRelationManagers())->toBe([])
        ->and($viewPage::getResource()::getRelations())->not->toBeEmpty();
})->with([
    'property' => [Property::class, ViewProperty::class, EditProperty::class, 'loginAsAdmin'],
    'asset' => [Asset::class, ViewAsset::class, EditAsset::class, 'loginAsAdmin'],
    'company' => [Company::class, ViewCompany::class, EditCompany::class, 'loginAsAdminInFmPanel'],
    'contract' => [Contract::class, ViewContract::class, EditContract::class, 'loginAsAdminInContractsPanel'],
    'maintenance plan' => [MaintenancePlan::class, ViewMaintenancePlan::class, EditMaintenancePlan::class, 'loginAsAdminInFmPanel'],
    'maintenance order' => [MaintenanceOrder::class, ViewMaintenanceOrder::class, EditMaintenanceOrder::class, 'loginAsAdminInFmPanel'],
]);

it('aggregates all costs of a property across projects, assets, tickets, maintenance and direct bookings', function () {
    loginAsAdmin();

    $property = Property::factory()->create();
    $other = Property::factory()->create();

    $project = Project::factory()->create(['property_id' => $property->getKey()]);
    $asset = Asset::factory()->create(['property_id' => $property->getKey()]);
    $ticket = Ticket::factory()->create(['property_id' => $property->getKey()]);
    $plan = MaintenancePlan::factory()->create(['asset_id' => $asset->getKey()]);
    $order = MaintenanceOrder::factory()->create(['maintenance_plan_id' => $plan->getKey()]);

    $entry = fn ($owner, float $amount): CostEntry => $owner->costEntries()->create(['kind' => 'actual', 'amount' => $amount, 'booked_on' => now()]);

    $direct = $entry($property, 10);
    $fromProject = $entry($project, 100);
    $fromAsset = $entry($asset, 1000);
    $fromTicket = $entry($ticket, 20);
    $fromOrder = $entry($order, 200);
    $estimate = $project->costEntries()->create(['kind' => 'estimate', 'amount' => 5000, 'booked_on' => now()]);
    $foreign = $entry($other, 999);

    expect($property->allCostEntries()->count())->toBe(6)
        ->and($property->costTotals())->toBe(['property' => 10.0, 'projects' => 100.0, 'assets' => 1000.0, 'tickets' => 20.0, 'maintenance' => 200.0, 'total' => 1330.0]);

    Livewire::test(PropertyCostEntriesRelationManager::class, ['ownerRecord' => $property, 'pageClass' => ViewProperty::class])
        ->assertOk()
        ->assertCanSeeTableRecords([$direct, $fromProject, $fromAsset, $fromTicket, $fromOrder, $estimate])
        ->assertCanNotSeeTableRecords([$foreign])
        ->assertSee($project->name)
        ->assertSee($asset->name);

    $view = Livewire::test(ViewProperty::class, ['record' => $property->getKey()])
        ->assertOk()
        ->assertSee(__('app.details_tab'))
        ->assertSee('1.330,00');

    expect($view->instance()->hasCombinedRelationManagerTabsWithContent())->toBeTrue();
});

it('lists cost entries of a company read-only', function () {
    loginAsAdminInFmPanel();

    $company = Company::query()->create(['name' => 'Rohrfrei GmbH']);
    $ticket = Ticket::factory()->create();
    $entry = $ticket->costEntries()->create(['kind' => 'actual', 'amount' => 50, 'booked_on' => now(), 'company_id' => $company->getKey()]);

    Livewire::test(CompanyCostEntriesRelationManager::class, ['ownerRecord' => $company, 'pageClass' => ViewCompany::class])
        ->assertOk()
        ->assertCanSeeTableRecords([$entry])
        ->assertDontSeeHtml("mountAction('captureReceipt'")
        ->assertDontSeeHtml("mountAction('create'");
});
