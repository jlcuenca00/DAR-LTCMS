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
                    <p class="profile-panel-subtitle">Update the name and email address used for your system login.</p>
                </div>

                <div class="profile-panel-body">
                    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
                        @csrf
                    </form>

                    <form method="post" action="{{ route('profile.update') }}" class="profile-form">
                        @csrf
                        @method('patch')

                        <div class="profile-form-grid">
                            <div class="profile-field">
                                <label class="profile-label" for="name">Name</label>
                                <input id="name" name="name" type="text" class="profile-input" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
                                @error('name')
                                    <div class="profile-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="profile-field">
                                <label class="profile-label" for="email">Email Address</label>
                                <input id="email" name="email" type="email" class="profile-input" value="{{ old('email', $user->email) }}" required autocomplete="username">
                                @error('email')
                                    <div class="profile-error">{{ $message }}</div>
                                @enderror

                                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                                    <div class="profile-verify-box">
                                        Your email address is unverified.
                                        <button form="send-verification" class="profile-inline-button">Resend verification email</button>.

                                        @if (session('status') === 'verification-link-sent')
                                            <div class="profile-saved" style="margin-top: 6px;">A new verification link has been sent.</div>
                                        @endif
                                    </div>
                                @endif
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
                    <h3 class="profile-panel-title">Update Password</h3>
                    <p class="profile-panel-subtitle">Use a secure password to protect system access and preserve account accountability.</p>
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
                        </div>

                        <div class="profile-actions">
                            <button type="submit" class="profile-button">
                                <i class="fa-solid fa-key"></i>
                                Save Password
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
                <p class="profile-panel-subtitle">Profile changes affect login identity only.</p>
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
