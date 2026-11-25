<?php

use App\Enums\TicketStatus;
use App\Filament\Portal\Pages\PortalDashboard;
use App\Filament\Portal\Resources\AssetResource;
use App\Filament\Portal\Resources\TicketResource;
use App\Filament\Portal\Resources\TicketResource\Pages\ListTickets;
use App\Filament\Portal\Resources\TicketResource\Pages\ViewTicket;
use App\Filament\Portal\Resources\TicketResource\RelationManagers\PublicRepliesRelationManager;
use App\Filament\Portal\Widgets\AwaitingConfirmationWidget;
use App\Filament\Portal\Widgets\MyPropertiesWidget;
use App\Filament\Portal\Widgets\MyReportsStatsWidget;
use App\Filament\Portal\Widgets\OpenReportsWidget;
use App\Filament\Portal\Widgets\ReportNewsWidget;
use App\Filament\Portal\Widgets\ReportsByCategoryChartWidget;
use App\Filament\Portal\Widgets\ReportsByStatusChartWidget;
use App\Filament\Portal\Widgets\ReportsTrendChartWidget;
use App\Models\Property;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use App\Support\Portal\TicketStatusHistory;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;
use Livewire\Livewire;

/**
 * @param  list<Property>  $assignedProperties
 */
function loginAsPortalMelder(array $assignedProperties = []): User
{
    test()->seed(RolesAndPermissionsSeeder::class);

    $organization = defaultOrganization();

    setPermissionsTeamId($organization->getKey());

    $melder = User::factory()->create(['email' => 'hausmeister@example.com']);
    $melder->organizations()->attach($organization);
    $melder->assignRole(Role::query()->where('name', 'Melder')->where('organization_id', $organization->getKey())->firstOrFail());
    $melder->properties()->attach(collect($assignedProperties)->map->getKey()->all());

    test()->actingAs($melder);

    Filament::setTenant($organization);
    Filament::setCurrentPanel('portal');
    Filament::getPanel('portal')->boot();

    return $melder;
}

/**
 * @param  array<string, mixed>  $attributes
 */
function reportedTicket(User $reporter, Property $property, array $attributes = []): Ticket
{
    return Ticket::factory()->create([
        'property_id' => $property->getKey(),
        'reported_by' => $reporter->getKey(),
        ...$attributes,
    ]);
}

function callPortalWidgetMethod(object $widget, string $method): mixed
{
    return Closure::bind(fn (): mixed => $this->{$method}(), $widget, $widget::class)();
}

it('uses the top navigation with groups like the other panels', function () {
    loginAsPortalMelder();

    $panel = Filament::getPanel('portal');

    expect($panel->hasTopNavigation())->toBeTrue()
        ->and(collect($panel->getNavigationGroups())->map(fn ($group) => $group->getLabel())->values()->all())
        ->toBe([__('portal.navigation_group'), __('app.nav_groups.objects'), __('form-builder.navigation.group')])
        ->and(TicketResource::getNavigationGroup())->toBe(__('portal.navigation_group'))
        ->and(AssetResource::getNavigationGroup())->toBe(__('app.nav_groups.objects'))
        ->and(collect($panel->getNavigationItems())->map->getLabel()->all())->toContain(__('tickets.portal.report'));
});

it('shows own tickets plus those on assigned properties, never others', function () {
    $assigned = Property::factory()->create();
    $other = Property::factory()->create();

    $melder = loginAsPortalMelder([$assigned]);
    $kollege = User::factory()->create();

    $eigenes = reportedTicket($melder, $assigned);
    $eigenesAmFremdenObjekt = reportedTicket($melder, $other);
    $kollegeAmZugewiesenen = reportedTicket($kollege, $assigned);
    $kollegeAmFremden = reportedTicket($kollege, $other);

    $visible = Ticket::query()->visibleInPortal($melder)->pluck('id')->all();

    expect($visible)->toContain($eigenes->getKey(), $eigenesAmFremdenObjekt->getKey(), $kollegeAmZugewiesenen->getKey())
        ->not->toContain($kollegeAmFremden->getKey());
});

it('limits users without assigned properties to their own tickets', function () {
    $property = Property::factory()->create();

    $melder = loginAsPortalMelder();
    $kollege = User::factory()->create();

    $eigenes = reportedTicket($melder, $property);
    $fremdes = reportedTicket($kollege, $property);

    expect(Ticket::query()->visibleInPortal($melder)->pluck('id')->all())
        ->toBe([$eigenes->getKey()])
        ->and(Ticket::query()->onAssignedProperties($melder)->exists())->toBeFalse()
        ->and(Ticket::query()->whereKey($fremdes->getKey())->visibleInPortal($melder)->exists())->toBeFalse();
});

