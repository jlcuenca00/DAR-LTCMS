<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Landowner;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'role' => ['nullable', 'string', Rule::in(User::ROLES)],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $usersQuery = User::with('landowner')
            ->latest();

        if (! empty($filters['role'])) {
            $usersQuery->where('role', $filters['role']);
        }

        if (! empty($filters['status'])) {
            $usersQuery->where('is_active', $filters['status'] === 'active');
        }

        if (! empty($filters['search'])) {
            $usersQuery->where(function ($query) use ($filters) {
                $query->where('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('username', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('email', 'like', '%' . $filters['search'] . '%');
            });
        }

        $users = $usersQuery
            ->paginate(15)
            ->withQueryString();

        return view('staff.users.index', compact('users', 'filters'));
    }

    public function create()
    {
        $landowners = Landowner::query()
            ->whereNull('user_id')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('staff.users.create', compact('landowners'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'alpha_dash:ascii', 'max:100', 'unique:users,username'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::defaults(), 'confirmed'],
            'role' => ['required', 'string', Rule::in(User::ROLES)],
            'is_active' => ['nullable', 'boolean'],
            'landowner_id' => ['nullable', 'integer', 'exists:landowners,id'],
        ]);

        if ($validated['role'] === User::ROLE_LANDOWNER && empty($validated['landowner_id'])) {
            return back()
                ->withInput()
                ->withErrors([
                    'landowner_id' => 'A landowner account must be linked to a landowner record.',
                ]);
        }

        if (! empty($validated['landowner_id'])) {
            $landowner = Landowner::find($validated['landowner_id']);

            if ($landowner?->user_id) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'landowner_id' => 'This landowner record is already linked to another user account.',
                    ]);
            }
        }

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'] ?? ($validated['username'] . '@dar-ltcms.local'),
                'password' => $validated['password'],
                'role' => $validated['role'],
                'is_active' => (bool) ($validated['is_active'] ?? false),
                'must_change_password' => true,
                'password_changed_at' => now(),
            ]);

            if ($validated['role'] === User::ROLE_LANDOWNER && ! empty($validated['landowner_id'])) {
                Landowner::whereKey($validated['landowner_id'])
                    ->update([
                        'user_id' => $user->id,
                    ]);
            }

            AuditLogger::record(
                'user_created',
                null,
                $user,
                [
                    'created_user_id' => $user->id,
                    'created_user_username' => $user->username,
                    'created_user_role' => $user->role,
                    'is_active' => $user->is_active,
                    'linked_landowner_id' => $validated['landowner_id'] ?? null,
                    'must_change_password' => true,
                ]
            );

            return $user;
        });

        return redirect()
            ->route('staff.users.index')
            ->with('success', "User account {$user->username} created successfully. The user must change the initial password after signing in.");
    }

    public function edit(User $user)
    {
        $landowners = Landowner::query()
            ->where(function ($query) use ($user) {
                $query->whereNull('user_id')
                    ->orWhere('user_id', $user->id);
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $linkedLandownerId = optional($user->landowner)->id;

        return view('staff.users.edit', compact(
            'user',
            'landowners',
            'linkedLandownerId'
        ));
    }

    public function update(Request $request, User $user)
    {
        $currentUser = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'alpha_dash:ascii',
                'max:100',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'role' => ['required', 'string', Rule::in(User::ROLES)],
            'is_active' => ['nullable', 'boolean'],
            'landowner_id' => ['nullable', 'integer', 'exists:landowners,id'],
        ]);

        if ($user->id === $currentUser?->id && $validated['role'] !== $user->role) {
            return back()
                ->withInput()
                ->withErrors([
                    'role' => 'You cannot change your own role.',
                ]);
        }

        $requestedIsActive = (bool) ($validated['is_active'] ?? false);

        if ($user->id === $currentUser?->id && ! $requestedIsActive) {
            return back()
                ->withInput()
                ->withErrors([
                    'is_active' => 'You cannot deactivate your own account.',
                ]);
        }

        if ($validated['role'] === User::ROLE_LANDOWNER && empty($validated['landowner_id'])) {
            return back()
                ->withInput()
                ->withErrors([
                    'landowner_id' => 'A landowner account must be linked to a landowner record.',
                ]);
        }

        if (! empty($validated['landowner_id'])) {
            $landowner = Landowner::find($validated['landowner_id']);

            if ($landowner?->user_id && (int) $landowner->user_id !== (int) $user->id) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'landowner_id' => 'This landowner record is already linked to another user account.',
                    ]);
            }
        }

        DB::transaction(function () use ($validated, $user, $requestedIsActive) {
            $oldValues = [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => $user->is_active,
                'linked_landowner_id' => optional($user->landowner)->id,
            ];

            $user->fill([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'] ?? ($validated['username'] . '@dar-ltcms.local'),
                'role' => $validated['role'],
                'is_active' => $requestedIsActive,
            ]);

            $user->save();

            Landowner::where('user_id', $user->id)
                ->update([
                    'user_id' => null,
                ]);

            if ($user->role === User::ROLE_LANDOWNER && ! empty($validated['landowner_id'])) {
                Landowner::whereKey($validated['landowner_id'])
                    ->update([
                        'user_id' => $user->id,
                    ]);
            }

            $user->refresh();

            AuditLogger::record(
                'user_updated',
                null,
                $user,
                [
                    'updated_user_id' => $user->id,
                    'updated_user_username' => $user->username,
                    'old_values' => $oldValues,
                    'new_values' => [
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'is_active' => $user->is_active,
                        'linked_landowner_id' => optional($user->landowner)->id,
                    ],
                ]
            );
        });

        return redirect()
            ->route('staff.users.index')
            ->with('success', "User account {$user->username} updated successfully.");
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        if ((int) $request->user()->id === (int) $user->id) {
            return back()->with('error', 'Use your profile settings to change your own password.');
        }

        $temporaryPassword = Str::password(12, true, true, false, false);

        DB::transaction(function () use ($user, $temporaryPassword, $request) {
            $user->forceFill([
                'password' => $temporaryPassword,
                'must_change_password' => true,
                'password_changed_at' => now(),
                'remember_token' => Str::random(60),
            ])->save();

            AuditLogger::record(
                'user_password_reset',
                null,
                $user,
                [
                    'reset_user_id' => $user->id,
                    'reset_username' => $user->username,
                    'account_active' => $user->is_active,
                    'force_change_on_next_login' => true,
                ],
                $request->user()->id
            );
        });

        $statusMessage = $user->is_active
            ? 'A temporary password was generated. It is shown only once below.'
            : 'A temporary password was generated. The account remains inactive and cannot sign in until reactivated.';

        return back()
            ->with('success', $statusMessage)
            ->with('temporary_password', $temporaryPassword)
            ->with('temporary_password_username', $user->username);
    }

}
