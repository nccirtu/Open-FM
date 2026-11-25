@php
    use Filament\Support\Facades\FilamentAsset;
    use OpenFm\FilamentCalendar\Enums\CalendarView;

    $view = $this->getCurrentView();
    $days = $this->getCalendarDays();
    $dateClickAction = $this->getDateClickAction();
    $maxEvents = $this->getMaxEventsPerDay();
    $locale = app()->getLocale();
@endphp

<x-filament-widgets::widget class="fc-widget">
    <div
        x-data="{}"
        x-load-css="[@js(FilamentAsset::getStyleHref('filament-calendar', package: 'open-fm/filament-calendar'))]"
    >
        <x-filament::section>
            <div class="fc-toolbar">
                <div class="fc-toolbar-start">
                    <div class="fc-nav">
                        <x-filament::icon-button
                            icon="heroicon-m-chevron-left"
                            color="gray"
                            wire:click="previous"
                            :label="__('filament-calendar::calendar.previous')"
                        />
                        <x-filament::icon-button
                            icon="heroicon-m-chevron-right"
                            color="gray"
                            wire:click="next"
                            :label="__('filament-calendar::calendar.next')"
                        />
                    </div>

                    <h2 class="fc-title">{{ $this->getTitle() }}</h2>

                    @if (filled($heading = $this->getHeading()))
                        <span class="fc-heading">{{ $heading }}</span>
                    @endif
                </div>

                <div class="fc-toolbar-end">
                    <x-filament::button
                        color="gray"
                        size="sm"
                        wire:click="today"
                        :disabled="$this->isShowingToday()"
                        :tooltip="$this->isShowingToday() ? __('filament-calendar::calendar.today_shown') : null"
                    >
                        {{ __('filament-calendar::calendar.today') }}
                    </x-filament::button>

                    @if (count($this->getViews()) > 1)
                        <div class="fc-view-switcher" role="group" aria-label="{{ __('filament-calendar::calendar.view') }}">
                            @foreach ($this->getViews() as $option)
                                <x-filament::button
                                    :color="$option === $view ? 'primary' : 'gray'"
                                    size="sm"
                                    wire:click="setView(@js($option->value))"
                                    :aria-pressed="$option === $view ? 'true' : 'false'"
                                    @class(['fc-view-button', 'fc-active' => $option === $view])
                                >
                                    {{ $option->getLabel() }}
                                </x-filament::button>
                            @endforeach
                        </div>
                    @endif

                    @foreach ($this->getCachedCalendarHeaderActions() as $headerAction)
                        {{ $headerAction }}
                    @endforeach
                </div>
            </div>

            @if ($this->hasFilters())
                <div class="fc-filters">
                    {{ $this->filtersForm }}
                </div>
            @endif

            @if ($view === CalendarView::List)
                @php
                    $listDays = $days->filter(fn (array $day): bool => $day['events'] !== []);
                @endphp

                <div class="fc-list" wire:loading.class="fc-loading">
                    @forelse ($listDays as $day)
                        <div @class(['fc-list-day', 'fc-day-today' => $day['isToday']])>
                            <div class="fc-list-date">
                                <span class="fc-list-weekday">{{ $day['date']->locale($locale)->isoFormat('dd') }}</span>
                                <span class="fc-list-daynum">{{ $day['date']->day }}</span>
                                <span class="fc-list-month">{{ $day['date']->locale($locale)->isoFormat('MMM') }}</span>
                            </div>

                            <div class="fc-list-events">
                                @foreach ($day['events'] as $event)
                                    @include('filament-calendar::partials.event', ['event' => $event, 'compact' => false])
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="fc-empty">{{ __('filament-calendar::calendar.no_events') }}</p>
                    @endforelse
                </div>
            @else
                <div class="fc-grid fc-grid-{{ $view->value }}" wire:loading.class="fc-loading">
                    @foreach ($this->getWeekdayLabels() as $weekday)
                        <div class="fc-weekday">{{ $weekday }}</div>
                    @endforeach

                    @foreach ($days as $day)
                        @php
                            $visibleEvents = $view === CalendarView::Month ? array_slice($day['events'], 0, $maxEvents) : $day['events'];
                            $hiddenCount = count($day['events']) - count($visibleEvents);
                            $fullDate = $day['date']->locale($locale)->isoFormat('dddd, LL');
                        @endphp

                        <div @class([
                            'fc-day',
                            'fc-day-outside' => $day['isOutside'],
                            'fc-day-today' => $day['isToday'],
                        ])>
                            <div class="fc-day-header">
                                @if (filled($dateClickAction))
                                    <button
                                        type="button"
                                        class="fc-day-number"
                                        wire:click="mountAction(@js($dateClickAction), @js(['date' => $day['date']->toDateString()]))"
                                        title="{{ $fullDate }}"
                                    >
                                        {{ $day['date']->day }}
                                    </button>
                                @else
                                    <span class="fc-day-number" title="{{ $fullDate }}">{{ $day['date']->day }}</span>
                                @endif

                                @if ($view === CalendarView::Week)
                                    <span class="fc-day-name">{{ $day['date']->locale($locale)->isoFormat('dddd') }}</span>
                                @endif
                            </div>

                            <div class="fc-day-events">
                                @foreach ($visibleEvents as $event)
                                    @include('filament-calendar::partials.event', ['event' => $event, 'compact' => $view === CalendarView::Month])
                                @endforeach

                                @if ($hiddenCount > 0)
                                    <button type="button" class="fc-more" wire:click="openDay(@js($day['date']->toDateString()))">
                                        {{ __('filament-calendar::calendar.more', ['count' => $hiddenCount]) }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>
    </div>

    <x-filament-actions::modals />
</x-filament-widgets::widget>
