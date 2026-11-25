<?php

namespace OpenFm\FilamentCalendar\Widgets;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use OpenFm\FilamentCalendar\CalendarEvent;
use OpenFm\FilamentCalendar\Concerns\InteractsWithCalendarActions;
use OpenFm\FilamentCalendar\Enums\CalendarView;
use Throwable;

abstract class CalendarWidget extends Widget implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithCalendarActions;
    use InteractsWithSchemas;

    protected string $view = 'filament-calendar::calendar-widget';

    protected int|string|array $columnSpan = 'full';

    public string $calendarView = 'month';

    public string $date = '';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $filters = [];

    /**
     * @return iterable<CalendarEvent>
     */
    abstract public function getEvents(CarbonImmutable $start, CarbonImmutable $end): iterable;

    public function mount(): void
    {
        $this->calendarView = $this->getDefaultView()->value;
        $this->date = $this->getInitialDate()->toDateString();

        if ($this->hasFilters()) {
            $this->filtersForm->fill();
        }
    }

    /**
     * @return array<int, mixed>
     */
    protected function getFilterComponents(): array
    {
        return [];
    }

    public function hasFilters(): bool
    {
        return $this->getFilterComponents() !== [];
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components($this->getFilterComponents())
            ->columns(['default' => 1, 'sm' => 2, 'lg' => 4])
            ->statePath('filters');
    }

    public function getHeading(): ?string
    {
        return null;
    }

    /**
     * @return array<CalendarView>
     */
    public function getViews(): array
    {
        return CalendarView::cases();
    }

    public function getDefaultView(): CalendarView
    {
        return CalendarView::Month;
    }

    public function getInitialDate(): CarbonImmutable
    {
        return CarbonImmutable::today();
    }

    public function getFirstDayOfWeek(): int
    {
        return CarbonInterface::MONDAY;
    }

    public function getMaxEventsPerDay(): int
    {
        return 3;
    }

    public function getDateClickAction(): ?string
    {
        return null;
    }

    public function getCurrentView(): CalendarView
    {
        $view = CalendarView::tryFrom($this->calendarView);

        return $view !== null && in_array($view, $this->getViews(), true) ? $view : $this->getDefaultView();
    }

    public function getDate(): CarbonImmutable
    {
        try {
            return CarbonImmutable::parse($this->date)->startOfDay();
        } catch (Throwable) {
            return $this->getInitialDate()->startOfDay();
        }
    }

    public function setView(string $view): void
    {
        $calendarView = CalendarView::tryFrom($view);

        if ($calendarView !== null && in_array($calendarView, $this->getViews(), true)) {
            $this->calendarView = $calendarView->value;
        }
    }

    public function previous(): void
    {
        $date = match ($this->getCurrentView()) {
            CalendarView::Week => $this->getDate()->subWeek(),
            default => $this->getDate()->startOfMonth()->subMonthNoOverflow(),
        };

        $this->date = $date->toDateString();
    }

    public function next(): void
    {
        $date = match ($this->getCurrentView()) {
            CalendarView::Week => $this->getDate()->addWeek(),
            default => $this->getDate()->startOfMonth()->addMonthNoOverflow(),
        };

        $this->date = $date->toDateString();
    }

    public function today(): void
    {
        $this->date = CarbonImmutable::today()->toDateString();
    }

    public function isShowingToday(): bool
    {
        $today = CarbonImmutable::today();

        return match ($this->getCurrentView()) {
            CalendarView::Week => $today->betweenIncluded(...$this->getRange()),
            default => $this->getDate()->isSameMonth($today),
        };
    }

    public function openDay(string $date): void
    {
        try {
            $this->date = CarbonImmutable::parse($date)->toDateString();
        } catch (Throwable) {
            return;
        }

        if (in_array(CalendarView::Week, $this->getViews(), true)) {
            $this->calendarView = CalendarView::Week->value;
        }
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function getRange(): array
    {
        $date = $this->getDate();
        $weekStart = $this->getFirstDayOfWeek();
        $weekEnd = ($weekStart + 6) % 7;

        return match ($this->getCurrentView()) {
            CalendarView::Week => [$date->startOfWeek($weekStart), $date->startOfWeek($weekStart)->addDays(6)],
            CalendarView::List => [$date->startOfMonth(), $date->endOfMonth()->startOfDay()],
            CalendarView::Month => [$date->startOfMonth()->startOfWeek($weekStart), $date->endOfMonth()->endOfWeek($weekEnd)->startOfDay()],
        };
    }

    public function getTitle(): string
    {
        $date = $this->getDate()->locale(app()->getLocale());

        if ($this->getCurrentView() !== CalendarView::Week) {
            return $date->isoFormat('MMMM YYYY');
        }

        [$start, $end] = $this->getRange();

        return __('filament-calendar::calendar.week_title', [
            'week' => $start->isoWeek(),
            'start' => $start->locale(app()->getLocale())->isoFormat('ll'),
            'end' => $end->locale(app()->getLocale())->isoFormat('ll'),
        ]);
    }

    /**
     * @return array<int, string>
     */
    public function getWeekdayLabels(): array
    {
        $start = CarbonImmutable::today()->startOfWeek($this->getFirstDayOfWeek())->locale(app()->getLocale());

        return collect(range(0, 6))
            ->map(fn (int $offset): string => $start->addDays($offset)->isoFormat('dd'))
            ->all();
    }

    /**
     * @return Collection<int, array{date: CarbonImmutable, events: array<int, CalendarEvent>, isToday: bool, isOutside: bool}>
     */
    public function getCalendarDays(): Collection
    {
        [$start, $end] = $this->getRange();
        $month = $this->getDate()->month;

        $events = collect($this->getEvents($start, $end))
            ->sortBy(fn (CalendarEvent $event): string => ($event->isAllDay() ? '0' : '1').$event->getStart()->format('Y-m-d H:i').$event->getTitle())
            ->values();

        $days = collect();

        for ($day = $start; $day->lessThanOrEqualTo($end); $day = $day->addDay()) {
            $days->push([
                'date' => $day,
                'events' => $events->filter(fn (CalendarEvent $event): bool => $event->occursOn($day))->values()->all(),
                'isToday' => $day->isToday(),
                'isOutside' => $this->getCurrentView() === CalendarView::Month && $day->month !== $month,
            ]);
        }

        return $days;
    }
}
