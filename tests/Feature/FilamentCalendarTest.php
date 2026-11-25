<?php

use Carbon\CarbonImmutable;
use Livewire\Livewire;
use OpenFm\FilamentCalendar\CalendarEvent;
use Tests\Fixtures\TestCalendarWidget;

it('shows the month grid with events, a link and an overflow entry', function () {
    loginAsAdminInFmPanel();
    app()->setLocale('de');

    Livewire::test(TestCalendarWidget::class)
        ->assertSee('Oktober 2026')
        ->assertSee('Wartung Aufzug')
        ->assertSeeHtml('href="https://example.test/wartung"')
        ->assertSee(['Begehung', 'Prüfung'])
        ->assertDontSee('09:00–10:30')
        ->assertSee(__('filament-calendar::calendar.more', ['count' => 1]))
        ->assertSee('Messe');
});

it('limits the range to the visible view and navigates by month and week', function () {
    loginAsAdminInFmPanel();

    $component = Livewire::test(TestCalendarWidget::class);
    [$start, $end] = $component->instance()->getRange();

    expect($start->toDateString())->toBe('2026-09-28')
        ->and($end->toDateString())->toBe('2026-11-01');

    $component->call('setView', 'week');
    [$weekStart, $weekEnd] = $component->instance()->getRange();

    expect([$weekStart->toDateString(), $weekEnd->toDateString()])->toBe(['2026-10-05', '2026-10-11']);

    $component->call('next')->assertSet('date', '2026-10-18')
        ->call('setView', 'month')->call('previous')->assertSet('date', '2026-09-01')
        ->call('setView', 'yearly')->assertSet('calendarView', 'month')
        ->call('openDay', '2026-10-06')->assertSet('calendarView', 'week')->assertSee(['Besprechung', '09:00–10:30']);
});

it('lists only days with events and filters through the filter form', function () {
    loginAsAdminInFmPanel();

    Livewire::test(TestCalendarWidget::class)
        ->call('setView', 'list')
        ->assertSee('Prüfung')
        ->set('filters.kind', 'meetings')
        ->assertSee('Besprechung')
        ->assertDontSee('Wartung Aufzug');
});

it('mounts the widget actions for clicked events and days', function () {
    loginAsAdminInFmPanel();

    Livewire::test(TestCalendarWidget::class)
        ->callAction('open', arguments: ['id' => 'b'])
        ->callAction('open', arguments: ['date' => '2026-10-20'])
        ->assertSet('clicked', ['b', '2026-10-20']);
});

it('resolves filament color names and css colors for events', function () {
    expect(CalendarEvent::make('x')->color('success')->getColorStyle())->toBe('--fc-event-color: var(--success-500);')
        ->and(CalendarEvent::make('y')->color('#2f68cc')->getColorStyle())->toBe('--fc-event-color: #2f68cc;')
        ->and(CalendarEvent::make('z')->start('2026-10-30')->end('2026-11-02')->occursOn(CarbonImmutable::parse('2026-11-01')))->toBeTrue();
});

it('disables "today" while the current period is shown', function () {
    loginAsAdminInFmPanel();
    test()->travelTo(CarbonImmutable::parse('2026-10-11 10:00'));

    $component = Livewire::test(TestCalendarWidget::class)->call('today');

    expect($component->instance()->isShowingToday())->toBeTrue();

    $component->call('next');
    expect($component->instance()->isShowingToday())->toBeFalse();

    $component->call('today')->call('setView', 'week');
    expect($component->instance()->isShowingToday())->toBeTrue();

    $component->call('previous');
    expect($component->instance()->isShowingToday())->toBeFalse();
});
