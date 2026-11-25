<?php

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Filament\Fm\Resources\TicketResource\Pages\CreateTicket;
use App\Filament\Fm\Resources\TicketResource\Pages\EditTicket;
use App\Filament\Fm\Resources\TicketResource\Pages\ListTickets;
use App\Filament\Fm\Resources\TicketResource\Pages\ViewTicket;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

it('creates a ticket with defaults, reporter and tenant', function () {
    $admin = loginAsAdminInFmPanel();

    $property = Property::factory()->create();

    Livewire::test(CreateTicket::class)
        ->fillForm([
            'property_id' => $property->id,
            'title' => 'Kühlregal ausgefallen',
            'description' => 'Kühlregal im VK kühlt nicht mehr.',
            'category' => TicketCategory::Refrigeration->value,
            'priority' => TicketPriority::P1->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $ticket = Ticket::query()->firstOrFail();

    expect($ticket->organization_id)->toBe(defaultOrganization()->getKey());
    expect($ticket->reported_by)->toBe($admin->getKey());
    expect($ticket->status)->toBe(TicketStatus::Reported);
    expect($ticket->priority)->toBe(TicketPriority::P1);
    expect($ticket->property_id)->toBe($property->id);
});

it('requires property, title, category and priority', function () {
    loginAsAdminInFmPanel();

    Livewire::test(CreateTicket::class)
        ->fillForm([
            'property_id' => null,
            'title' => '',
            'category' => null,
            'priority' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'property_id' => 'required',
            'title' => 'required',
            'category' => 'required',
            'priority' => 'required',
        ]);
});

it('only lists tickets of the current tenant', function () {
    loginAsAdminInFmPanel();

    $eigenesTicket = Ticket::factory()->create();

    $fremdeOrganisation = Organization::factory()->create();
    $fremdesTicket = inOrganization($fremdeOrganisation, fn (): Ticket => Ticket::factory()->create());

    expect($fremdesTicket->organization_id)->toBe($fremdeOrganisation->getKey());

    Livewire::test(ListTickets::class)
        ->assertCanSeeTableRecords([$eigenesTicket])
        ->assertCanNotSeeTableRecords([$fremdesTicket]);
});

it('sets resolved_at when the status changes to resolved via the processing modal', function () {
    loginAsAdminInFmPanel();

    $ticket = Ticket::factory()->create();

    expect($ticket->resolved_at)->toBeNull();

    Livewire::test(ListTickets::class)
        ->callAction(TestAction::make('process')->table($ticket), data: [
            'status' => TicketStatus::Resolved->value,
        ])
        ->assertHasNoActionErrors();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Resolved);
    expect($ticket->fresh()->resolved_at)->not->toBeNull();
});

it('notifies the assignee on assignment via the processing modal', function () {
    loginAsAdminInFmPanel();

    $techniker = User::factory()->create(['email' => 'techniker@example.com']);
    $techniker->organizations()->attach(defaultOrganization());

    $ticket = Ticket::factory()->create();

    Livewire::test(ListTickets::class)
        ->callAction(TestAction::make('process')->table($ticket), data: [
            'status' => $ticket->status->value,
            'assigned_to' => $techniker->getKey(),
        ])
        ->assertHasNoActionErrors();

    expect($ticket->fresh()->assigned_to)->toBe($techniker->getKey());
    expect($techniker->notifications()->count())->toBe(1);
    expect($techniker->notifications()->first()->data['title'])->toBe('Störung zugewiesen');
});

it('shows only the report form on the edit page without processing fields or relations', function () {
    loginAsAdminInFmPanel();

    $ticket = Ticket::factory()->create();

    $page = Livewire::test(EditTicket::class, ['record' => $ticket->getRouteKey()]);

    $page->assertFormFieldExists('title')
        ->assertFormFieldDoesNotExist('status')
        ->assertFormFieldDoesNotExist('assigned_to')
        ->assertDontSee(__('tickets.sections.processing'))
        ->assertDontSee(__('tickets.replies.title'));
});

it('shows processing details and the reply relation on the view page', function () {
    loginAsAdminInFmPanel();

    $ticket = Ticket::factory()->create(['title' => 'Rolltor klemmt']);

    Livewire::test(ViewTicket::class, ['record' => $ticket->getRouteKey()])
        ->assertSee('Rolltor klemmt')
        ->assertSee(__('tickets.sections.processing'))
        ->assertSee(__('tickets.activities.title'));

    expect($ticket->fresh()->seen_at)->not->toBeNull();
});

it('does not notify when assigning a ticket to yourself', function () {
    $admin = loginAsAdminInFmPanel();

    Ticket::factory()->create(['assigned_to' => $admin->getKey()]);

    expect($admin->notifications()->count())->toBe(0);
});

it('grants ticket permissions to FM but not to Melder', function () {
    loginAsAdminInFmPanel();

    $organization = defaultOrganization();

    $fmUser = User::factory()->create(['email' => 'fm@example.com']);
    $fmUser->organizations()->attach($organization);
    $fmUser->assignRole(Role::query()->where('name', 'FM')->where('organization_id', $organization->getKey())->firstOrFail());

    $melder = User::factory()->create(['email' => 'mieter@example.com']);
    $melder->organizations()->attach($organization);
    $melder->assignRole(Role::query()->where('name', 'Melder')->where('organization_id', $organization->getKey())->firstOrFail());

    expect($fmUser->can('viewAny', Ticket::class))->toBeTrue();
    expect($fmUser->can('create', Ticket::class))->toBeTrue();

    expect($melder->can('viewAny', Ticket::class))->toBeTrue();
    expect($melder->can('create', Ticket::class))->toBeTrue();
    expect($melder->can('update', Ticket::factory()->create()))->toBeFalse();
    expect($melder->can('deleteAny', Ticket::class))->toBeFalse();
});
