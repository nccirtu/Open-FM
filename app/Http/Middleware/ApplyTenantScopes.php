<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use App\Models\Asset;
use App\Models\AssetTypeClassification;
use App\Models\BimModel;
use App\Models\ClassificationNode;
use App\Models\Company;
use App\Models\Contract;
use App\Models\CostEntry;
use App\Models\EnergyGoal;
use App\Models\MaintenanceOrder;
use App\Models\MaintenancePlan;
use App\Models\MaintenancePlanTemplate;
use App\Models\Meter;
use App\Models\MeterDailyValue;
use App\Models\MeterReading;
use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Models\Property;
use App\Models\SlaDefinition;
use App\Models\StructureNode;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\TicketAutomationRule;
use App\Models\TicketReply;
use App\Models\WasteCollection;
use App\Models\WasteContainer;
use App\Models\WasteContainerType;
use App\Models\WasteDailyValue;
use App\Models\WasteEmissionFactor;
use App\Models\WasteEvidence;
use App\Models\WasteFillReading;
use App\Models\WasteFraction;
use App\Models\WasteFractionGroup;
use App\Models\WasteGoal;
use App\Models\WastePickup;
use App\Models\WasteSchedule;
use App\Models\WasteSettings;
use App\Models\WasteTreatment;
use App\Models\WasteTreatmentCategory;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyTenantScopes
{
    /** @var list<class-string<Model>> */
    public const TENANT_MODELS = [
        Property::class,
        Asset::class,
        Project::class,
        CostEntry::class,
        Task::class,
        ProjectTemplate::class,
        Company::class,
        ApiToken::class,
        Ticket::class,
        TicketReply::class,
        TicketAutomationRule::class,
        MaintenancePlan::class,
        MaintenancePlanTemplate::class,
        MaintenanceOrder::class,
        Contract::class,
        StructureNode::class,
        SlaDefinition::class,
        BimModel::class,
        Meter::class,
        MeterReading::class,
        MeterDailyValue::class,
        EnergyGoal::class,
        ClassificationNode::class,
        AssetTypeClassification::class,
        WasteSettings::class,
        WasteTreatmentCategory::class,
        WasteTreatment::class,
        WasteFractionGroup::class,
        WasteContainerType::class,
        WasteFraction::class,
        WasteContainer::class,
        WasteSchedule::class,
        WastePickup::class,
        WasteCollection::class,
        WasteDailyValue::class,
        WasteGoal::class,
        WasteEvidence::class,
        WasteEmissionFactor::class,
        WasteFillReading::class,
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Filament::getTenant();

        if ($tenant !== null) {
            static::scopeToOrganizations([$tenant->getKey()]);
        }

        return $next($request);
    }

    /**
     * @param  list<int>  $organizationIds
     */
    public static function scopeToOrganizations(array $organizationIds): void
    {
        foreach (self::TENANT_MODELS as $model) {
            $model::addGlobalScope(
                'organization',
                fn (Builder $query) => $query->whereIn($query->getModel()->getTable().'.organization_id', $organizationIds),
            );
        }
    }
}
