<?php

use App\Enums\BillingCycle;
use App\Enums\ContractStatus;
use App\Enums\ContractType;
use App\Filament\Contracts\Resources\ContractResource\Pages\CreateContract;
use App\Filament\Contracts\Resources\ContractResource\Pages\EditContract;
use App\Filament\Contracts\Resources\ContractResource\Pages\ListContracts;
use App\Filament\Fm\Resources\TicketResource\Pages\ListTickets;
use App\Models\Asset;
use App\Models\Contract;
use App\Models\MaintenancePlan;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

it('creates a contract with tenant, term and assignments', function () {
    loginAsAdminInContractsPanel();

    $property = Property::factory()->create();
    $asset = Asset::factory()->create(['property_id' => $property->id]);

    Livewire::test(CreateContract::class)
        ->fillForm([
            'name' => 'Wartung Aufzüge',
            'contract_number' => 'V-2026-001',
            'partner_name' => 'Schindler GmbH',
            'type' => ContractType::Maintenance->value,
            'status' => ContractStatus::Active->value,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYears(2)->toDateString(),
            'notice_period_days' => 90,
            'reminder_lead_days' => 60,
            'amount' => 450,
            'billing_cycle' => BillingCycle::Monthly->value,
            'properties' => [$property->id],
            'assets' => [$asset->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $contract = Contract::query()->firstOrFail();

    expect($contract->organization_id)->toBe(defaultOrganization()->getKey());
    expect($contract->type)->toBe(ContractType::Maintenance);
    expect($contract->properties->pluck('id')->all())->toBe([$property->id]);
    expect($contract->assets->pluck('id')->all())->toBe([$asset->id]);
});

it('requires name, partner, type and status', function () {
    loginAsAdminInContractsPanel();

    Livewire::test(CreateContract::class)
        ->fillForm([
            'name' => '',
            'partner_name' => '',
            'type' => null,
            'status' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'name' => 'required',
            'partner_name' => 'required',
            'type' => 'required',
            'status' => 'required',
        ]);
});

it('only lists contracts of the current tenant', function () {
    loginAsAdminInContractsPanel();

    $eigenerVertrag = Contract::factory()->create();

    $fremdeOrganisation = Organization::factory()->create();
    $fremderVertrag = inOrganization($fremdeOrganisation, fn (): Contract => Contract::factory()->create());

    expect($fremderVertrag->organization_id)->toBe($fremdeOrganisation->getKey());

    Livewire::test(ListContracts::class)
        ->assertCanSeeTableRecords([$eigenerVertrag])
        ->assertCanNotSeeTableRecords([$fremderVertrag]);
});

it('updates a contract via the edit page', function () {
    loginAsAdminInContractsPanel();

    $contract = Contract::factory()->create();

    Livewire::test(EditContract::class, ['record' => $contract->getKey()])
        ->fillForm([
            'status' => ContractStatus::Terminated->value,
            'notes' => 'Zum Laufzeitende gekündigt.',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($contract->fresh()->status)->toBe(ContractStatus::Terminated);
    expect($contract->fresh()->notes)->toBe('Zum Laufzeitende gekündigt.');
});

it('computes the termination deadline from end date and notice period', function () {
    $contract = Contract::factory()->make([
        'end_date' => '2027-06-30',
        'notice_period_days' => 90,
    ]);

    expect($contract->noticeDeadline()->toDateString())->toBe('2027-04-01');

    $unbefristet = Contract::factory()->make(['end_date' => null]);

    expect($unbefristet->noticeDeadline())->toBeNull();
    expect($unbefristet->needsTerminationReminder())->toBeFalse();
});

it('flags contracts whose termination deadline falls within the reminder lead', function () {
    $faellig = Contract::factory()->dueForTermination()->make();
    $spaeter = Contract::factory()->make([
        'end_date' => now()->addYears(2)->toDateString(),
        'notice_period_days' => 90,
        'reminder_lead_days' => 60,
    ]);
    $gekuendigt = Contract::factory()->dueForTermination()->make(['status' => ContractStatus::Terminated]);

    expect($faellig->needsTerminationReminder())->toBeTrue();
    expect($spaeter->needsTerminationReminder())->toBeFalse();
    expect($gekuendigt->needsTerminationReminder())->toBeFalse();
});

it('computes yearly costs from the billing cycle', function () {
    expect(Contract::factory()->make(['amount' => 100, 'billing_cycle' => BillingCycle::Monthly])->yearlyAmount())->toBe(1200.0);
    expect(Contract::factory()->make(['amount' => 100, 'billing_cycle' => BillingCycle::Quarterly])->yearlyAmount())->toBe(400.0);
    expect(Contract::factory()->make(['amount' => 100, 'billing_cycle' => BillingCycle::Yearly])->yearlyAmount())->toBe(100.0);
    expect(Contract::factory()->make(['amount' => 100, 'billing_cycle' => BillingCycle::OneTime])->yearlyAmount())->toBe(0.0);
    expect(Contract::factory()->make(['amount' => null])->yearlyAmount())->toBe(0.0);
});

it('links a contract to a ticket via the processing modal', function () {
    loginAsAdminInFmPanel();

    $contract = Contract::factory()->create();
    $ticket = Ticket::factory()->create();

    Livewire::test(ListTickets::class)
        ->callAction(TestAction::make('process')->table($ticket), data: [
            'status' => $ticket->status->value,
            'contract_id' => $contract->getKey(),
        ])
        ->assertHasNoActionErrors();

    expect($ticket->fresh()->contract_id)->toBe($contract->getKey());
    expect($contract->tickets()->count())->toBe(1);
});

it('links a contract to a maintenance plan', function () {
    loginAsAdminInFmPanel();

    $contract = Contract::factory()->create();
    $plan = MaintenancePlan::factory()->create(['contract_id' => $contract->getKey()]);

    expect($plan->contract->getKey())->toBe($contract->getKey());
    expect($contract->maintenancePlans()->count())->toBe(1);
});

it('sends termination reminders to Admins and the Verträge role once per contract', function () {
    $admin = loginAsAdmin();

    $organization = defaultOrganization();

    $vertragsUser = User::factory()->create(['email' => 'vertraege@example.com']);
    $vertragsUser->organizations()->syncWithoutDetaching($organization);
    $vertragsUser->assignRole(Role::query()->where('name', 'Verträge')->where('organization_id', $organization->getKey())->firstOrFail());

    $normalerUser = User::factory()->create(['email' => 'normal@example.com']);
    $normalerUser->organizations()->syncWithoutDetaching($organization);
    $normalerUser->assignRole('User');

    $faellig = Contract::factory()->dueForTermination()->create();
    Contract::factory()->create(['end_date' => now()->addYears(2)->toDateString()]);

    $this->artisan('contracts:send-reminders')->assertSuccessful();

    $notifiedIds = DB::table('notifications')->pluck('notifiable_id');

    expect($notifiedIds)->toContain($admin->getKey());
    expect($notifiedIds)->toContain($vertragsUser->getKey());
    expect($notifiedIds)->not->toContain($normalerUser->getKey());
    expect($faellig->fresh()->reminded_at)->not->toBeNull();

    $anzahl = DB::table('notifications')->count();

    $this->artisan('contracts:send-reminders')->assertSuccessful();

    expect(DB::table('notifications')->count())->toBe($anzahl);
});

it('does not remind for contracts of another organization twice or cross-tenant', function () {
    loginAsAdmin();

    $fremdeOrganisation = Organization::factory()->create();
    $fremderVertrag = inOrganization($fremdeOrganisation, fn (): Contract => Contract::factory()->dueForTermination()->create());

    $this->artisan('contracts:send-reminders')->assertSuccessful();

    expect(DB::table('notifications')->count())->toBe(0);
    expect($fremderVertrag->fresh()->reminded_at)->not->toBeNull();
});
