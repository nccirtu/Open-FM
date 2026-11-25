<?php

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Filament\Fm\Resources\TicketResource\Pages\EditTicket;
use App\Filament\Fm\Resources\TicketResource\RelationManagers\RepliesRelationManager;
use App\Filament\Fm\Widgets\TicketStatsWidget;
use App\Filament\Portal\Resources\TicketResource as PortalTicketResource;
use App\Filament\Portal\Resources\TicketResource\Pages\CreateTicket as PortalCreateTicket;
use App\Filament\Portal\Resources\TicketResource\Pages\ListTickets as PortalListTickets;
use App\Filament\Portal\Resources\TicketResource\Pages\ViewTicket;
use App\Filament\Portal\Resources\TicketResource\RelationManagers\PublicRepliesRelationManager;
use App\Models\Property;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketAutomationRule;
use App\Models\TicketReply;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

function loginAsMelderInPortalPanel(): User
{
    test()->seed(RolesAndPermissionsSeeder::class);

    $organization = defaultOrganization();
    setPermissionsTeamId($organization->getKey());

    $melder = User::factory()->create(['email' => 'mieter@example.com']);
    $melder->organizations()->attach($organization);
    $melder->assignRole(Role::query()->where('name', 'Melder')->where('organization_id', $organization->getKey())->firstOrFail());

    test()->actingAs($melder);

    Filament::setTenant($organization);
    Filament::setCurrentPanel('portal');
    Filament::getPanel('portal')->boot();

    return $melder;
}

it('adds replies and internal notes through the FM relation manager', function () {
    loginAsAdminInFmPanel();

    $ticket = Ticket::factory()->create();

    Livewire::test(RepliesRelationManager::class, [
        'ownerRecord' => $ticket,
        'pageClass' => EditTicket::class,
    ])
        ->callAction(TestAction::make('create')->table(), data: [
            'message' => 'Techniker ist informiert.',
            'is_internal_note' => true,
        ])
        ->assertHasNoActionErrors();

    $reply = $ticket->replies()->firstOrFail();

    expect($reply->is_internal_note)->toBeTrue();
    expect($reply->user_id)->toBe(auth()->id());
    expect($reply->organization_id)->toBe(defaultOrganization()->getKey());
});

it('notifies the reporter on public replies but not on internal notes', function () {
    loginAsAdminInFmPanel();

    $melder = User::factory()->create(['email' => 'melder@example.com']);
    $melder->organizations()->attach(defaultOrganization());

    $ticket = Ticket::factory()->create(['reported_by' => $melder->getKey()]);

    TicketReply::factory()->create(['ticket_id' => $ticket->id, 'is_internal_note' => true]);
    expect($melder->notifications()->count())->toBe(0);

    TicketReply::factory()->create(['ticket_id' => $ticket->id, 'is_internal_note' => false]);
    expect($melder->notifications()->count())->toBe(1);
});

it('marks tickets as unread on new activity and as read when opened', function () {
    loginAsAdminInFmPanel();

    $ticket = Ticket::factory()->create();

    expect($ticket->fresh()->isUnread())->toBeTrue();

    Livewire::test(EditTicket::class, ['record' => $ticket->getRouteKey()]);

    expect($ticket->fresh()->isUnread())->toBeFalse();

    $this->travel(1)->minutes();

    TicketReply::factory()->create(['ticket_id' => $ticket->id]);

    expect($ticket->fresh()->isUnread())->toBeTrue();
});

it('logs activities for creation and status changes via spatie activitylog', function () {
    loginAsAdminInFmPanel();

    $ticket = Ticket::factory()->create();
    $ticket->update(['status' => TicketStatus::InProgress]);

    $events = Activity::query()
        ->where('subject_type', Ticket::class)
        ->where('subject_id', $ticket->getKey())
        ->pluck('event')
        ->all();

    expect($events)->toContain('created');
    expect($events)->toContain('updated');

    $update = Activity::query()
        ->where('subject_type', Ticket::class)
        ->where('subject_id', $ticket->getKey())
        ->where('event', 'updated')
        ->firstOrFail();

    expect($update->attribute_changes['attributes']['status'])->toBe(TicketStatus::InProgress->value);
    expect($update->causer_id)->toBe(auth()->id());
});

