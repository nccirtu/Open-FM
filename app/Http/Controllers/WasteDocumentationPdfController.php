<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\WasteEvidence;
use App\Services\Waste\WasteDocumentation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class WasteDocumentationPdfController extends Controller
{
    public function download(Request $request, Property $property, WasteDocumentation $documentation): Response
    {
        setPermissionsTeamId($property->organization_id);

        abort_unless($request->user()?->can('viewAny', WasteEvidence::class), 403);

        $year = (int) $request->integer('year', now()->year);
        abort_if($year < 2000 || $year > 2100, 404);

        $pdf = Pdf::loadView('pdfs.abfall-dokumentation', ['doc' => $documentation->for($property, $year)]);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('abfall-dokumentation-'.Str::slug($property->property_number).'-'.$year.'.pdf');
    }
}
