<?php

use App\Filament\Fm\Resources\MaintenanceOrderResource\Pages\ListMaintenanceOrders;
use App\Filament\Fm\Resources\TicketResource\Pages\ListTickets;
use App\Models\Company;
use App\Models\MaintenanceOrder;
use App\Models\MaintenancePlan;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

function loginAsServicePartner(Company $partner): User
{
    test()->seed(RolesAndPermissionsSeeder::class);

    $organization = defaultOrganization();

    setPermissionsTeamId($organization->getKey());

    $user = User::factory()->create(['email' => 'partner@example.com']);
    $user->organizations()->syncWithoutDetaching($organization);
    $user->forceFill(['company_id' => $partner->getKey()])->saveQuietly();
    $user->assignRole(Role::query()->where('name', 'Dienstleister')->where('organization_id', $organization->getKey())->firstOrFail());

    test()->actingAs($user);

    Filament::setTenant($organization);
    Filament::setCurrentPanel('fm');
    Filament::getPanel('fm')->boot();

    return $user->fresh();
}

it('lets service partners access only the FM panel', function () {
    $partner = makeServicePartnerFirm();
    $user = loginAsServicePartner($partner);

    expect($user->canAccessPanel(Filament::getPanel('fm')))->toBeTrue();
    expect($user->canAccessPanel(Filament::getPanel('pm')))->toBeFalse();
    expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeFalse();
    expect($user->restrictedToCompanyId())->toBe($partner->getKey());
});

it('shows service partners only tickets assigned to their company', function () {
    $partner = makeServicePartnerFirm();
    loginAsServicePartner($partner);

    $eigenesTicket = Ticket::factory()->create(['company_id' => $partner->getKey()]);
    $fremdesTicket = Ticket::factory()->create();

    Livewire::test(ListTickets::class)
        ->assertCanSeeTableRecords([$eigenesTicket])
        ->assertCanNotSeeTableRecords([$fremdesTicket]);
});

it('shows service partners only maintenance orders of their plans', function () {
    $partner = makeServicePartnerFirm();
    loginAsServicePartner($partner);

    $eigenerPlan = MaintenancePlan::factory()->create(['company_id' => $partner->getKey()]);
    $eigenerAuftrag = MaintenanceOrder::firstOrCreateForPlanDueDate($eigenerPlan);

    $fremderPlan = MaintenancePlan::factory()->create();
    $fremderAuftrag = MaintenanceOrder::firstOrCreateForPlanDueDate($fremderPlan);

    Livewire::test(ListMaintenanceOrders::class)
        ->assertCanSeeTableRecords([$eigenerAuftrag])
        ->assertCanNotSeeTableRecords([$fremderAuftrag]);
});

it('does not restrict admins linked to a partner', function () {
    $partner = makeServicePartnerFirm();

    $admin = loginAsAdmin();
    $admin->forceFill(['company_id' => $partner->getKey()])->saveQuietly();

    expect($admin->fresh()->restrictedToCompanyId())->toBeNull();
});

function makeServicePartnerFirm(): Company
{
    return Company::factory()->create();
}
