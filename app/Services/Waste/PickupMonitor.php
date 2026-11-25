<?php

namespace App\Services\Waste;

use App\Enums\WastePickupStatus;
use App\Models\WastePickup;
use App\Models\WasteSettings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class PickupMonitor
{
    /**
     * @var array<string, WasteSettings>
     */
    private array $settings = [];

    public function __construct(
        private WasteCollectionRecorder $recorder,
        private PickupNotifier $notifier,
    ) {}

    /**
     * @return array{staging: int, completed: int, requests: int}
     */
    public function run(?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();

        return [
            'staging' => $this->stagingReminders($now),
            'completed' => $this->completeByPlan($now),
            'requests' => $this->remindRequests($now),
        ];
    }

    private function stagingReminders(CarbonImmutable $now): int
    {
        $pickups = WastePickup::query()
            ->withoutGlobalScopes()
            ->with(['schedule' => fn ($query) => $query->withoutGlobalScopes()])
            ->where('status', WastePickupStatus::Planned->value)
            ->whereNull('staging_reminded_at')
            ->whereDate('due_on', $now->addDay()->toDateString())
            ->get()
            ->filter(fn (WastePickup $pickup): bool => (bool) $pickup->schedule?->needs_staging
                && $now->greaterThanOrEqualTo($this->timeOn($now, $this->settingsFor($pickup)->staging_reminder_time)));

        $groups = $pickups->groupBy(fn (WastePickup $pickup): string => $pickup->property_id.'|'.$pickup->due_on?->toDateString());

        $groups->each(function (Collection $group) use ($now): void {
            $this->notifier->notifyStaging($group->values());
            $group->each(fn (WastePickup $pickup) => $pickup->forceFill(['staging_reminded_at' => $now])->save());
        });

        return $groups->count();
    }

    private function completeByPlan(CarbonImmutable $now): int
    {
        $pickups = WastePickup::query()
            ->withoutGlobalScopes()
            ->where('status', WastePickupStatus::Planned->value)
            ->whereDate('due_on', '<=', $now->toDateString())
            ->get()
            ->filter(fn (WastePickup $pickup): bool => $now->greaterThanOrEqualTo($pickup->cutoffAt($this->settingsFor($pickup))));

        $pickups->each(fn (WastePickup $pickup) => $this->recorder->completeByPlan($pickup));

        return $pickups->count();
    }

    private function remindRequests(CarbonImmutable $now): int
    {
        $pickups = WastePickup::query()
            ->withoutGlobalScopes()
            ->where('status', WastePickupStatus::Requested->value)
            ->whereNull('reminded_at')
            ->whereNotNull('requested_at')
            ->get()
            ->filter(fn (WastePickup $pickup): bool => $now->greaterThanOrEqualTo(
                CarbonImmutable::parse($pickup->requested_at)->addDays($this->settingsFor($pickup)->request_reminder_days),
            ));

        foreach ($pickups as $pickup) {
            $this->notifier->notifyRequestPending($pickup);
            $pickup->forceFill(['reminded_at' => $now])->save();
        }

        return $pickups->count();
    }

    private function timeOn(CarbonImmutable $day, string $time): CarbonImmutable
    {
        [$hour, $minute] = array_map('intval', explode(':', $time.':0'));

        return $day->setTime($hour, $minute);
    }

    private function settingsFor(WastePickup $pickup): WasteSettings
    {
        return $this->settings[(string) $pickup->organization_id] ??= WasteSettings::forOrganization($pickup->organization_id);
    }
}
