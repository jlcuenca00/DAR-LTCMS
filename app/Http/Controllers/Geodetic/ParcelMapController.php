<?php

namespace App\Http\Controllers\Geodetic;

use App\Http\Controllers\Controller;
use App\Models\Parcel;
use App\Models\ParcelGeometryEditSession;
use App\Models\ParcelGeometryRevision;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ParcelMapController extends Controller
{
    private const EDIT_SESSION_TTL_SECONDS = 120;

    public function index()
    {
        $parcelFeatures = Parcel::query()
            ->select([
                'id',
                'parcel_code',
                'title_no',
                'tax_decl_no',
                'municipality',
                'barangay',
                'area_hectares',
                'status',
                'geometry_geojson',
                'is_flagged',
                'flag_reason',
            ])
            ->with([
                'landholdings:id,parcel_id,landowner_id,status',
                'landholdings.landowner:id,first_name,middle_name,last_name,suffix',
            ])
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
            ->select([
                'id',
                'parcel_code',
                'title_no',
                'tax_decl_no',
                'municipality',
                'barangay',
                'area_hectares',
                'created_at',
            ])
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
                    ? number_format((float) $parcel->area_hectares, 4).' ha'
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
    public function editGeometry(Request $request, Parcel $parcel)
    {
        $this->pruneExpiredEditSessions();

        $editSession = ParcelGeometryEditSession::query()->updateOrCreate(
            [
                'parcel_id' => $parcel->id,
                'user_id' => $request->user()->id,
            ],
            [
                'session_token' => (string) Str::uuid(),
                'base_geometry_version' => (int) $parcel->geometry_version,
                'last_seen_at' => now(),
            ]
        );

        $activeEditors = $this->activeEditorsFor($parcel, $request->user()->id);
        $recentRevisions = $parcel->geometryRevisions()
            ->with('actor:id,name')
            ->limit(8)
            ->get();

        return view('geodetic.parcels.geometry-edit', compact(
            'parcel',
            'editSession',
            'activeEditors',
            'recentRevisions'
        ));
    }

    public function heartbeatGeometrySession(Request $request, Parcel $parcel)
    {
        $data = $request->validate([
            'edit_session_token' => ['required', 'uuid'],
        ]);

        $session = ParcelGeometryEditSession::query()
            ->where('parcel_id', $parcel->id)
            ->where('user_id', $request->user()->id)
            ->where('session_token', $data['edit_session_token'])
            ->first();

        if (! $session || $session->last_seen_at->lt(now()->subSeconds(self::EDIT_SESSION_TTL_SECONDS))) {
            $session?->delete();

            return response()->json([
                'message' => 'Editing session expired. Reload the parcel before saving.',
            ], 409);
        }

        $session->forceFill(['last_seen_at' => now()])->save();

        return response()->json([
            'editors' => $this->activeEditorsFor($parcel, $request->user()->id),
        ]);
    }

    public function releaseGeometrySession(Request $request, Parcel $parcel)
    {
        $data = $request->validate([
            'edit_session_token' => ['required', 'uuid'],
        ]);

        ParcelGeometryEditSession::query()
            ->where('parcel_id', $parcel->id)
            ->where('user_id', $request->user()->id)
            ->where('session_token', $data['edit_session_token'])
            ->delete();

        return response()->noContent();
    }

    public function updateGeometry(Request $request, Parcel $parcel)
    {
        $data = $request->validate([
            'geometry_geojson' => ['required', 'string', 'max:200000'],
            'geometry_version' => ['required', 'integer', 'min:0'],
            'edit_session_token' => ['required', 'uuid'],
        ]);

        $geometry = $this->decodeParcelGeoJson($data['geometry_geojson']);
        $actor = $request->user();

        $result = DB::transaction(function () use ($parcel, $data, $geometry, $actor) {
            $current = Parcel::query()
                ->whereKey($parcel->id)
                ->lockForUpdate()
                ->firstOrFail();

            $session = ParcelGeometryEditSession::query()
                ->where('parcel_id', $current->id)
                ->where('user_id', $actor->id)
                ->where('session_token', $data['edit_session_token'])
                ->lockForUpdate()
                ->first();

            if (! $session || $session->last_seen_at->lt(now()->subSeconds(self::EDIT_SESSION_TTL_SECONDS))) {
                $session?->delete();

                return [
                    'status' => 'expired',
                    'current_version' => (int) $current->geometry_version,
                ];
            }

            $submittedVersion = (int) $data['geometry_version'];
            $currentVersion = (int) $current->geometry_version;

            if ($submittedVersion !== $currentVersion) {
                $session->delete();

                return [
                    'status' => 'conflict',
                    'current_version' => $currentVersion,
                ];
            }

            $previousGeometry = $current->geometry_geojson;
            $previousVersion = $currentVersion;
            $hadGeometryBefore = ! empty($previousGeometry);

            if ($hadGeometryBefore) {
                ParcelGeometryRevision::query()->firstOrCreate(
                    [
                        'parcel_id' => $current->id,
                        'geometry_version' => $previousVersion,
                    ],
                    [
                        'geometry_geojson' => $previousGeometry,
                        'actor_user_id' => null,
                        'source' => 'baseline_snapshot',
                    ]
                );
            }

            // Deliberately update only map geometry. Parcel::saving advances
            // geometry_version whenever geometry_geojson changes.
            $current->forceFill([
                'geometry_geojson' => $geometry,
            ])->save();

            $newVersion = (int) $current->geometry_version;

            $revision = ParcelGeometryRevision::query()->create([
                'parcel_id' => $current->id,
                'geometry_version' => $newVersion,
                'geometry_geojson' => $geometry,
                'actor_user_id' => $actor->id,
                'source' => 'geodetic_edit',
            ]);

            $session->delete();

            return [
                'status' => 'saved',
                'parcel' => $current,
                'revision_id' => $revision->id,
                'previous_version' => $previousVersion,
                'new_version' => $newVersion,
                'had_geometry_before' => $hadGeometryBefore,
            ];
        });

        if ($result['status'] === 'expired') {
            return redirect()
                ->route('geodetic.parcels.geometry.edit', $parcel)
                ->with('error', 'Your editing session expired. The latest parcel geometry has been reloaded; review it before making changes again.');
        }

        if ($result['status'] === 'conflict') {
            return redirect()
                ->route('geodetic.parcels.geometry.edit', $parcel)
                ->with('error', 'This parcel was updated by another user while you were editing. Your save was blocked, and the latest geometry has been reloaded to prevent an overwrite.');
        }

        /** @var Parcel $savedParcel */
        $savedParcel = $result['parcel'];

        AuditLogger::record(
            'geodetic_parcel_geometry_updated',
            null,
            $savedParcel,
            [
                'parcel_id' => $savedParcel->id,
                'parcel_code' => $savedParcel->parcel_code,
                'geometry_type' => $geometry['type'] ?? null,
                'had_geometry_before' => $result['had_geometry_before'],
                'has_geometry_after' => true,
                'previous_geometry_version' => $result['previous_version'],
                'new_geometry_version' => $result['new_version'],
                'revision_id' => $result['revision_id'],
                'editable_scope' => 'geometry_geojson only',
                'concurrency_policy' => 'optimistic version check with active editor presence',
                'actor_user_id' => $actor?->id,
                'actor_name' => $actor?->name,
                'actor_role' => $actor?->role,
                'scope_note' => 'Map geometry is a technical reference and does not establish ownership or legal parcel boundaries.',
            ]
        );

        app(NotificationService::class)->notifyGeodeticParcelGeometryUpdated($savedParcel, $actor);

        return redirect()
            ->route('geodetic.parcels.show', $savedParcel)
            ->with('success', 'Parcel geometry saved successfully as version '.$result['new_version'].'.');
    }

    private function activeEditorsFor(Parcel $parcel, int $excludeUserId)
    {
        $cutoff = now()->subSeconds(self::EDIT_SESSION_TTL_SECONDS);

        return ParcelGeometryEditSession::query()
            ->with('user:id,name')
            ->where('parcel_id', $parcel->id)
            ->where('user_id', '!=', $excludeUserId)
            ->where('last_seen_at', '>=', $cutoff)
            ->orderByDesc('last_seen_at')
            ->get()
            ->map(fn (ParcelGeometryEditSession $session) => [
                'user_id' => $session->user_id,
                'name' => $session->user?->name ?: 'Another Geodetic user',
                'last_seen_at' => $session->last_seen_at?->toIso8601String(),
            ])
            ->values();
    }

    private function pruneExpiredEditSessions(): void
    {
        ParcelGeometryEditSession::query()
            ->where('last_seen_at', '<', now()->subSeconds(self::EDIT_SESSION_TTL_SECONDS))
            ->delete();
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