it('keeps own tickets visible after the property assignment is removed', function () {
    $property = Property::factory()->create();

    $melder = loginAsPortalMelder([$property]);

    $eigenes = reportedTicket($melder, $property);
    $melder->properties()->detach();

    Livewire::test(ListTickets::class)
        ->set('activeTab', 'all')
        ->assertCanSeeTableRecords([$eigenes]);
});

it('lists own tickets in tabs and offers all tickets of assigned properties as an extra tab', function () {
    $assigned = Property::factory()->create();
    $other = Property::factory()->create();

    $melder = loginAsPortalMelder([$assigned]);
    $kollege = User::factory()->create();

    $offen = reportedTicket($melder, $assigned, ['status' => TicketStatus::InProgress]);
    $wartet = reportedTicket($melder, $assigned, ['status' => TicketStatus::Resolved]);
    $geschlossen = reportedTicket($melder, $assigned, ['status' => TicketStatus::Closed]);
    $kollegeAmZugewiesenen = reportedTicket($kollege, $assigned);
    $kollegeAmFremden = reportedTicket($kollege, $other);

    $list = Livewire::test(ListTickets::class);

    expect(array_keys($list->instance()->getTabs()))->toBe(['open', 'awaiting', 'closed', 'all', 'properties']);

    $list->assertCanSeeTableRecords([$offen])
        ->assertCanNotSeeTableRecords([$wartet, $geschlossen, $kollegeAmZugewiesenen, $kollegeAmFremden]);

    $list->set('activeTab', 'awaiting')->assertCanSeeTableRecords([$wartet])->assertCanNotSeeTableRecords([$offen]);
    $list->set('activeTab', 'closed')->assertCanSeeTableRecords([$geschlossen])->assertCanNotSeeTableRecords([$offen]);
    $list->set('activeTab', 'all')->assertCanSeeTableRecords([$offen, $wartet, $geschlossen])->assertCanNotSeeTableRecords([$kollegeAmZugewiesenen]);
    $list->set('activeTab', 'properties')
        ->assertCanSeeTableRecords([$offen, $kollegeAmZugewiesenen])
        ->assertCanNotSeeTableRecords([$kollegeAmFremden]);
});

it('offers no property tab without assigned properties', function () {
    loginAsPortalMelder();

    expect(array_keys(Livewire::test(ListTickets::class)->instance()->getTabs()))->toBe(['open', 'awaiting', 'closed', 'all']);
});

it('offers only visible properties in the property filter', function () {
    $assigned = Property::factory()->create();
    $unrelated = Property::factory()->create();

    loginAsPortalMelder([$assigned]);

    expect(TicketResource::filterableProperties())->toHaveKey($assigned->getKey())
        ->not->toHaveKey($unrelated->getKey());
});

it('shows tickets of colleagues read-only without replies or reporter actions', function () {
    $property = Property::factory()->create();

    $melder = loginAsPortalMelder([$property]);
    $kollege = User::factory()->create();

    $fremdes = reportedTicket($kollege, $property, ['status' => TicketStatus::Resolved]);
    $eigenes = reportedTicket($melder, $property, ['status' => TicketStatus::Resolved]);

    Livewire::test(ViewTicket::class, ['record' => $fremdes->getRouteKey()])
        ->assertSuccessful()
        ->assertActionHidden('confirmResolution')
        ->assertActionHidden('reopen');

    Livewire::test(ViewTicket::class, ['record' => $eigenes->getRouteKey()])
        ->assertActionVisible('confirmResolution');

    expect(PublicRepliesRelationManager::canViewForRecord($fremdes, ViewTicket::class))->toBeFalse()
        ->and(PublicRepliesRelationManager::canViewForRecord($eigenes, ViewTicket::class))->toBeTrue();
});

it('records the status history of a ticket', function () {
    $property = Property::factory()->create();

    $melder = loginAsPortalMelder([$property]);
    $ticket = reportedTicket($melder, $property);

    $bearbeiter = User::factory()->create();
    $bearbeiter->organizations()->attach(defaultOrganization());
    $bearbeiter->assignRole('Admin');
    test()->actingAs($bearbeiter);

    $ticket->update(['status' => TicketStatus::InProgress, 'title' => 'Umbenannt']);

    $history = TicketStatusHistory::forTicket($ticket);

    expect($history)->toHaveCount(2)
        ->and($history[0]['from'])->toBeNull()
        ->and($history[0]['to'])->toBe(TicketStatus::Reported)
        ->and($history[1]['from'])->toBe(TicketStatus::Reported)
        ->and($history[1]['to'])->toBe(TicketStatus::InProgress);

    test()->actingAs($melder);

    Livewire::test(ViewTicket::class, ['record' => $ticket->getRouteKey()])
        ->assertSuccessful()
        ->assertSee(TicketStatus::Reported->getLabel().' → '.TicketStatus::InProgress->getLabel());
});

