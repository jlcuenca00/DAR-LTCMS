<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Parcel;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ParcelReviewFlagController extends Controller
{
    public function edit(Parcel $parcel)
    {
        $parcel->load(['flaggedBy', 'flagResolvedBy']);

        return view('staff.records.parcel-review-flag', [
            'parcel' => $parcel,
            'flagReasons' => Parcel::reviewFlagReasonOptions(),
        ]);
    }

    public function flag(Request $request, Parcel $parcel)
    {
        if ($parcel->status === 'inactive') {
            throw ValidationException::withMessages([
                'flag_reason' => 'Inactive parcel records cannot be newly flagged for review. Reactivate the parcel first if further review is required.',
            ]);
        }

        $data = $request->validate([
            'flag_reason' => ['required', Rule::in(array_keys(Parcel::reviewFlagReasonOptions()))],
            'flag_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        [$parcel, $wasFlagged] = DB::transaction(function () use ($parcel, $data, $request) {
            $lockedParcel = Parcel::query()
                ->whereKey($parcel->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedParcel->status === 'inactive') {
                throw ValidationException::withMessages([
                    'flag_reason' => 'Inactive parcel records cannot be newly flagged for review. Reactivate the parcel first if further review is required.',
                ]);
            }

            $wasFlagged = (bool) $lockedParcel->is_flagged;
            $previousReason = $lockedParcel->flag_reason;

            $lockedParcel->forceFill([
                'is_flagged' => true,
                'flag_reason' => $data['flag_reason'],
                'flag_notes' => $data['flag_notes'] ?? null,
                'flagged_by' => $request->user()?->id,
                'flagged_at' => now(),
                'flag_resolved_by' => null,
                'flag_resolved_at' => null,
                'flag_resolution_notes' => null,
            ])->save();

            AuditLogger::record(
                $wasFlagged ? 'parcel_review_flag_updated' : 'parcel_review_flagged',
                null,
                $lockedParcel,
                [
                    'parcel_id' => $lockedParcel->id,
                    'parcel_code' => $lockedParcel->parcel_code,
                    'previous_reason' => $previousReason,
                    'flag_reason' => $lockedParcel->flag_reason,
                    'flag_notes' => $lockedParcel->flag_notes,
                    'actor_user_id' => $request->user()?->id,
                    'actor_name' => $request->user()?->name,
                    'scope_note' => 'Review flag only; parcel ownership, landholding, application decision, and registry records are unchanged.',
                ]
            );

            return [$lockedParcel, $wasFlagged];
        });

        return redirect()
            ->route('staff.records.parcels.review-flag.edit', $parcel)
            ->with('success', $wasFlagged ? 'Parcel review flag updated.' : 'Parcel flagged for review.');
    }

    public function resolve(Request $request, Parcel $parcel)
    {
        $data = $request->validate([
            'resolution_notes' => ['required', 'string', 'max:5000'],
        ]);

        if (! $parcel->is_flagged) {
            return redirect()
                ->route('staff.records.parcels.review-flag.edit', $parcel)
                ->with('success', 'This parcel has no active review flag.');
        }

        $parcel = DB::transaction(function () use ($parcel, $data, $request) {
            $lockedParcel = Parcel::query()
                ->whereKey($parcel->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedParcel->is_flagged) {
                return $lockedParcel;
            }

            $flagSnapshot = [
                'flag_reason' => $lockedParcel->flag_reason,
                'flag_notes' => $lockedParcel->flag_notes,
                'flagged_by' => $lockedParcel->flagged_by,
                'flagged_at' => $lockedParcel->flagged_at?->toIso8601String(),
            ];

            $lockedParcel->forceFill([
                'is_flagged' => false,
                'flag_resolved_by' => $request->user()?->id,
                'flag_resolved_at' => now(),
                'flag_resolution_notes' => $data['resolution_notes'],
            ])->save();

            AuditLogger::record(
                'parcel_review_flag_resolved',
                null,
                $lockedParcel,
                [
                    'parcel_id' => $lockedParcel->id,
                    'parcel_code' => $lockedParcel->parcel_code,
                    ...$flagSnapshot,
                    'resolution_notes' => $lockedParcel->flag_resolution_notes,
                    'actor_user_id' => $request->user()?->id,
                    'actor_name' => $request->user()?->name,
                    'scope_note' => 'Review flag resolved; parcel ownership, landholding, application decision, and registry records are unchanged.',
                ]
            );

            return $lockedParcel;
        });

        return redirect()
            ->route('staff.records.parcels.review-flag.edit', $parcel)
            ->with('success', 'Parcel review flag resolved.');
    }
}
