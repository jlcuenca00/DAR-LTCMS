<x-staff-shell title="Account Details" active="users">
@php
    $initialTab = $errors->hasAny(['reason', 'confirmation', 'deletion']) || ($errors->any() && old('confirmation')) || session('temporary_password') ? 'security' : 'details';
@endphp
<div class="account-workspace" data-account-tabs data-initial-tab="{{ $initialTab }}">
    @if ($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800 shadow-sm">
            <div class="mb-2 font-black">Please fix the following:</div>
            <ul class="list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


        @if (session('success'))
            <div class="rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-semibold text-green-800 shadow-sm">
                <i class="fa-solid fa-circle-check mr-2"></i>{{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-800 shadow-sm">
                <i class="fa-solid fa-circle-exclamation mr-2"></i>{{ session('error') }}
            </div>
        @endif

        @if (session('temporary_password'))
            <section class="rounded-2xl border border-amber-300 bg-amber-50 px-5 py-4 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[0.16em] text-amber-800">Temporary Password — Shown Once</p>
                <p class="mt-2 text-sm text-amber-900">Give this password securely to <strong>{{ session('temporary_password_username') }}</strong>. The user must replace it after signing in.</p>
                <div class="mt-3 rounded-xl border border-amber-300 bg-white px-4 py-3 font-mono text-lg font-black tracking-wider text-gray-950">{{ session('temporary_password') }}</div>
            </section>
        @endif



    <header class="account-header">
        <div class="account-monogram" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</div>
        <div class="account-identity">
            <h2>{{ $user->name }}</h2>
            <p class="account-muted">{{ $user->email ?? $user->username }}</p>
        </div>
        <div class="account-header-badges">
            <span class="account-role">{{ $user->isStaff() ? 'Legal Clearance Staff' : ucfirst($user->role) }}</span>
            <span class="account-status">{{ $user->account_deleted_at ? 'Deleted' : ($user->is_active ? 'Active' : 'Suspended') }}</span>
        </div>
    </header>
    <nav class="account-tabs" role="tablist" aria-label="Account sections">
        @foreach(['details' => 'Details', 'security' => 'Security', 'activity' => 'Activity'] as $tab => $label)
            <button type="button" role="tab" id="account-tab-{{ $tab }}" aria-controls="account-pane-{{ $tab }}" aria-selected="{{ $initialTab === $tab ? 'true' : 'false' }}" tabindex="{{ $initialTab === $tab ? '0' : '-1' }}" data-account-tab="{{ $tab }}">{{ $label }}</button>
        @endforeach
    </nav>
    <div id="account-pane-details" role="tabpanel" aria-labelledby="account-tab-details" tabindex="0" data-account-pane="details" @if($initialTab !== 'details') hidden @endif>
        @if($user->account_deleted_at)
            <section class="account-panel"><h3>Deleted account</h3><p>This account cannot sign in or be reactivated. Its historical identity is preserved for audit records.</p></section>
        @else
            <form method="POST" action="{{ route('staff.users.update', $user) }}" data-account-edit-form class="account-panel account-details-form">
                @csrf
                @method('PUT')
                <input type="hidden" name="expected_account_revision" value="{{ old('expected_account_revision', $accountRevision) }}">
                <section class="account-form-section">
                    <h3>Profile</h3>
                        <div class="account-fields account-fields-three">
                            <div class="user-field">
                                <label for="field-default-name" class="user-label">Name</label>
                                <input id="field-default-name" type="text" name="name" value="{{ old('name', $user->name) }}" required class="user-input" @error('name', 'default') aria-invalid="true" aria-describedby="field-default-name-server-error" data-ui-server-invalid @enderror>
                                @error('name')
                                    <p id="field-default-name-server-error" class="user-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="user-field">
                                <label for="field-default-username" class="user-label">Username</label>
                                <input id="field-default-username" type="text" name="username" value="{{ old('username', $user->username) }}" required class="user-input" @error('username', 'default') aria-invalid="true" aria-describedby="field-default-username-server-error" data-ui-server-invalid @enderror>
                                @error('username')
                                    <p id="field-default-username-server-error" class="user-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="user-field">
                                <label for="field-default-email" class="user-label">Email Address <span class="normal-case tracking-normal font-medium text-slate-500">(Optional)</span></label>
                                <input id="field-default-email" type="email" name="email" value="{{ old('email', $user->email) }}" class="user-input" autocomplete="email" placeholder="Example: user@example.com" @error('email', 'default') aria-invalid="true" aria-describedby="field-default-email-server-error" data-ui-server-invalid @enderror>
                                <p class="mt-2 text-xs leading-5 text-slate-500">Used for password recovery and account messages.</p>
                                @error('email')
                                    <p id="field-default-email-server-error" class="user-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                </section>
                @if($user->registration_details)
                <section class="account-form-section">
                    <h3>Submitted registration details</h3>
                    <p class="account-muted">Provided by the applicant for DAR review.</p>
                    <dl class="account-facts">
                        <div><dt>Contact number</dt><dd>{{ $user->registration_details['contact_number'] ?? 'Not provided' }}</dd></div>
                        <div><dt>Current address</dt><dd class="break-words">{{ $user->registration_details['address'] ?? 'Not provided' }}</dd></div>
                    </dl>
                </section>
                @endif
                <section class="account-form-section">
                    <h3>Access & linked records</h3>
                        <div class="account-access-fields">
                            <div class="user-field">
                                <label for="field-default-role" class="user-label">Role</label>
                                <select id="field-default-role" name="role" required class="user-select" @disabled(auth()->id() === $user->id) @error('role', 'default') aria-invalid="true" aria-describedby="field-default-role-server-error" data-ui-server-invalid @enderror>
                                    <option value="staff" {{ old('role', $user->role) === 'staff' ? 'selected' : '' }}>Legal Clearance Staff</option>
                                    <option value="landowner" {{ old('role', $user->role) === 'landowner' ? 'selected' : '' }}>Landowner</option>
                                    <option value="geodetic" {{ old('role', $user->role) === 'geodetic' ? 'selected' : '' }}>Geodetic</option>
                                </select>
                                @if(auth()->id() === $user->id)<input type="hidden" name="role" value="{{ $user->role }}">@endif
                                @error('role')
                                    <p id="field-default-role-server-error" class="user-error">{{ $message }}</p>
                                @enderror
                            </div>

                <div class="account-status-field">
                <label for="is_active" class="user-label">Account status</label>
                <select id="is_active" name="is_active" class="user-select" @disabled(auth()->id() === $user->id)>
                    <option value="1" @selected(old('is_active', $user->is_active) == 1)>Active</option>
                    <option value="0" @selected(old('is_active', $user->is_active) == 0)>Suspended</option>
                </select>
                @if(auth()->id() === $user->id)<input type="hidden" name="is_active" value="1"><p class="account-muted">Your own account must remain active.</p>@endif
                </div>
                            <div data-landowner-fields @if(old('role', $user->role) !== 'landowner') hidden @endif>
                            <div class="account-landowner-link">

                                <div class="user-disclosure-panel">
                                    <div class="user-field">
                                        <label for="field-default-landowner_id" class="user-label">Linked Landowner Record</label>
                                        <div data-remote-record-select data-lookup-url="{{ route('staff.lookups.landowners', ['scope' => 'user-link', 'current_user_id' => $user->id]) }}" class="space-y-2">
                                            <input type="search"
                                                   class="user-input"
                                                   placeholder="Search landowner name or record ID"
                                                   autocomplete="off"
                                                   data-remote-record-search>
                                            <select id="field-default-landowner_id" name="landowner_id"
                                                    class="user-select"
                                                    data-remote-record-control
                                                    data-placeholder="No linked landowner record" @error('landowner_id', 'default') aria-invalid="true" aria-describedby="field-default-landowner_id-server-error" data-ui-server-invalid @enderror>
                                                <option value="">No linked landowner record</option>
                                                @if ($selectedLandowner)
                                                    <option value="{{ $selectedLandowner->id }}" selected>
                                                        {{ $selectedLandowner->full_name }} — ID {{ $selectedLandowner->id }}
                                                    </option>
                                                @endif
                                            </select>
                                            <p class="user-card-copy" data-remote-record-status>Search by name, then select the correct DAR record.</p>
                                        </div>
                                        @error('landowner_id')
                                            <p id="field-default-landowner_id-server-error" class="user-error">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="user-field">
                                <label for="field-default-registration_status" class="user-label">Landowner Registration Review</label>
                                <select id="field-default-registration_status" name="registration_status" class="user-select" @error('registration_status', 'default') aria-invalid="true" aria-describedby="field-default-registration_status-server-error" data-ui-server-invalid @enderror>
                                    @foreach (\App\Models\User::REGISTRATION_STATUSES as $status)
                                        <option value="{{ $status }}" @selected(old('registration_status', $user->registration_status) === $status)>
                                            {{ str_replace('_', ' ', ucwords($status, '_')) }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="mt-2 text-xs leading-5 text-slate-500">Approve only after DAR staff verifies the landowner identity and links the correct landowner record.</p>
                                @error('registration_status')
                                    <p id="field-default-registration_status-server-error" class="user-error">{{ $message }}</p>
                                @enderror
                                </div>
                        </div>
                        </div>

                </section>
                <footer class="account-save">
                    <span data-account-unsaved role="status" hidden>Unsaved changes</span>
                    <button type="submit" class="staff-button staff-button-primary">Save Changes</button>
                    <a href="{{ route('staff.users.index') }}" class="staff-button staff-button-light">Cancel</a>
                </footer>
            </form>
        @endif
    </div>
    <div id="account-pane-security" role="tabpanel" aria-labelledby="account-tab-security" tabindex="0" data-account-pane="security" @if($initialTab !== 'security') hidden @endif>
        @if($user->account_deleted_at)
            <section class="account-panel"><h3>Sign-in disabled</h3><p>The deleted account’s email and Google sign-in have been released. No further account actions are available.</p></section>
        @else
        <section class="account-panel"><header><h3>Sign-in & security</h3><p>Manage recovery and end existing sessions.</p></header>
            <dl class="account-facts"><div><dt>Sign-in method</dt><dd>{{ $user->google_id ? 'Google' : 'Username and password' }}</dd></div><div><dt>Recovery email</dt><dd>{{ $user->email ? ($user->email_verified_at ? 'Verified' : 'Not verified') : 'Not provided' }}</dd></div><div><dt>Last sign-in</dt><dd>{{ $user->last_login_at?->timezone('Asia/Manila')->format('M j, Y · g:i A') ?? 'Never signed in' }}</dd></div><div><dt>Created</dt><dd>{{ $user->created_at?->timezone('Asia/Manila')->format('M j, Y') }}</dd></div></dl>
            @if(auth()->id() !== $user->id)
            <div class="account-action-row"><div><h4>End existing sessions</h4><p>The user will need to sign in again. Their password stays the same.</p></div><form method="POST" action="{{ route('staff.users.revoke-sessions', $user) }}" onsubmit="return confirm('Sign this user out of existing sessions?')">@csrf<input type="hidden" name="expected_account_revision" value="{{ $accountRevision }}"><button class="staff-button staff-button-light">Revoke Sessions</button></form></div>
            <div class="account-action-row"><div><h4>Password recovery</h4><p>{{ $user->email && $user->email_verified_at ? 'This user can recover their password through Forgot Password.' : 'Generate a temporary password that must be changed at the next sign-in.' }}</p></div>
            @unless($user->email && $user->email_verified_at)
            <form id="staff-password-reset-form" method="POST" action="{{ route('staff.users.reset-password', $user) }}" onsubmit="return confirm('Generate a temporary password for this user?')">@csrf<button class="staff-button staff-button-light">Reset Password</button></form>
            @endunless</div>
            @else<a class="staff-button staff-button-light" href="{{ route('profile.edit') }}">Open My Profile</a>@endif
        </section>
        <section class="account-panel account-danger"><header><h3>Delete account</h3><p>Remove an unused account and release its email and Google sign-in for registration again.</p></header>
            @if($deletionBlockedReason)<p>{{ $deletionBlockedReason }}</p>
            @else
            <details @if($errors->hasAny(['reason', 'confirmation', 'deletion'])) open @endif><summary class="account-delete-trigger">Delete account…</summary>
                <form method="POST" action="{{ route('staff.users.destroy', $user) }}" class="account-delete-form">@csrf @method('DELETE')
                    <input type="hidden" name="expected_account_revision" value="{{ $accountRevision }}">
                    <p>This cannot be undone. Existing audit history is preserved; the old account cannot be restored. This does not revoke Google's consent grant.</p>
                    <label for="deletion-reason">Reason</label><textarea id="deletion-reason" name="reason" maxlength="1000" required class="user-input">{{ old('reason') }}</textarea>
                    <label for="deletion-confirmation">Type DELETE to confirm deletion of {{ $user->username }}</label><input id="deletion-confirmation" name="confirmation" required pattern="DELETE" autocomplete="off" class="user-input">
                    <button type="submit" class="account-delete-button">Delete Account</button>
                </form>
            </details>
            @endif
        </section>

        @endif
    </div>
    <div id="account-pane-activity" role="tabpanel" aria-labelledby="account-tab-activity" tabindex="0" data-account-pane="activity" hidden>
        <section class="account-panel"><header><h3>Account history</h3><p>Latest account changes and management actions.</p></header><ol class="account-timeline">@forelse($accountHistory as $event)<li><strong>{{ ucfirst(str_replace('_', ' ', $event->action)) }}</strong><span>{{ $event->actor_name_snapshot ?? 'System' }} · {{ $event->created_at->timezone('Asia/Manila')->format('M j, Y · g:i A') }}</span></li>@empty<li>No account management actions recorded.</li>@endforelse</ol></section>

    </div>
</div>
</x-staff-shell>
