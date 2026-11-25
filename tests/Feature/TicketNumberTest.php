<?php

use App\Filament\Fm\Resources\TicketResource\Pages\ListTickets;
use App\Models\Organization;
use App\Models\Ticket;
use App\Services\EntityFolders;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

it('numbers tickets sequentially per organization', function () {
    loginAsAdmin();

    $first = Ticket::factory()->create();
    $second = Ticket::factory()->create();

    $foreign = inOrganization(Organization::factory()->create(), fn (): Ticket => Ticket::factory()->create());

    expect($first->ticket_number)->toBe(1)
        ->and($second->ticket_number)->toBe(2)
        ->and($foreign->ticket_number)->toBe(1);
});

it('continues after an explicitly given ticket number', function () {
    loginAsAdmin();

    Ticket::factory()->create(['ticket_number' => 50]);

    expect(Ticket::factory()->create()->ticket_number)->toBe(51);
});

it('shows the ticket number in the ticket list', function () {
    loginAsAdminInFmPanel();

    $ticket = Ticket::factory()->create();

    Livewire::test(ListTickets::class)
        ->assertCanSeeTableRecords([$ticket])
        ->assertTableColumnStateSet('ticket_number', 1, $ticket);
});

it('names the document folder of a ticket by its number', function () {
    loginAsAdmin();

    $ticket = Ticket::factory()->create(['title' => 'Heizung kalt']);

    expect(EntityFolders::nameFor($ticket))->toBe('#1 Heizung kalt');
});

it('exposes, filters and sorts by the ticket number in the API', function () {
    Filament::setTenant(Organization::factory()->create(), isQuiet: true);
    actingAsApiUser(['tickets:read']);

    Ticket::factory()->count(3)->create();

    $this->getJson('/api/v1/tickets?filter[ticket_number]=2')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.ticket_number', 2);

    $this->getJson('/api/v1/tickets?sort=-ticket_number')
        ->assertOk()
        ->assertJsonPath('data.0.ticket_number', 3);
});

it('numbers existing tickets by creation time when migrating', function () {
    loginAsAdmin();

    $late = Ticket::factory()->create(['created_at' => now()->subDay()]);
    $early = Ticket::factory()->create(['created_at' => now()->subWeek()]);
    $foreign = inOrganization(Organization::factory()->create(), fn (): Ticket => Ticket::factory()->create());

    DB::table('tickets')->update(['ticket_number' => null]);

    (require database_path('migrations/2026_10_03_203604_add_ticket_number_to_tickets_table.php'))->up();

    expect($early->fresh()->ticket_number)->toBe(1)
        ->and($late->fresh()->ticket_number)->toBe(2)
        ->and($foreign->fresh()->ticket_number)->toBe(1);
});