it('counts only own tickets in the dashboard stats', function () {
    $property = Property::factory()->create();

    $melder = loginAsPortalMelder([$property]);
    $kollege = User::factory()->create();

    reportedTicket($melder, $property, ['status' => TicketStatus::Reported]);
    reportedTicket($melder, $property, ['status' => TicketStatus::InProgress]);
    reportedTicket($kollege, $property, ['status' => TicketStatus::Reported]);

    $erledigt = reportedTicket($melder, $property, ['status' => TicketStatus::Resolved]);
    $erledigt->forceFill(['created_at' => now()->subHours(5), 'resolved_at' => now()])->saveQuietly();

    Livewire::test(MyReportsStatsWidget::class)
        ->assertSuccessful()
        ->assertSee(__('portal.stats.awaiting_hint'))
        ->assertSee(__('portal.stats.hours', ['count' => '5,0']));

    $stats = collect(callPortalWidgetMethod(new MyReportsStatsWidget, 'getStats'))->map(fn ($stat) => $stat->getValue())->all();

    expect($stats)->toBe([2, 1, 1, '5,0 Std.']);
});

it('lists only own open tickets in the open reports widget', function () {
    $property = Property::factory()->create();

    $melder = loginAsPortalMelder([$property]);
    $kollege = User::factory()->create();

    $offen = reportedTicket($melder, $property, ['status' => TicketStatus::InProgress]);
    $geschlossen = reportedTicket($melder, $property, ['status' => TicketStatus::Closed]);
    $fremd = reportedTicket($kollege, $property);

    Livewire::test(OpenReportsWidget::class)
        ->assertCanSeeTableRecords([$offen])
        ->assertCanNotSeeTableRecords([$geschlossen, $fremd]);
});

it('confirms a resolved ticket straight from the awaiting widget', function () {
    $property = Property::factory()->create();

    $melder = loginAsPortalMelder([$property]);
    $kollege = User::factory()->create();

    $wartet = reportedTicket($melder, $property, ['status' => TicketStatus::Resolved]);
    $fremd = reportedTicket($kollege, $property, ['status' => TicketStatus::Resolved]);

    Livewire::test(AwaitingConfirmationWidget::class)
        ->assertCanSeeTableRecords([$wartet])
        ->assertCanNotSeeTableRecords([$fremd])
        ->callAction(TestAction::make('confirmResolution')->table($wartet), data: ['rating' => 4])
        ->assertHasNoActionErrors();

    expect($wartet->refresh()->status)->toBe(TicketStatus::Closed)
        ->and($wartet->rating)->toBe(4);
});

it('reopens a resolved ticket straight from the awaiting widget', function () {
    $property = Property::factory()->create();

    $melder = loginAsPortalMelder([$property]);
    $wartet = reportedTicket($melder, $property, ['status' => TicketStatus::Resolved, 'resolved_at' => now()]);

    Livewire::test(AwaitingConfirmationWidget::class)
        ->callAction(TestAction::make('reopen')->table($wartet), data: ['reason' => 'Tropft weiter.'])
        ->assertHasNoActionErrors();

    expect($wartet->refresh()->status)->toBe(TicketStatus::Reported);
});