it('shows ticket statistics in the FM dashboard widget', function () {
    loginAsAdminInFmPanel();

    Ticket::factory()->create(['priority' => TicketPriority::P1]);
    Ticket::factory()->create(['status' => TicketStatus::Resolved, 'resolved_at' => now()]);
    Ticket::factory()->create(['due_at' => now()->subDay()]);

    Livewire::test(TicketStatsWidget::class)
        ->assertSee('Offene Störungen')
        ->assertSee('Offene Notfälle (P1)')
        ->assertSee('Überfällig')
        ->assertSee('Erledigt (30 Tage)');
});

it('applies automation rules on ticket creation', function () {
    $admin = loginAsAdminInFmPanel();

    TicketAutomationRule::factory()->create([
        'event' => TicketAutomationRule::EVENT_CREATED,
        'conditions' => [['field' => 'category', 'value' => TicketCategory::Refrigeration->value]],
        'actions' => [
            ['type' => 'assign_to', 'value' => (string) $admin->getKey()],
            ['type' => 'set_priority', 'value' => TicketPriority::P1->value],
        ],
    ]);

    $treffer = Ticket::factory()->create(['category' => TicketCategory::Refrigeration]);
    $kein_treffer = Ticket::factory()->create(['category' => TicketCategory::Cleaning]);

    expect($treffer->fresh()->assigned_to)->toBe($admin->getKey());
    expect($treffer->fresh()->priority)->toBe(TicketPriority::P1);
    expect(Activity::query()
        ->where('subject_type', Ticket::class)
        ->where('subject_id', $treffer->getKey())
        ->where('event', 'automation')
        ->count())->toBe(1);

    expect($kein_treffer->fresh()->assigned_to)->toBeNull();
    expect($kein_treffer->fresh()->priority)->toBe(TicketPriority::P3);
});

it('does not recurse when an automation adds an internal note on replies', function () {
    loginAsAdminInFmPanel();

    TicketAutomationRule::factory()->create([
        'event' => TicketAutomationRule::EVENT_REPLY_ADDED,
        'conditions' => [],
        'actions' => [['type' => 'add_internal_note', 'note' => 'Automatische Notiz']],
    ]);

    $ticket = Ticket::factory()->create();

    TicketReply::factory()->create(['ticket_id' => $ticket->id]);

    expect($ticket->replies()->count())->toBe(2);
    expect($ticket->replies()->where('is_internal_note', true)->count())->toBe(1);
});

it('lets a Melder report and track only their own tickets in the portal', function () {
    $melder = loginAsMelderInPortalPanel();

    $property = Property::factory()->create();

    Livewire::test(PortalCreateTicket::class)
        ->fillForm([
            'property_id' => $property->id,
            'title' => 'Licht defekt',
            'category' => TicketCategory::Electrical->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $eigenes = Ticket::query()->firstOrFail();
    expect($eigenes->reported_by)->toBe($melder->getKey());
    expect($eigenes->priority)->toBe(TicketPriority::P3);

    $fremdes = Ticket::factory()->create(['reported_by' => User::factory()->create()->getKey()]);

    Livewire::test(PortalListTickets::class)
        ->assertCanSeeTableRecords([$eigenes])
        ->assertCanNotSeeTableRecords([$fremdes]);

    $this->get(PortalTicketResource::getUrl('view', ['record' => $fremdes], panel: 'portal'))
        ->assertNotFound();
});

it('hides internal notes from the portal history but allows public replies', function () {
    $melder = loginAsMelderInPortalPanel();

    $ticket = Ticket::factory()->create(['reported_by' => $melder->getKey()]);

    $intern = TicketReply::factory()->create(['ticket_id' => $ticket->id, 'is_internal_note' => true]);
    $oeffentlich = TicketReply::factory()->create(['ticket_id' => $ticket->id, 'is_internal_note' => false]);

    Livewire::test(PublicRepliesRelationManager::class, [
        'ownerRecord' => $ticket,
        'pageClass' => ViewTicket::class,
    ])
        ->assertCanSeeTableRecords([$oeffentlich])
        ->assertCanNotSeeTableRecords([$intern])
        ->callAction(TestAction::make('create')->table(), data: ['message' => 'Ist immer noch kaputt.'])
        ->assertHasNoActionErrors();

    expect($ticket->replies()->where('is_internal_note', false)->count())->toBe(2);
});
