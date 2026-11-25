<?php

namespace App\Observers;

use App\Models\MaintenancePlanTemplate;
use App\Services\MaintenancePlanGenerator;

class MaintenancePlanTemplateObserver
{
    public function created(MaintenancePlanTemplate $template): void
    {
        app(MaintenancePlanGenerator::class)->forTemplate($template);
    }

    public function updated(MaintenancePlanTemplate $template): void
    {
        if ($template->wasChanged(['is_active', 'asset_type_id']) && $template->is_active) {
            app(MaintenancePlanGenerator::class)->forTemplate($template);
        }
    }
}
