<?php

namespace App\Services\Waste;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Enums\WasteMissedReason;
use App\Models\Property;
use App\Models\Ticket;
use App\Models\User;
use App\Models\WastePickup;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class PickupNotifier
{
    /**
     * @param  Collection<int, WastePickup>  $pickups
     */
    public function notifyStaging(Collection $pickups): void
    {
        $first = $pickups->first();
        $property = $this->property($first);
        $recipients = $this->recipients($property);

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::make()
            ->title(__('waste.notifications.staging_title'))
            ->body(__('waste.notifications.staging_body', [
                'property' => $property->property_number,
                'date' => $first->due_on?->format('d.m.Y'),
                'containers' => $this->containerList($pickups),
            ]))
            ->info()
            ->icon('heroicon-o-calendar')
            ->sendToDatabase($recipients);
    }

    public function notifyRequestPending(WastePickup $pickup): void
    {
        $property = $this->property($pickup);
        $recipients = $this->recipients($property);

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::make()
            ->title(__('waste.notifications.request_pending_title'))
            ->body(__('waste.notifications.request_pending_body', [
                'property' => $property->property_number,
                'containers' => $this->containerList(collect([$pickup])),
                'date' => $pickup->requested_at?->format('d.m.Y'),
            ]))
            ->warning()
            ->sendToDatabase($recipients);
    }

    public function complain(WastePickup $pickup, WasteMissedReason $reason, ?string $note): ?Ticket
    {
        $container = $pickup->container()->withoutGlobalScopes()->with(['fraction' => fn ($query) => $query->withoutGlobalScopes()])->first();

        if ($container === null) {
            return null;
        }

        $property = $this->property($pickup);

        $ticket = new Ticket([
            'property_id' => $property->getKey(),
            'type' => TicketType::Request,
            'category' => TicketCategory::Other,
            'priority' => TicketPriority::P3,
            'status' => TicketStatus::Reported,
            'title' => __('waste.tickets.complaint_title', [
                'container' => $container->label(),
                'date' => $pickup->due_on?->format('d.m.Y'),
            ]),
            'description' => trim(__('waste.tickets.complaint_description', [
                'reason' => $reason->getLabel(),
                'property' => $property->property_number,
            ])."\n\n".($note ?? '')),
            'company_id' => $container->company_id,
            'contract_id' => $container->contract_id,
            'assigned_to' => $property->manager_id,
        ]);
        $ticket->organization_id = $property->organization_id;
        $ticket->save();

        return $ticket;
    }

    /**
     * @return Collection<int, User>
     */
    public function recipients(Property $property): Collection
    {
        $userIds = collect([$property->manager_id])->filter();

        $roleUserIds = $this->usersWithRoles($property->organization_id, ['Abfall']);

        if ($userIds->isEmpty() && $roleUserIds->isEmpty()) {
            $roleUserIds = $this->usersWithRoles($property->organization_id, ['Admin']);
        }

        return User::query()->whereIn('id', $userIds->merge($roleUserIds)->unique()->values())->get();
    }

    /**
     * @param  list<string>  $roles
     * @return Collection<int, string>
     */
    private function usersWithRoles(?string $organizationId, array $roles): Collection
    {
        $modelHasRoles = config('permission.table_names.model_has_roles');
        $rolesTable = config('permission.table_names.roles');

        return DB::table($modelHasRoles)
            ->join($rolesTable, "{$rolesTable}.id", '=', "{$modelHasRoles}.role_id")
            ->where("{$rolesTable}.organization_id", $organizationId)
            ->whereIn("{$rolesTable}.name", $roles)
            ->where("{$modelHasRoles}.model_type", (new User)->getMorphClass())
            ->pluck("{$modelHasRoles}.model_id")
            ->map(fn (mixed $id): string => (string) $id);
    }

    private function property(WastePickup $pickup): Property
    {
        return Property::query()->withoutGlobalScopes()->findOrFail($pickup->property_id);
    }

    /**
     * @param  Collection<int, WastePickup>  $pickups
     */
    private function containerList(Collection $pickups): string
    {
        return $pickups
            ->map(fn (WastePickup $pickup): ?string => $pickup->container()->withoutGlobalScopes()->with(['fraction' => fn ($query) => $query->withoutGlobalScopes()])->first()?->label())
            ->filter()
            ->implode(', ');
    }
}
