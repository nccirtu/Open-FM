<?php

namespace Tests\Fixtures;

use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use OpenFm\FilamentCalendar\CalendarEvent;
use OpenFm\FilamentCalendar\Enums\CalendarEventVariant;
use OpenFm\FilamentCalendar\Widgets\CalendarWidget;

class TestCalendarWidget extends CalendarWidget
{
    /**
     * @var array<int, string>
     */
    public array $clicked = [];

    public function getInitialDate(): CarbonImmutable
    {
        return CarbonImmutable::parse('2026-10-11');
    }

    protected function getFilterComponents(): array
    {
        return [
            Select::make('kind')
                ->options(['all' => 'Alle', 'meetings' => 'Nur Besprechungen'])
                ->default('all')
                ->live(),
        ];
    }

    public function getEvents(CarbonImmutable $start, CarbonImmutable $end): iterable
    {
        $events = [
            CalendarEvent::make('a')->title('Wartung Aufzug')->start('2026-10-06')->url('https://example.test/wartung'),
            CalendarEvent::make('b')->title('Besprechung')->start('2026-10-06 09:00')->end('2026-10-06 10:30')->allDay(false)->action('open', ['id' => 'b']),
            CalendarEvent::make('c')->title('Begehung')->start('2026-10-06')->variant(CalendarEventVariant::Striped),
            CalendarEvent::make('d')->title('Prüfung')->start('2026-10-06'),
            CalendarEvent::make('e')->title('Messe')->start('2026-10-30')->end('2026-11-02'),
        ];

        return collect($events)
            ->filter(fn (CalendarEvent $event): bool => ($this->filters['kind'] ?? 'all') !== 'meetings' || $event->getTitle() === 'Besprechung')
            ->filter(fn (CalendarEvent $event): bool => $event->getEnd()->greaterThanOrEqualTo($start) && $event->getStart()->lessThanOrEqualTo($end->endOfDay()));
    }

    public function getDateClickAction(): ?string
    {
        return 'open';
    }

    public function openAction(): Action
    {
        return Action::make('open')
            ->action(function (array $arguments): void {
                $this->clicked[] = $arguments['id'] ?? $arguments['date'];
            });
    }
}
