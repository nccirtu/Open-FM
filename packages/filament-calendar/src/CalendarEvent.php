<?php

namespace OpenFm\FilamentCalendar;

use BackedEnum;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Filament\Support\Facades\FilamentColor;
use OpenFm\FilamentCalendar\Enums\CalendarEventVariant;

final class CalendarEvent
{
    private string $title = '';

    private ?CarbonImmutable $start = null;

    private ?CarbonImmutable $end = null;

    private bool $allDay = true;

    private ?string $color = null;

    private string|BackedEnum|null $icon = null;

    private ?string $description = null;

    private ?string $badge = null;

    private CalendarEventVariant $variant = CalendarEventVariant::Default;

    private ?string $url = null;

    private bool $shouldOpenUrlInNewTab = false;

    private ?string $action = null;

    /**
     * @var array<string, mixed>
     */
    private array $actionArguments = [];

    private function __construct(private readonly string $id) {}

    public static function make(string $id): self
    {
        return new self($id);
    }

    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function start(CarbonInterface|string $start): self
    {
        $this->start = CarbonImmutable::parse($start);

        return $this;
    }

    public function end(CarbonInterface|string|null $end): self
    {
        $this->end = $end !== null ? CarbonImmutable::parse($end) : null;

        return $this;
    }

    public function allDay(bool $condition = true): self
    {
        $this->allDay = $condition;

        return $this;
    }

    public function color(?string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function icon(string|BackedEnum|null $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function description(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function badge(?string $badge): self
    {
        $this->badge = $badge;

        return $this;
    }

    public function variant(CalendarEventVariant $variant): self
    {
        $this->variant = $variant;

        return $this;
    }

    public function url(?string $url, bool $shouldOpenInNewTab = false): self
    {
        $this->url = $url;
        $this->shouldOpenUrlInNewTab = $shouldOpenInNewTab;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    public function action(?string $name, array $arguments = []): self
    {
        $this->action = $name;
        $this->actionArguments = $arguments;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getStart(): CarbonImmutable
    {
        return $this->start ?? CarbonImmutable::today();
    }

    public function getEnd(): CarbonImmutable
    {
        return $this->end ?? $this->getStart();
    }

    public function isAllDay(): bool
    {
        return $this->allDay;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function getColorStyle(): string
    {
        $color = $this->color;

        if (blank($color)) {
            return '';
        }

        if (array_key_exists($color, FilamentColor::getColors())) {
            return "--fc-event-color: var(--{$color}-500);";
        }

        return '--fc-event-color: '.e($color).';';
    }

    public function getIcon(): string|BackedEnum|null
    {
        return $this->icon;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getBadge(): ?string
    {
        return $this->badge;
    }

    public function getVariant(): CalendarEventVariant
    {
        return $this->variant;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function shouldOpenUrlInNewTab(): bool
    {
        return $this->shouldOpenUrlInNewTab;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    /**
     * @return array<string, mixed>
     */
    public function getActionArguments(): array
    {
        return $this->actionArguments;
    }

    public function occursOn(CarbonImmutable $day): bool
    {
        return $this->getStart()->startOfDay()->lessThanOrEqualTo($day->startOfDay())
            && $this->getEnd()->startOfDay()->greaterThanOrEqualTo($day->startOfDay());
    }

    public function getTimeLabel(): ?string
    {
        if ($this->allDay) {
            return null;
        }

        $start = $this->getStart()->format('H:i');

        return $this->end !== null && $this->end->isSameDay($this->getStart())
            ? $start.'–'.$this->end->format('H:i')
            : $start;
    }
}
