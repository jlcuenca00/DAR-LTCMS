<div class="profile-stack">
    <section class="profile-hero">
        <div class="profile-hero-main">
            <div class="profile-hero-icon">
                <i class="fa-solid fa-user-gear"></i>
            </div>

            <div>
                <p class="profile-eyebrow">{{ $context['portal'] }}</p>
                <h2 class="profile-title">Account and Password Settings</h2>
                <p class="profile-copy">{{ $context['note'] }}</p>
            </div>
        </div>

        <span class="profile-badge">{{ $context['badge'] }}</span>
    </section>

    <div class="profile-grid">
        <div class="profile-column">
            <section class="profile-panel">
                <div class="profile-panel-header">
                    <h3 class="profile-panel-title">Profile Information</h3>
                    <p class="profile-panel-subtitle">Update your name and contact email. Your username is managed by authorized DAR staff.</p>
                </div>

                <div class="profile-panel-body">
                    <form method="post" action="{{ route('profile.update') }}" class="profile-form" enctype="multipart/form-data">
                        @csrf
                        @method('patch')

                        <div class="profile-form-grid">
                            <div class="profile-field full">
                                <label class="profile-label" for="profile_photo">Profile Picture</label>
                                <div class="profile-photo-control">
                                    <div class="profile-photo-preview">
                                        @if ($user->profile_photo_path)
                                            <img src="{{ asset('storage/' . ltrim($user->profile_photo_path, '/')) }}" alt="Profile picture for {{ $user->name }}">
                                        @else
                                            {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                                        @endif
                                    </div>
                                    <div class="profile-photo-actions">
                                        <input id="profile_photo" name="profile_photo" type="file" accept="image/jpeg,image/png,image/webp" class="profile-input">
                                        <div class="text-xs text-gray-500">JPEG, PNG, or WebP. Maximum 2 MB.</div>
                                        @if ($user->profile_photo_path)
                                            <label class="profile-photo-remove">
                                                <input type="checkbox" name="remove_profile_photo" value="1">
                                                Remove current profile picture
                                            </label>
                                        @endif
                                        @error('profile_photo')
                                            <div class="profile-error">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="profile-field">
                                <label class="profile-label" for="name">Name</label>
                                <input id="name" name="name" type="text" class="profile-input" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
                                @error('name')
                                    <div class="profile-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="profile-field">
                                <label class="profile-label" for="profile_username">Username</label>
                                <input id="profile_username" type="text" class="profile-input" value="{{ $user->username }}" readonly aria-readonly="true">
                                <div class="mt-1 text-xs text-gray-500">Contact authorized DAR staff to change the username.</div>
                            </div>

                            <div class="profile-field full">
                                <label class="profile-label" for="email">Email Address (Optional)</label>
                                <input id="email" name="email" type="email" class="profile-input" value="{{ old('email', $user->email) }}" autocomplete="email">
                                @error('email')
                                    <div class="profile-error">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="profile-actions">
                            <button type="submit" class="profile-button">
                                <i class="fa-solid fa-floppy-disk"></i>
                                Save Profile
                            </button>

                            @if (session('status') === 'profile-updated')
                                <span class="profile-saved">Saved.</span>
                            @endif
                        </div>
                    </form>
                </div>
            </section>

            <section class="profile-panel">
                <div class="profile-panel-header">
                    <h3 class="profile-panel-title">Change Password</h3>
                    <p class="profile-panel-subtitle">Enter your current password, then create a new password for your username-based account.</p>
                </div>

                <div class="profile-panel-body">
                    <form method="post" action="{{ route('password.update') }}" class="profile-form">
                        @csrf
                        @method('put')

                        <div class="profile-form-grid">
                            <div class="profile-field full">
                                <label class="profile-label" for="update_password_current_password">Current Password</label>
                                <input id="update_password_current_password" name="current_password" type="password" class="profile-input" autocomplete="current-password">
                                @error('current_password', 'updatePassword')
                                    <div class="profile-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="profile-field">
                                <label class="profile-label" for="update_password_password">New Password</label>
                                <input id="update_password_password" name="password" type="password" class="profile-input" autocomplete="new-password">
                                @error('password', 'updatePassword')
                                    <div class="profile-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="profile-field">
                                <label class="profile-label" for="update_password_password_confirmation">Confirm Password</label>
                                <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="profile-input" autocomplete="new-password">
                                @error('password_confirmation', 'updatePassword')
                                    <div class="profile-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <x-password-requirements
                                password-id="update_password_password"
                                confirmation-id="update_password_password_confirmation"
                            />
                        </div>
OLD, 'add live password checklist to profile');

    // ---------------------------------------------------------------------
    // 9) Forced first-login password UI + staff-created account UI.
    // ---------------------------------------------------------------------
    replaceLiteral('resources/views/auth/force-password-change.blade.php', <<<'OLD'
                <p class="note">Use at least eight characters. Do not reuse the temporary password or share the new password with staff.</p>
OLD, <<<'NEW'
                <x-password-requirements password-id="password" confirmation-id="password_confirmation" />

                <p class="note">Use at least eight characters with uppercase, lowercase, a number, and a symbol. Do not reuse the temporary password or share the new password with staff.</p>

                        <div class="profile-actions">
                            <button type="submit" class="profile-button">
                                <i class="fa-solid fa-key"></i>
                                Change Password
                            </button>

                            @if (session('status') === 'password-updated')
                                <span class="profile-saved">Saved.</span>
                            @endif
                        </div>
                    </form>
                </div>
            </section>
        </div>

        <aside class="profile-panel">
            <div class="profile-panel-header">
                <h3 class="profile-panel-title">Account Access Notes</h3>
                <p class="profile-panel-subtitle">Profile changes do not alter your username, role, or permissions.</p>
            </div>

            <div class="profile-panel-body">
                <ul class="profile-note-list">
                    <li class="profile-note-item">
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>Access remains controlled by your assigned system role and account status.</span>
                    </li>
                    <li class="profile-note-item">
                        <i class="fa-solid fa-user-lock"></i>
                        <span>Changing profile details does not grant additional permissions or record access.</span>
                    </li>
                    <li class="profile-note-item">
                        <i class="fa-solid fa-file-signature"></i>
                        <span>All operational actions remain subject to role-based controls and auditability rules.</span>
                    </li>
                    <li class="profile-note-item">
                        <i class="fa-solid fa-building-shield"></i>
                        <span>Role assignment, deactivation, and account recovery are handled by authorized DAR staff.</span>
                    </li>
                </ul>
            </div>
        </aside>
    </div>
</div>
