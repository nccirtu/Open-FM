@if (filled($icon = $event->getIcon()))
    {{ \Filament\Support\generate_icon_html($icon, attributes: new \Illuminate\View\ComponentAttributeBag(['class' => 'fc-event-icon'])) }}
@endif

@if (filled($time = $event->getTimeLabel()))
    <span class="fc-event-time">{{ $time }}</span>
@endif

<span class="fc-event-title">{{ $event->getTitle() }}</span>

@if (filled($badge = $event->getBadge()))
    <span class="fc-event-badge">{{ $badge }}</span>
@endif

@if (! $compact && filled($description = $event->getDescription()))
    <span class="fc-event-description">{{ $description }}</span>
@endif
