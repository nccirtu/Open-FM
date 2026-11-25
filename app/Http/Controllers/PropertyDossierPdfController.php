<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Models\MaintenancePlan;
use App\Models\Property;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PropertyDossierPdfController extends Controller
{
    public function download(Property $property): Response
    {
        setPermissionsTeamId($property->organization_id);
        Gate::authorize('view', $property);

        ini_set('memory_limit', '512M');

        $property->load([
            'structureNodes',
            'assets.assetType.classifications.classificationNode',
            'assets.structureNode',
            'assets.company',
            'contracts.company',
        ]);

        $assetsByCategory = $property->assets
            ->sortBy('name')
            ->groupBy(function ($asset) use ($property): string {
                $node = $asset->assetType?->classifications
                    ->firstWhere('organization_id', $property->organization_id)
                    ?->classificationNode;

                return $node?->label() ?? $asset->assetType?->name ?? __('reports.dossier.uncategorized');
            })
            ->sortKeys();

        $maintenancePlans = MaintenancePlan::query()
            ->where('is_active', true)
            ->whereHas('asset', fn ($query) => $query->where('property_id', $property->getKey()))
            ->with(['asset', 'company'])
            ->orderBy('next_due_at')
            ->get();

        $structureRows = [];
        $byParent = $property->structureNodes->groupBy(fn ($node) => $node->parent_id ?? '');

        $walk = function (string $parentId, int $depth) use (&$walk, $byParent, &$structureRows): void {
            foreach ($byParent->get($parentId, collect())->sortBy([['sort', 'asc'], ['name', 'asc']]) as $node) {
                $structureRows[] = ['node' => $node, 'depth' => $depth];
                $walk($node->id, $depth + 1);
            }
        };

        $walk('', 0);

        $openTickets = $property->tickets()
            ->whereIn('status', TicketStatus::open())
            ->orderBy('created_at')
            ->get();

        $pdf = Pdf::loadView('pdfs.objektakte', [
            'property' => $property,
            'structureRows' => $structureRows,
            'assetsByCategory' => $assetsByCategory,
            'maintenancePlans' => $maintenancePlans,
            'openTickets' => $openTickets,
        ]);

        $pdf->setPaper('a4', 'portrait');

        $fileName = 'objektakte-'.Str::slug(trim($property->property_number.' '.($property->city ?? ''))).'-'.now()->format('Y-m-d').'.pdf';

        return $pdf->download($fileName);
    }
}
