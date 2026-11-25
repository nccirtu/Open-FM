<?php

namespace App\Http\Controllers;

use App\Enums\StructureNodeType;
use App\Models\Asset;
use App\Models\BimModel;
use App\Models\StructureNode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class BimModelController extends Controller
{
    public function file(BimModel $bimModel): Response
    {
        setPermissionsTeamId($bimModel->organization_id);
        Gate::authorize('view', $bimModel->property);

        abort_unless(Storage::disk('local')->exists($bimModel->file_path), 404);

        return Storage::disk('local')->response($bimModel->file_path, basename($bimModel->file_path), [
            'Content-Type' => 'application/octet-stream',
        ]);
    }

    public function importRooms(Request $request, BimModel $bimModel): JsonResponse
    {
        setPermissionsTeamId($bimModel->organization_id);
        Gate::authorize('update', $bimModel->property);

        $data = $request->validate([
            'floors' => ['array'],
            'floors.*.guid' => ['required', 'string', 'max:64'],
            'floors.*.name' => ['required', 'string', 'max:255'],
            'floors.*.level' => ['required', 'integer'],
            'rooms' => ['array'],
            'rooms.*.guid' => ['required', 'string', 'max:64'],
            'rooms.*.name' => ['required', 'string', 'max:255'],
            'rooms.*.storey_guid' => ['nullable', 'string', 'max:64'],
            'rooms.*.area' => ['nullable', 'numeric', 'min:0'],
            'rooms.*.height' => ['nullable', 'numeric', 'min:0'],
            'assets' => ['array'],
            'assets.*.guid' => ['required', 'string', 'max:64'],
            'assets.*.name' => ['required', 'string', 'max:255'],
            'assets.*.ifc_class' => ['nullable', 'string', 'max:64'],
            'assets.*.storey_guid' => ['nullable', 'string', 'max:64'],
            'assets.*.room_guid' => ['nullable', 'string', 'max:64'],
        ]);

        $floorsByGuid = [];
        $roomsByGuid = [];
        $floorCount = 0;
        $roomCount = 0;

        $nodeByGuid = fn (string $guid): ?StructureNode => StructureNode::query()
            ->where('property_id', $bimModel->property_id)
            ->where('ifc_guid', $guid)
            ->first();

        foreach ($data['floors'] ?? [] as $floorData) {
            $floor = $nodeByGuid($floorData['guid']) ?? new StructureNode;

            $floor->fill([
                'property_id' => $bimModel->property_id,
                'type' => StructureNodeType::Floor,
                'name' => $floorData['name'],
                'level' => $floorData['level'],
                'sort' => $floorData['level'],
            ]);

            $floor->ifc_guid = $floorData['guid'];
            $floor->organization_id = $bimModel->organization_id;

            $floor->saveQuietly();

            $floorsByGuid[$floorData['guid']] = $floor;
            $floorCount++;
        }

        foreach ($data['rooms'] ?? [] as $roomData) {
            $room = $nodeByGuid($roomData['guid']) ?? new StructureNode;

            $storeyGuid = $roomData['storey_guid'] ?? null;

            $parentId = $storeyGuid !== null
                ? ($floorsByGuid[$storeyGuid]->id ?? $nodeByGuid($storeyGuid)?->id)
                : null;

            $room->fill(array_filter([
                'property_id' => $bimModel->property_id,
                'type' => StructureNodeType::Room,
                'name' => $roomData['name'],
                'parent_id' => $parentId,
                'area' => $roomData['area'] ?? null,
                'height' => $roomData['height'] ?? null,
            ], fn (mixed $value): bool => $value !== null));

            $room->ifc_guid = $roomData['guid'];
            $room->organization_id = $bimModel->organization_id;
            $room->saveQuietly();

            $roomsByGuid[$roomData['guid']] = $room;
            $roomCount++;
        }

        $assetCount = 0;

        foreach ($data['assets'] ?? [] as $assetData) {
            $asset = Asset::query()
                ->where('property_id', $bimModel->property_id)
                ->where('ifc_guid', $assetData['guid'])
                ->first() ?? new Asset;

            $storeyGuid = $assetData['storey_guid'] ?? null;
            $roomGuid = $assetData['room_guid'] ?? null;

            $nodeId = ($roomGuid !== null
                ? ($roomsByGuid[$roomGuid]->id ?? $nodeByGuid($roomGuid)?->id)
                : null)
                ?? ($storeyGuid !== null
                    ? ($floorsByGuid[$storeyGuid]->id ?? $nodeByGuid($storeyGuid)?->id)
                    : null);

            $asset->fill(array_filter([
                'property_id' => $bimModel->property_id,
                'name' => $assetData['name'],
                'structure_node_id' => $nodeId,
            ], fn (mixed $value): bool => $value !== null));

            $asset->ifc_guid = $assetData['guid'];
            $asset->organization_id = $bimModel->organization_id;
            $asset->saveQuietly();

            $assetCount++;
        }

        return response()->json([
            'floors' => $floorCount,
            'rooms' => $roomCount,
            'assets' => $assetCount,
        ]);
    }
}
