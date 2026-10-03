<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Landowner;
use App\Models\Parcel;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RecordLookupController extends Controller
{
    private const RESULT_LIMIT = 20;

    public function landowners(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'scope' => ['nullable', Rule::in(['user-link'])],
            'current_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $search = trim((string) ($filters['q'] ?? ''));
        $query = Landowner::query();

        if (($filters['scope'] ?? null) === 'user-link') {
            $currentUserId = $filters['current_user_id'] ?? null;

            $query->where(function ($eligible) use ($currentUserId) {
                $eligible->whereNull('user_id');

                if ($currentUserId) {
                    $eligible->orWhere('user_id', $currentUserId);
                }
            });
        }

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $pattern = '%'.collect(preg_split('/\\s+/u', $needle))
                ->filter()
                ->implode('%').'%';

            $query->where(function ($matching) use ($pattern, $search) {
                $matching->whereRaw(
                    "LOWER(COALESCE(first_name, '') || ' ' || COALESCE(middle_name, '') || ' ' || COALESCE(last_name, '') || ' ' || COALESCE(registered_owner_status, '') || ' ' || COALESCE(spouse_name, '') || ' ' || COALESCE(contact_number, '') || ' ' || COALESCE(address_line, '')) LIKE ?",
                    [$pattern]
                );

                if (ctype_digit($search)) {
                    $matching->orWhereKey((int) $search);
                }
            });
        }

        $results = $query
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(self::RESULT_LIMIT)
            ->get([
                'id',
                'first_name',
                'middle_name',
                'last_name',
                'suffix',
                'municipality',
                'barangay',
            ])
            ->map(function (Landowner $landowner): array {
                $location = collect([$landowner->barangay, $landowner->municipality])
                    ->filter()
                    ->implode(', ');

                return [
                    'id' => $landowner->id,
                    'text' => $landowner->full_name
                        .' — ID '.$landowner->id
                        .($location !== '' ? ' — '.$location : ''),
                    'meta' => [
                        'name' => $landowner->full_name,
                        'municipality' => $landowner->municipality,
                        'barangay' => $landowner->barangay,
                    ],
                ];
            })
            ->values();

        return response()->json(['results' => $results]);
    }

    public function landownerUsers(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'landowner_id' => ['nullable', 'integer', 'exists:landowners,id'],
        ]);
        $currentUserId = isset($filters['landowner_id'])
            ? Landowner::query()->whereKey($filters['landowner_id'])->value('user_id')
            : null;
        $query = User::query()->where('role', User::ROLE_LANDOWNER)
            ->where(function ($eligible) use ($currentUserId) {
                $eligible->whereDoesntHave('landowner');
                if ($currentUserId) {
                    $eligible->orWhereKey($currentUserId);
                }
            });
        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $needle = '%' . mb_strtolower($search) . '%';
            $query->where(function ($matching) use ($needle, $search) {
                $matching->whereRaw("LOWER(COALESCE(name, '') || ' ' || COALESCE(email, '') || ' ' || COALESCE(username, '')) LIKE ?", [$needle]);
                if (ctype_digit($search)) {
                    $matching->orWhereKey((int) $search);
                }
            });
        }
        $results = $query->orderBy('name')->orderBy('id')->limit(self::RESULT_LIMIT)
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user) => ['id' => $user->id, 'text' => $user->name . ' — ' . ($user->email ?: 'ID ' . $user->id)]);
        return response()->json(['results' => $results]);
    }

    public function parcels(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'scope' => ['nullable', Rule::in(['active'])],
        ]);

        $search = trim((string) ($filters['q'] ?? ''));
        $query = Parcel::query();

        if (($filters['scope'] ?? null) === 'active') {
            $query->where('status', 'active');
        }

        if ($search !== '') {
            $needle = mb_strtolower($search);

            $query->where(function ($matching) use ($needle, $search) {
                $matching->whereRaw(
                    "LOWER(COALESCE(parcel_code, '') || ' ' || COALESCE(title_no, '') || ' ' || COALESCE(tax_decl_no, '') || ' ' || COALESCE(lot_number, '') || ' ' || COALESCE(survey_plan_number, '') || ' ' || COALESCE(rod_office, '') || ' ' || COALESCE(remarks, '')) LIKE ?",
                    ["%{$needle}%"]
                );

                if (ctype_digit($search)) {
                    $matching->orWhereKey((int) $search);
                }
            });
        }

        $results = $query
            ->orderBy('parcel_code')
            ->limit(self::RESULT_LIMIT)
            ->get([
                'id',
                'parcel_code',
                'title_no',
                'tax_decl_no',
                'municipality',
                'barangay',
                'area_hectares',
            ])
            ->map(function (Parcel $parcel): array {
                $reference = collect([
                    filled($parcel->title_no) ? 'Title: '.$parcel->title_no : null,
                    filled($parcel->tax_decl_no) ? 'Tax Declaration: '.$parcel->tax_decl_no : null,
                ])->filter()->implode(' / ');

                $label = collect([
                    $parcel->parcel_code,
                    $parcel->title_no,
                    $parcel->barangay,
                    $parcel->municipality,
                ])->filter()->implode(' — ');

                return [
                    'id' => $parcel->id,
                    'text' => $label,
                    'meta' => [
                        'area' => $parcel->area_hectares,
                        'reference' => $reference,
                        'parcelCode' => $parcel->parcel_code,
                        'title' => $parcel->title_no,
                        'municipality' => $parcel->municipality,
                        'barangay' => $parcel->barangay,
                    ],
                ];
            })
            ->values();

        return response()->json(['results' => $results]);
    }
}
