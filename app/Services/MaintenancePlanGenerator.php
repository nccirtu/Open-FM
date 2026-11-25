<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\MaintenancePlan;
use App\Models\MaintenancePlanTemplate;

class MaintenancePlanGenerator
{
    public function forAsset(Asset $asset): int
    {
        if ($asset->asset_type_id === null || $asset->organization_id === null) {
            return 0;
        }

        $templates = MaintenancePlanTemplate::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $asset->organization_id)
            ->where('asset_type_id', $asset->asset_type_id)
            ->where('is_active', true)
            ->get();

        return $templates->sum(fn (MaintenancePlanTemplate $template): int => $this->createPlan($template, $asset) ? 1 : 0);
    }

    public function forTemplate(MaintenancePlanTemplate $template): int
    {
        if (! $template->is_active || $template->organization_id === null) {
            return 0;
        }

        $assets = Asset::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $template->organization_id)
            ->where('asset_type_id', $template->asset_type_id)
            ->get();

        return $assets->sum(fn (Asset $asset): int => $this->createPlan($template, $asset) ? 1 : 0);
    }

    private function createPlan(MaintenancePlanTemplate $template, Asset $asset): bool
    {
        $exists = MaintenancePlan::query()
            ->withoutGlobalScopes()
            ->where('maintenance_plan_template_id', $template->getKey())
            ->where('asset_id', $asset->getKey())
            ->exists();

        if ($exists) {
            return false;
        }

        $plan = new MaintenancePlan([
            'asset_id' => $asset->getKey(),
            'name' => $template->name,
            'type' => $template->type,
            'legal_basis' => $template->legal_basis,
            'interval_value' => $template->interval_value,
            'interval_unit' => $template->interval_unit,
            'next_due_at' => $template->interval_unit->addTo(now(), $template->interval_value)->toDateString(),
            'lead_time_days' => $template->lead_time_days,
            'notes' => $template->notes,
            'tasks' => $template->tasks,
        ]);

        $plan->maintenance_plan_template_id = $template->getKey();
        $plan->organization_id = $template->organization_id;

        $plan->saveQuietly();

        return true;
    }
}