it('shows public replies and status changes of own tickets as news, never internal notes', function () {
    $property = Property::factory()->create();

    $melder = loginAsPortalMelder([$property]);
    $kollege = User::factory()->create();

    $eigenes = reportedTicket($melder, $property, ['title' => 'Heizung kalt']);
    $fremdes = reportedTicket($kollege, $property, ['title' => 'Tür klemmt']);

    $bearbeiter = User::factory()->create(['name' => 'Frau Wartung']);
    $bearbeiter->organizations()->attach(defaultOrganization());
    $bearbeiter->assignRole('Admin');
    test()->actingAs($bearbeiter);

    TicketReply::factory()->create(['ticket_id' => $eigenes->getKey(), 'user_id' => $bearbeiter->getKey(), 'message' => 'Techniker kommt morgen.', 'is_internal_note' => false]);
    TicketReply::factory()->create(['ticket_id' => $eigenes->getKey(), 'user_id' => $bearbeiter->getKey(), 'message' => 'Intern: Budget prüfen.', 'is_internal_note' => true]);
    TicketReply::factory()->create(['ticket_id' => $fremdes->getKey(), 'user_id' => $bearbeiter->getKey(), 'message' => 'Antwort an Kollegen.', 'is_internal_note' => false]);

    $eigenes->update(['status' => TicketStatus::InProgress]);
    $fremdes->update(['status' => TicketStatus::InProgress]);

    test()->actingAs($melder);

    Livewire::test(ReportNewsWidget::class)
        ->assertSuccessful()
        ->assertSee('Heizung kalt')
        ->assertSee('Frau Wartung: Techniker kommt morgen.')
        ->assertSee(TicketStatus::Reported->getLabel().' → '.TicketStatus::InProgress->getLabel())
        ->assertDontSee('Intern: Budget prüfen.')
        ->assertDontSee('Tür klemmt')
        ->assertDontSee('Antwort an Kollegen.');
});

it('lists assigned properties with the open tickets the user may see', function () {
    $assigned = Property::factory()->create();
    $unrelated = Property::factory()->create();

    $melder = loginAsPortalMelder([$assigned]);
    $kollege = User::factory()->create();

    reportedTicket($melder, $assigned, ['status' => TicketStatus::Reported]);
    reportedTicket($kollege, $assigned, ['status' => TicketStatus::InProgress]);
    reportedTicket($kollege, $assigned, ['status' => TicketStatus::Closed]);
    reportedTicket($kollege, $unrelated);

    Livewire::test(MyPropertiesWidget::class)
        ->assertCanSeeTableRecords([$assigned])
        ->assertCanNotSeeTableRecords([$unrelated])
        ->assertTableColumnStateSet('open_reports_count', 2, $assigned);
});

it('lists all tenant properties for users without assignment but counts only their own tickets', function () {
    $a = Property::factory()->create();
    $b = Property::factory()->create();

    $melder = loginAsPortalMelder();
    $kollege = User::factory()->create();

    reportedTicket($melder, $a);
    reportedTicket($kollege, $a);

    Livewire::test(MyPropertiesWidget::class)
        ->assertCanSeeTableRecords([$a, $b])
        ->assertTableColumnStateSet('open_reports_count', 1, $a)
        ->assertTableColumnStateSet('open_reports_count', 0, $b);
});

it('draws the portal charts from own tickets only', function () {
    $property = Property::factory()->create();

    $melder = loginAsPortalMelder([$property]);
    $kollege = User::factory()->create();

    reportedTicket($melder, $property, ['status' => TicketStatus::Reported]);
    reportedTicket($melder, $property, ['status' => TicketStatus::Closed]);
    reportedTicket($kollege, $property, ['status' => TicketStatus::Reported]);

    foreach ([ReportsByStatusChartWidget::class, ReportsTrendChartWidget::class, ReportsByCategoryChartWidget::class] as $chart) {
        Livewire::test($chart)->assertSuccessful();
    }

    $byStatus = callPortalWidgetMethod(new ReportsByStatusChartWidget, 'getData');
    $trend = callPortalWidgetMethod(new ReportsTrendChartWidget, 'getData');

    expect(array_sum($byStatus['datasets'][0]['data']))->toBe(2)
        ->and(array_sum($trend['datasets'][0]['data']))->toBe(2);
});

it('renders the portal dashboard with all widgets and packs the default layout into full rows', function () {
    $property = Property::factory()->create();

    loginAsPortalMelder([$property]);

    $dashboard = Livewire::test(PortalDashboard::class)
        ->assertSuccessful()
        ->assertActionExists('report')
        ->assertSeeLivewire(MyReportsStatsWidget::class)
        ->assertSeeLivewire(OpenReportsWidget::class)
        ->assertSeeLivewire(ReportsByStatusChartWidget::class)
        ->assertSeeLivewire(AwaitingConfirmationWidget::class)
        ->assertSeeLivewire(ReportNewsWidget::class)
        ->assertSeeLivewire(MyPropertiesWidget::class)
        ->assertSeeLivewire(ReportsTrendChartWidget::class)
        ->assertSeeLivewire(ReportsByCategoryChartWidget::class);

    $rows = collect($dashboard->get('widgetGridLayout'))->groupBy('y');

    expect($rows)->toHaveCount(4);

    $rows->each(fn (Collection $row) => expect($row->sum('w'))->toBe(
        12,
        $row->pluck('widget')->map(fn (string $widget): string => class_basename($widget))->implode(', '),
    ));
});
