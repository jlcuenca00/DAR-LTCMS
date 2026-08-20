<?php

namespace App\Http\Controllers\Geodetic;

use App\Http\Controllers\Controller;
use App\Models\Parcel;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ParcelMapController extends Controller
{
    public function index()
    {
        $parcelFeatures = Parcel::query()
            ->with('landholdings.landowner')
            ->where('status', 'active')
            ->whereNotNull('geometry_geojson')
            ->orderBy('municipality')
            ->orderBy('barangay')
            ->orderBy('parcel_code')
            ->get()
            ->map(function (Parcel $parcel) {
                $geometry = $parcel->geometry_geojson;

                if (! is_array($geometry)) {
                    return null;
                }

                if (empty($geometry['type']) || empty($geometry['coordinates'])) {
                    return null;
                }

                $landownerNames = $parcel->landholdings
                    ->map(fn ($landholding) => $landholding->landowner?->full_name)
                    ->filter()
                    ->unique()
                    ->values()
                    ->implode(', ');

                return [
                    'type' => 'Feature',
                    'properties' => [
                        'id' => $parcel->id,
                        'details_url' => route('geodetic.parcels.show', $parcel),
                        'parcel_code' => $parcel->parcel_code,
                        'title_no' => $parcel->title_no ?: 'N/A',
                        'tax_decl_no' => $parcel->tax_decl_no ?: 'N/A',
                        'landowner' => $landownerNames ?: 'No linked landowner record',
                        'municipality' => $parcel->municipality ?: 'N/A',
                        'barangay' => $parcel->barangay ?: 'N/A',
                        'area_hectares' => $parcel->area_hectares ?: 'N/A',
                        'status' => $parcel->is_flagged ? 'flagged' : 'active',
                        'is_flagged' => (bool) $parcel->is_flagged,
                        'flag_reason' => $parcel->is_flagged ? $parcel->flag_reason_label : null,
                    ],
                    'geometry' => $geometry,
                ];
            })
            ->filter()
            ->values();

        $parcelGeoJson = [
            'type' => 'FeatureCollection',
            'features' => $parcelFeatures,
        ];

        return view('geodetic.maps.parcel-map', compact('parcelGeoJson'));
    }

    /**
     * Dashboard work queue for parcels that still need map geometry.
     * This is derived from the parcel record itself; no duplicate workflow status is stored.
     */
    public function awaitingGeometry()
    {
        $query = Parcel::query()
            ->whereNull('geometry_geojson')
            ->where('status', '!=', 'inactive');

        $count = (clone $query)->count();

        $parcels = $query
            ->oldest('created_at')
            ->limit(8)
            ->get()
            ->map(fn (Parcel $parcel) => [
                'id' => $parcel->id,
                'parcel_code' => $parcel->parcel_code,
                'title_no' => $parcel->title_no ?: 'No title reference',
                'tax_decl_no' => $parcel->tax_decl_no ?: 'No tax declaration',
                'municipality' => $parcel->municipality ?: 'N/A',
                'barangay' => $parcel->barangay ?: 'N/A',
                'area_hectares' => $parcel->area_hectares !== null
                    ? number_format((float) $parcel->area_hectares, 4) . ' ha'
                    : 'N/A',
                'edit_url' => route('geodetic.parcels.geometry.edit', $parcel),
                'details_url' => route('geodetic.parcels.show', $parcel),
            ]);

        return response()->json([
            'count' => $count,
            'parcels' => $parcels,
        ]);
    }

    public function show(Parcel $parcel)
    {
        $parcel->load([
            'landholdings.landowner',
            'landholdings.sourceApplication',
        ]);

        return view('geodetic.parcels.show', compact('parcel'));
    }

    /**
     * Geodetic personnel may edit parcel map geometry only.
     * Ownership, landholding, application, legal, and registry fields remain read-only.
     */
    public function editGeometry(Parcel $parcel)
    {
        return view('geodetic.parcels.geometry-edit', compact('parcel'));
    }

    public function updateGeometry(Request $request, Parcel $parcel)
    {
        $data = $request->validate([
            'geometry_geojson' => ['required', 'string', 'max:200000'],
        ]);

        $geometry = $this->decodeParcelGeoJson($data['geometry_geojson']);
        $hadGeometryBefore = ! empty($parcel->geometry_geojson);

        // Deliberately update only the map geometry. Ignore any extra submitted fields.
        $parcel->forceFill([
            'geometry_geojson' => $geometry,
        ])->save();

        AuditLogger::record(
            'geodetic_parcel_geometry_updated',
            null,
            $parcel,
            [
                'parcel_id' => $parcel->id,
                'parcel_code' => $parcel->parcel_code,
                'geometry_type' => $geometry['type'] ?? null,
                'had_geometry_before' => $hadGeometryBefore,
                'has_geometry_after' => true,
                'editable_scope' => 'geometry_geojson only',
                'actor_user_id' => $request->user()?->id,
                'actor_name' => $request->user()?->name,
                'actor_role' => $request->user()?->role,
                'scope_note' => 'Map geometry is a technical reference and does not establish ownership or legal parcel boundaries.',
            ]
        );

        return redirect()
            ->route('geodetic.parcels.show', $parcel)
            ->with('success', 'Parcel GeoJSON geometry saved successfully.');
    }

    private function decodeParcelGeoJson(string $value): array
    {
        $decoded = json_decode($value, true);

        if (
            json_last_error() !== JSON_ERROR_NONE ||
            ! is_array($decoded) ||
            empty($decoded['type']) ||
            empty($decoded['coordinates'])
        ) {
            throw ValidationException::withMessages([
                'geometry_geojson' => 'The geometry must be valid GeoJSON with a type and coordinates.',
            ]);
        }

        if (($decoded['type'] ?? null) !== 'Polygon') {
            throw ValidationException::withMessages([
                'geometry_geojson' => 'Only GeoJSON Polygon geometry is supported for parcel records.',
            ]);
        }

        return $decoded;
    }
}
