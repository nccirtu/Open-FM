@php
    /** @var \OpenFm\FilamentCalendar\CalendarEvent $event */
    $classes = [
        'fc-event',
        'fc-event-'.$event->getVariant()->value,
        'fc-event-compact' => $compact,
    ];
    $tooltip = trim($event->getTitle().(filled($event->getDescription()) ? ' · '.$event->getDescription() : ''));
@endphp

@if (filled($url = $event->getUrl()))
    <a
        href="{{ $url }}"
        @if ($event->shouldOpenUrlInNewTab()) target="_blank" rel="noopener noreferrer" @endif
        @class($classes)
        style="{{ $event->getColorStyle() }}"
        title="{{ $tooltip }}"
    >
        @include('filament-calendar::partials.event-content')
    </a>
@elseif (filled($eventAction = $event->getAction()))
    <button
        type="button"
        wire:click="mountAction(@js($eventAction), @js($event->getActionArguments()))"
        @class($classes)
        style="{{ $event->getColorStyle() }}"
        title="{{ $tooltip }}"
    >
        @include('filament-calendar::partials.event-content')
    </button>
@else
    <div @class($classes) style="{{ $event->getColorStyle() }}" title="{{ $tooltip }}">
        @include('filament-calendar::partials.event-content')
    </div>
@endif
