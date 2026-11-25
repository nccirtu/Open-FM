<?php

namespace App\Http\Controllers;

use App\Models\MaintenancePlan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceCalendarPdfController extends Controller
{
    public function download(Request $request): Response
    {
        ini_set('memory_limit', '512M');

        $year = (int) $request->query('year', now()->year);
        $year = max(2000, min(2100, $year));

        $organizationIds = $this->organizationsWithAccess($request);

        abort_if($organizationIds->isEmpty(), 403);

        $plans = MaintenancePlan::query()
            ->whereIn('organization_id', $organizationIds->all())
            ->where('is_active', true)
            ->with(['asset.property'])
            ->get()
            ->sortBy([
                fn (MaintenancePlan $a, MaintenancePlan $b): int => strcmp(
                    $a->asset?->property?->property_number ?? '',
                    $b->asset?->property?->property_number ?? '',
                ),
                fn (MaintenancePlan $a, MaintenancePlan $b): int => strcmp($a->name, $b->name),
            ]);

        $rows = $plans->map(fn (MaintenancePlan $plan): array => [
            'plan' => $plan,
            'months' => $this->dueMonths($plan, $year),
        ]);

        $byProperty = $rows->groupBy(fn (array $row): string => trim(
            ($row['plan']->asset?->property?->property_number ?? '—').' '.($row['plan']->asset?->property?->city ?? ''),
        ))->sortKeys();

        $pdf = Pdf::loadView('pdfs.wartungskalender', [
            'year' => $year,
            'byProperty' => $byProperty,
        ]);

        $pdf->setPaper('a4', 'landscape');

        return $pdf->download("wartungskalender-{$year}.pdf");
    }

    /**
     * @return list<int>
     */
    private function dueMonths(MaintenancePlan $plan, int $year): array
    {
        $yearStart = now()->setDate($year, 1, 1)->startOfDay();
        $yearEnd = now()->setDate($year, 12, 31)->endOfDay();

        $date = $plan->next_due_at->copy();
        $guard = 0;

        while ($date->greaterThanOrEqualTo($yearStart) && $guard < 1000) {
            $date = $plan->interval_unit->addTo($date->copy(), -$plan->interval_value);
            $guard++;
        }

        $months = [];
        $guard = 0;

        while ($guard < 1000) {
            $date = $plan->interval_unit->addTo($date->copy(), $plan->interval_value);
            $guard++;

            if ($date->greaterThan($yearEnd)) {
                break;
            }

            if ($date->year === $year) {
                $months[] = $date->month;
            }
        }

        return array_values(array_unique($months));
    }

    /**
     * @return Collection<int, string>
     */
    private function organizationsWithAccess(Request $request): Collection
    {
        $user = $request->user();
        $previousTeamId = getPermissionsTeamId();

        try {
            return $user->organizations()->pluck('organizations.id')->filter(function (string $organizationId) use ($user): bool {
                setPermissionsTeamId($organizationId);
                $user->unsetRelation('roles')->unsetRelation('permissions');

                return $user->can('viewAny', MaintenancePlan::class);
            })->values();
        } finally {
            setPermissionsTeamId($previousTeamId);
            $user->unsetRelation('roles')->unsetRelation('permissions');
        }
    }
}
