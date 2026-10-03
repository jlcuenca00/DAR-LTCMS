<?php

namespace App\Http\Controllers\Geodetic;

use App\Http\Controllers\Controller;
use App\Models\Parcel;
use App\Models\ParcelGeometryEditSession;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use App\Services\ParcelGeometryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ParcelMapController extends Controller
{
    private const EDIT_SESSION_TTL_SECONDS = 120;

    public function index(\App\Services\ParcelMapDataService $maps)
    {
        $mapConfig = $maps->config(\Illuminate\Support\Facades\Auth::user());

        return view('geodetic.maps.parcel-map', compact('mapConfig'));
    }

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
        Gate::authorize('view', $parcel);

        $parcel->load([
            'landholdings' => fn ($query) => $query->select([
                'id',
                'parcel_id',
                'landowner_id',
                'source_application_id',
                'area_hectares',
                'status',
                'date_acquired',
                'date_transferred',
                'source_reference_number',
                'remarks',
            ]),
            'landholdings.landowner:id,first_name,middle_name,last_name,suffix',
            'landholdings.sourceApplication:id,application_code',
        ]);

        return view('geodetic.parcels.show', compact('parcel'));
    }

    /**
     * Geodetic personnel may edit parcel map geometry only.
     * Ownership, landholding, application, legal, and registry fields remain read-only.
     */
    public function editGeometry(Request $request, Parcel $parcel)
    {
        Gate::authorize('updateGeometry', $parcel);
        abort_if($parcel->status === 'inactive', 403, 'Archived parcel geometry is read-only.');

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
        Gate::authorize('updateGeometry', $parcel);

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

        if ($parcel->status === 'inactive'
            || (int) $session->base_geometry_version !== (int) $parcel->geometry_version) {
            $session->delete();

            return response()->json([
                'message' => 'This parcel changed after you opened the editor. Reload the latest geometry before continuing.',
            ], 409);
        }

        $session->forceFill(['last_seen_at' => now()])->save();

        return response()->json([
            'editors' => $this->activeEditorsFor($parcel, $request->user()->id),
        ]);
    }

    public function releaseGeometrySession(Request $request, Parcel $parcel)
    {
        Gate::authorize('updateGeometry', $parcel);

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
        Gate::authorize('updateGeometry', $parcel);

        $data = $request->validate([
            'geometry_geojson' => ['required', 'string', 'max:200000'],
            'geometry_version' => ['required', 'integer', 'min:0'],
            'edit_session_token' => ['required', 'uuid'],
        ]);

        $geometry = app(ParcelGeometryService::class)->decodePolygon(
            $data['geometry_geojson'],
            required: true
        );
        $actor = $request->user();

        $result = DB::transaction(function () use ($parcel, $data, $geometry, $actor) {
            $current = Parcel::query()
                ->whereKey($parcel->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($current->status === 'inactive') {
                ParcelGeometryEditSession::query()
                    ->where('parcel_id', $current->id)
                    ->where('user_id', $actor->id)
                    ->where('session_token', $data['edit_session_token'])
                    ->delete();

                return [
                    'status' => 'inactive',
                    'parcel' => $current,
                ];
            }

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
            $sessionBaseVersion = (int) $session->base_geometry_version;
            $currentVersion = (int) $current->geometry_version;

            if ($submittedVersion !== $sessionBaseVersion
                || $sessionBaseVersion !== $currentVersion) {
                $session->delete();

                return [
                    'status' => 'conflict',
                    'current_version' => $currentVersion,
                ];
            }

            $previousGeometry = $current->geometry_geojson;

            if ($previousGeometry === $geometry) {
                $session->delete();

                return [
                    'status' => 'unchanged',
                    'parcel' => $current,
                    'current_version' => $currentVersion,
                ];
            }

            $previousVersion = $currentVersion;
            $hadGeometryBefore = ! empty($previousGeometry);

            // Deliberately update only map geometry. Parcel::saving advances
            // geometry_version whenever geometry_geojson changes.
            $current->forceFill([
                'geometry_geojson' => $geometry,
            ])->save();

            $newVersion = (int) $current->geometry_version;

            $revision = app(ParcelGeometryService::class)->recordRevision(
                $current,
                $previousGeometry,
                $previousVersion,
                $actor,
                'geodetic_edit'
            );

            $session->delete();

            return [
                'status' => 'saved',
                'parcel' => $current,
                'revision_id' => $revision?->id,
                'previous_version' => $previousVersion,
                'new_version' => $newVersion,
                'had_geometry_before' => $hadGeometryBefore,
            ];
        });

        if ($result['status'] === 'inactive') {
            return redirect()
                ->route('geodetic.parcels.show', $result['parcel'])
                ->with('error', 'This parcel has been archived. Its map geometry is read-only and was not changed.');
        }

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

        if ($result['status'] === 'unchanged') {
            return redirect()
                ->route('geodetic.parcels.show', $result['parcel'])
                ->with('success', 'No geometry changes were detected. Version '.$result['current_version'].' was retained.');
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

}
