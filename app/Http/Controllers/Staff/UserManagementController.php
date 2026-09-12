<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Landowner;
use App\Models\User;
use App\Notifications\AccountCreatedNotification;
use App\Notifications\EmailAddedVerificationNotification;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'role' => ['nullable', 'string', Rule::in(User::ROLES)],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive', 'pending'])],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        // Keep the operational list focused on accounts that can currently sign in.
        // Inactive accounts remain preserved and are available through the dedicated tab.
        $filters['status'] = $filters['status'] ?? 'active';

        $accountCounts = [
            'active' => User::query()->where('is_active', true)->count(),
            'inactive' => User::query()->where('is_active', false)->count(),
            'pending' => User::query()->where('registration_status', User::REGISTRATION_PENDING)->count(),
        ];

        $usersQuery = User::with('landowner')
            ->when($filters['status'] === 'pending', function ($query) {
                $query->where('registration_status', User::REGISTRATION_PENDING);
            }, function ($query) use ($filters) {
                $query->where('is_active', $filters['status'] === 'active');
            })
            ->latest();

        if (! empty($filters['role'])) {
            $usersQuery->where('role', $filters['role']);
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

        return view('staff.users.index', compact('users', 'filters', 'accountCounts'));
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
            'role' => ['required', 'string', Rule::in(User::ROLES)],
            'is_active' => ['nullable', 'boolean'],
            'landowner_id' => ['nullable', 'integer', 'exists:landowners,id'],
            'registration_status' => ['nullable', 'string', Rule::in(User::REGISTRATION_STATUSES)],
        ]);

        if ($validated['role'] === User::ROLE_LANDOWNER && ($validated['registration_status'] ?? User::REGISTRATION_APPROVED) === User::REGISTRATION_APPROVED && empty($validated['landowner_id'])) {
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

        $email = $this->normalizeEmail($validated['email'] ?? null);
        $initialPassword = Str::password(12, true, true, true, false);

        $user = DB::transaction(function () use ($validated, $email, $initialPassword) {
            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $email,
                'password' => $initialPassword,
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
                    'has_recovery_email' => filled($user->email),
                    'linked_landowner_id' => $validated['landowner_id'] ?? null,
                    'must_change_password' => true,
                    'temporary_password_generated_by_system' => true,
                ]
            );

            return $user;
        });

        $emailDelivery = 'not_available';

        if ($this->hasRecoveryEmail($user)) {
            try {
                $user->notify(new AccountCreatedNotification($initialPassword));
                $emailDelivery = 'sent';

                AuditLogger::record(
                    'user_account_creation_email_sent',
                    null,
                    $user,
                    [
                        'created_user_id' => $user->id,
                        'created_user_username' => $user->username,
                        'recipient_email' => $user->email,
                        'force_change_on_first_login' => true,
                    ]
                );
            } catch (Throwable $exception) {
                $emailDelivery = 'failed';

                AuditLogger::record(
                    'user_account_creation_email_failed',
                    null,
                    $user,
                    [
                        'created_user_id' => $user->id,
                        'created_user_username' => $user->username,
                        'recipient_email' => $user->email,
                        'delivery_error_type' => $exception::class,
                    ]
                );
            }
        }

        $statusMessage = match ($emailDelivery) {
            'sent' => "User account {$user->username} created successfully. The username and system-generated temporary password were emailed to {$user->email}. The user must change the password after the first login.",
            'failed' => "User account {$user->username} created successfully, but the confirmation email could not be sent. The system-generated temporary password is shown once below so it can be provided securely. The user must change it after the first login.",
            default => "User account {$user->username} created successfully. No confirmation email was sent because the account has no deliverable email address. The system-generated temporary password is shown once below so it can be provided securely.",
        };

        if ($emailDelivery === 'sent') {
            return redirect()
                ->route('staff.users.index')
                ->with('success', $statusMessage);
        }

        return redirect()
            ->route('staff.users.edit', $user)
            ->with('success', $statusMessage)
            ->with('temporary_password', $initialPassword)
            ->with('temporary_password_username', $user->username);
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

        $email = $this->normalizeEmail($validated['email'] ?? null);
        $previousEmail = $this->normalizeEmail($user->email);
        $emailChanged = mb_strtolower((string) $previousEmail) !== mb_strtolower((string) $email);
        $verificationRequired = $emailChanged && filled($email);
        $emailChangeReason = blank($previousEmail) ? 'email_added' : 'email_changed';

        DB::transaction(function () use ($validated, $user, $requestedIsActive, $email, $emailChanged, $currentUser) {
            $oldValues = [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => $user->is_active,
                'linked_landowner_id' => optional($user->landowner)->id,
                'registration_status' => $user->registration_status,
            ];

            $user->fill([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $email,
                'role' => $validated['role'],
                'is_active' => $requestedIsActive,
                'registration_status' => $validated['registration_status'] ?? $user->registration_status,
            ]);

            if ($user->isDirty('registration_status')) {
                $user->registration_reviewed_at = now();
                $user->registration_reviewed_by_user_id = $currentUser?->id;
            }

            if ($emailChanged) {
                $user->email_verified_at = null;
            }

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
                        'registration_status' => $user->registration_status,
                    ],
                    'email_verification_reset' => $emailChanged,
                ]
            );
        });

        $verificationDelivery = 'not_required';

        if ($verificationRequired) {
            try {
                $user->refresh();
                $user->notify(new EmailAddedVerificationNotification());
                $verificationDelivery = 'sent';

                AuditLogger::record(
                    'user_email_verification_email_sent',
                    null,
                    $user,
                    [
                        'updated_user_id' => $user->id,
                        'updated_user_username' => $user->username,
                        'recipient_email' => $user->email,
                        'reason' => $emailChangeReason,
                        'verification_link_expires_in_hours' => 24,
                    ]
                );
            } catch (Throwable $exception) {
                $verificationDelivery = 'failed';

                AuditLogger::record(
                    'user_email_verification_email_failed',
                    null,
                    $user,
                    [
                        'updated_user_id' => $user->id,
                        'updated_user_username' => $user->username,
                        'recipient_email' => $user->email,
                        'reason' => $emailChangeReason,
                        'delivery_error_type' => $exception::class,
                    ]
                );
            }
        }

        $statusMessage = match ($verificationDelivery) {
            'sent' => "User account {$user->username} updated successfully. A verification email was sent to {$user->email} so the user can confirm the newly registered email address.",
            'failed' => "User account {$user->username} updated successfully, but the email verification message could not be sent. The address remains unverified.",
            default => "User account {$user->username} updated successfully.",
        };

        return redirect()
            ->route('staff.users.index')
            ->with('success', $statusMessage);
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        if ((int) $request->user()->id === (int) $user->id) {
            return back()->with('error', 'Use your profile settings to change your own password.');
        }

        if ($this->hasRecoveryEmail($user)) {
            return back()->with(
                'error',
                'This account has a registered email address. Ask the user to use Forgot Password and the email verification-code recovery flow instead of generating a temporary password.'
            );
        }

        $temporaryPassword = Str::password(12, true, true, true, false);

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
                    'reset_method' => 'staff_assisted_temporary_password',
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

    private function normalizeEmail(?string $email): ?string
    {
        $email = trim((string) $email);

        return $email === '' ? null : Str::lower($email);
    }

    private function hasRecoveryEmail(User $user): bool
    {
        return filled($user->email)
            && ! str_ends_with(Str::lower($user->email), '@dar-ltcms.local');
    }
}
