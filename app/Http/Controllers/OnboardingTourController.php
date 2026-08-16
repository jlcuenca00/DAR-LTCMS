<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OnboardingTourController extends Controller
{
    public function show(Request $request, string $tourKey): JsonResponse
    {
        $tour = config("onboarding.tours.{$tourKey}");

        abort_unless(is_array($tour), 404);
        abort_unless(($tour['role'] ?? null) === $request->user()?->role, 403);

        $version = (int) ($tour['version'] ?? 1);
        $entry = data_get($request->user()?->onboarding_state ?? [], $tourKey, []);
        $seenVersion = (int) ($entry['version'] ?? 0);

        return response()->json([
            'tour_key' => $tourKey,
            'version' => $version,
            'seen' => $seenVersion >= $version,
            'status' => $entry['status'] ?? null,
            'seen_at' => $entry['seen_at'] ?? null,
        ]);
    }

    public function store(Request $request, string $tourKey): JsonResponse
    {
        $tour = config("onboarding.tours.{$tourKey}");

        abort_unless(is_array($tour), 404);
        abort_unless(($tour['role'] ?? null) === $request->user()?->role, 403);

        $currentVersion = (int) ($tour['version'] ?? 1);

        $validated = $request->validate([
            'version' => ['required', 'integer', Rule::in([$currentVersion])],
            'status' => ['required', Rule::in(['completed', 'skipped'])],
        ]);

        $user = $request->user();
        $state = is_array($user->onboarding_state) ? $user->onboarding_state : [];
        $state[$tourKey] = [
            'version' => $currentVersion,
            'status' => $validated['status'],
            'seen_at' => now()->toIso8601String(),
        ];

        $user->forceFill(['onboarding_state' => $state])->save();

        return response()->json([
            'saved' => true,
            'tour_key' => $tourKey,
            'version' => $currentVersion,
            'status' => $validated['status'],
        ]);
    }
}
