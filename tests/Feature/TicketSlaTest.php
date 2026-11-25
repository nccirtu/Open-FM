<?php

use App\Enums\TicketEstimateStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketSuspensionReason;
use App\Filament\Admin\Resources\SlaDefinitionResource\Pages\CreateSlaDefinition;
use App\Filament\Fm\Resources\TicketResource\Pages\ListTickets;
use App\Models\SlaDefinition;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

it('applies SLA deadlines from the tenant definition when a ticket is created', function () {
    loginAsAdminInFmPanel();

    SlaDefinition::factory()->create(['priority' => TicketPriority::P1, 'response_hours' => 4, 'resolution_hours' => 24]);

    $ticket = Ticket::factory()->create(['priority' => TicketPriority::P1]);

    expect($ticket->respond_due_at->toDateTimeString())->toBe($ticket->created_at->addHours(4)->toDateTimeString());
    expect($ticket->resolve_due_at->toDateTimeString())->toBe($ticket->created_at->addHours(24)->toDateTimeString());

    $ohneRegel = Ticket::factory()->create(['priority' => TicketPriority::P3]);

    expect($ohneRegel->respond_due_at)->toBeNull();
});

it('recalculates SLA deadlines when the priority changes', function () {
    loginAsAdminInFmPanel();

    SlaDefinition::factory()->create(['priority' => TicketPriority::P1, 'response_hours' => 4, 'resolution_hours' => 24]);
    SlaDefinition::factory()->create(['priority' => TicketPriority::P2, 'response_hours' => 24, 'resolution_hours' => 72]);

    $ticket = Ticket::factory()->create(['priority' => TicketPriority::P2]);

    $ticket->update(['priority' => TicketPriority::P1]);

    expect($ticket->fresh()->resolve_due_at->toDateTimeString())
        ->toBe($ticket->created_at->addHours(24)->toDateTimeString());
});

it('pauses the SLA clock while a ticket is waiting', function () {
    loginAsAdminInFmPanel();

    SlaDefinition::factory()->create(['priority' => TicketPriority::P1, 'response_hours' => 4, 'resolution_hours' => 24]);

    $ticket = Ticket::factory()->create(['priority' => TicketPriority::P1]);
    $resolveDueBefore = $ticket->resolve_due_at;

    $ticket->update([
        'status' => TicketStatus::Waiting,
        'suspension_reason' => TicketSuspensionReason::AwaitingParts,
    ]);

    expect($ticket->fresh()->suspended_at)->not->toBeNull();
    expect($ticket->fresh()->slaColor())->toBe('gray');
    expect($ticket->fresh()->isSlaBreached())->toBeFalse();

    $this->travel(2)->hours();

    $ticket->fresh()->update(['status' => TicketStatus::InProgress]);

    $aktualisiert = $ticket->fresh();

    expect($aktualisiert->resolve_due_at->toDateTimeString())
        ->toBe($resolveDueBefore->addHours(2)->toDateTimeString());
    expect($aktualisiert->suspended_at)->toBeNull();
    expect($aktualisiert->suspension_reason)->toBeNull();
});

it('records the first agent response but not reporter replies', function () {
    $admin = loginAsAdminInFmPanel();

    $melder = User::factory()->create(['email' => 'melder@example.com']);
    $melder->organizations()->syncWithoutDetaching(defaultOrganization());

    $ticket = Ticket::factory()->create();
    $ticket->forceFill(['reported_by' => $melder->getKey()])->saveQuietly();

    $this->actingAs($melder);
    TicketReply::create(['ticket_id' => $ticket->getKey(), 'message' => 'Immer noch kaputt.']);

    expect($ticket->fresh()->first_response_at)->toBeNull();

    $this->actingAs($admin);
    TicketReply::create(['ticket_id' => $ticket->getKey(), 'message' => 'Wir kümmern uns.']);

    expect($ticket->fresh()->first_response_at)->not->toBeNull();
});

it('escalates SLA breaches once and notifies assignee and admins', function () {
    $admin = loginAsAdminInFmPanel();

    $techniker = User::factory()->create(['email' => 'techniker@example.com']);
    $techniker->organizations()->syncWithoutDetaching(defaultOrganization());

    $verletzt = Ticket::factory()->create(['assigned_to' => $techniker->getKey()]);
    $verletzt->forceFill(['resolve_due_at' => now()->subHour()])->saveQuietly();

    $wartet = Ticket::factory()->create(['status' => TicketStatus::Waiting]);
    $wartet->forceFill(['resolve_due_at' => now()->subHour()])->saveQuietly();

    $this->artisan('tickets:check-sla')->assertSuccessful();

    $notifiedIds = DB::table('notifications')->pluck('notifiable_id');

    expect($notifiedIds)->toContain($admin->getKey());
    expect($notifiedIds)->toContain($techniker->getKey());
    expect($verletzt->fresh()->sla_escalated_at)->not->toBeNull();
    expect($wartet->fresh()->sla_escalated_at)->toBeNull();

    $anzahl = DB::table('notifications')->count();

    $this->artisan('tickets:check-sla')->assertSuccessful();

    expect(DB::table('notifications')->count())->toBe($anzahl);
});

it('creates SLA definitions in the admin panel with unique priority per tenant', function () {
    loginAsAdminInAdminPanel();

    Livewire::test(CreateSlaDefinition::class)
        ->fillForm([
            'organization_id' => defaultOrganization()->getKey(),
            'priority' => TicketPriority::P1->value,
            'response_hours' => 4,
            'resolution_hours' => 24,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(SlaDefinition::query()->count())->toBe(1);
    expect(SlaDefinition::query()->first()->organization_id)->toBe(defaultOrganization()->getKey());

    Livewire::test(CreateSlaDefinition::class)
        ->fillForm([
            'organization_id' => defaultOrganization()->getKey(),
            'priority' => TicketPriority::P1->value,
            'response_hours' => 8,
            'resolution_hours' => 48,
        ])
        ->call('create')
        ->assertHasFormErrors(['priority']);
});

it('sets waiting reason and estimate via the processing modal', function () {
    loginAsAdminInFmPanel();

    $ticket = Ticket::factory()->create();

    Livewire::test(ListTickets::class)
        ->callAction(TestAction::make('process')->table($ticket), data: [
            'status' => TicketStatus::Waiting->value,
            'suspension_reason' => TicketSuspensionReason::AwaitingPartner->value,
            'estimate_status' => TicketEstimateStatus::Requested->value,
            'estimate_amount' => 1500,
        ])
        ->assertHasNoActionErrors();

    $ticket->refresh();

    expect($ticket->status)->toBe(TicketStatus::Waiting);
    expect($ticket->suspension_reason)->toBe(TicketSuspensionReason::AwaitingPartner);
    expect($ticket->estimate_status)->toBe(TicketEstimateStatus::Requested);
    expect((float) $ticket->estimate_amount)->toBe(1500.0);
});
