@php
    $profileInitialTab = $errors->getBag('updatePassword')->any() || session('status') === 'password-updated' ? 'security' : 'personal';
@endphp
<div class="profile-stack" data-account-tabs data-initial-tab="{{ $profileInitialTab }}">
    <header class="account-header">
        <div class="account-monogram" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</div>
        <div class="account-identity"><h2>{{ $user->name }}</h2><p class="account-muted">{{ $user->email ?? $user->username }}</p></div>
        <div class="account-header-badges"><span class="account-role">{{ $context['badge'] }}</span><span class="account-status">{{ $user->is_active ? 'Active' : 'Suspended' }}</span></div>
    </header>
        <details class="account-access-summary"><summary>My access</summary>
            <div class="profile-panel-header"><h3 class="profile-panel-title">My access</h3><p class="profile-panel-subtitle">{{ $context['portal'] }}</p></div>
            <div class="profile-panel-body">
                <dl class="account-facts">
                    <div><dt>Account status</dt><dd>{{ $user->is_active ? 'Active' : 'Suspended' }}</dd></div>
                    <div><dt>Sign-in method</dt><dd>{{ $user->google_id ? 'Google' : 'Username and password' }}</dd></div>
                    <div><dt>Email</dt><dd>{{ $user->email ? ($user->email_verified_at ? 'Verified' : 'Not verified') : 'Not provided' }}</dd></div>
                    <div><dt>Member since</dt><dd>{{ $user->created_at?->timezone('Asia/Manila')->format('M j, Y') }}</dd></div>
                </dl>
                @if($user->isLandowner())
                    <h4 class="account-section-title">Linked DAR record</h4><p class="profile-copy">{{ $user->landowner?->full_name ?? 'No landowner record linked. Contact DAR staff for assistance.' }}</p>
                    <p class="profile-copy">Your account can view only your linked records and application status.</p>
                @elseif($user->isGeodetic())
                    <h4 class="account-section-title">Parcel review access</h4><p class="profile-copy">View parcel and map information. Account management and clearance decisions are handled by Legal Clearance Staff.</p>
                @else
                    <h4 class="account-section-title">Legal Clearance Staff</h4><p class="profile-copy">Manage clearance processing and authorized records.</p><a href="{{ route('staff.users.index') }}" class="profile-button secondary" style="margin-top:16px">User Management</a>
                @endif
            </div>
        </details>
    <nav class="account-tabs" role="tablist" aria-label="Profile sections">
        @foreach(['personal' => 'Personal Details', 'security' => 'Security'] as $tab => $label)
            <button type="button" role="tab" id="profile-tab-{{ $tab }}" aria-controls="profile-pane-{{ $tab }}" aria-selected="{{ $profileInitialTab === $tab ? 'true' : 'false' }}" tabindex="{{ $profileInitialTab === $tab ? '0' : '-1' }}" data-account-tab="{{ $tab }}">{{ $label }}</button>
        @endforeach
    </nav>
    <div id="profile-pane-personal" role="tabpanel" aria-labelledby="profile-tab-personal" tabindex="0" data-account-pane="personal" @if($profileInitialTab !== 'personal') hidden @endif>
            <section id="profile-information" class="profile-panel">
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

                                <div class="profile-photo-control profile-photo-control-clean" data-profile-photo-editor>
                                    <div class="profile-photo-current" data-profile-photo-current>
                                        @if ($profilePhotoExists ?? false)
                                            <img
                                                src="{{ route('profile.photo', $user) }}"
                                                alt="Profile picture for {{ $user->name }}"
                                                data-profile-photo-current-image
                                            >
                                            <div class="profile-photo-empty" data-profile-photo-empty hidden>
                                                <i class="fa-regular fa-user" aria-hidden="true"></i>
                                                <span>No photo</span>
                                            </div>
                                        @else
                                            <img
                                                src=""
                                                alt="Profile picture preview for {{ $user->name }}"
                                                data-profile-photo-current-image
                                                hidden
                                            >
                                            <div class="profile-photo-empty" data-profile-photo-empty>
                                                <i class="fa-regular fa-user" aria-hidden="true"></i>
                                                <span>No photo</span>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="profile-photo-actions">
                                        <button type="button" class="profile-button secondary profile-photo-choose" data-profile-photo-choose>
                                            <i class="fa-solid fa-image" aria-hidden="true"></i>
                                            Choose Photo
                                        </button>

                                        <input
                                            id="profile_photo"
                                            name="profile_photo"
                                            type="file"
                                            accept="image/jpeg,image/png,image/webp"
                                            class="profile-photo-hidden-input"
                                            data-profile-photo-input
                                         @error('profile_photo', 'default') aria-invalid="true" aria-describedby="profile_photo-server-error" data-ui-server-invalid @enderror>

                                        <p class="profile-photo-help">
                                            JPEG, PNG, or WebP. After choosing a file, crop and resize it before saving your profile.
                                        </p>

                                        @if ($user->profile_photo_path)
                                            @unless ($profilePhotoExists ?? false)
                                                <p class="text-xs font-semibold text-amber-700">
                                                    The previously stored photo file is unavailable. Choose a replacement or remove the missing photo reference.
                                                </p>
                                            @endunless

                                            <label class="profile-photo-remove">
                                                <input type="checkbox" name="remove_profile_photo" value="1">
                                                Remove current profile picture
                                            </label>
                                        @endif

                                        @error('profile_photo')
                                            <div id="profile_photo-server-error" class="profile-error">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="profile-crop-modal" data-profile-crop-modal hidden>
                                        <div class="profile-crop-dialog" role="dialog" aria-modal="true" aria-labelledby="profile-crop-title">
                                            <header class="profile-crop-header">
                                                <div>
                                                    <h3 id="profile-crop-title">Crop Profile Picture</h3>
                                                    <p>Drag the image to reposition it, then use the zoom control.</p>
                                                </div>

                                                <button type="button" class="profile-crop-close" data-profile-crop-cancel aria-label="Close profile photo cropper">
                                                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                                </button>
                                            </header>

                                            <div class="profile-crop-body">
                                                <div class="profile-crop-stage" data-profile-crop-stage>
                                                    <img src="" alt="Image being cropped" data-profile-crop-image draggable="false">
                                                    <div class="profile-crop-guide" aria-hidden="true"></div>
                                                </div>

                                                <div class="profile-crop-controls">
                                                    <div class="profile-crop-control-heading">
                                                        <label for="profile_crop_zoom">Zoom</label>
                                                        <output for="profile_crop_zoom" data-profile-crop-zoom-output>100%</output>
                                                    </div>

                                                    <input
                                                        id="profile_crop_zoom"
                                                        type="range"
                                                        min="1"
                                                        max="3"
                                                        step="0.01"
                                                        value="1"
                                                        data-profile-crop-zoom
                                                    >

                                                    <p>The square inside the editor is exactly what will be saved as your profile picture.</p>
                                                </div>
                                            </div>

                                            <footer class="profile-crop-footer">
                                                <button type="button" class="profile-button secondary" data-profile-crop-cancel>
                                                    Cancel
                                                </button>
                                                <button type="button" class="profile-button" data-profile-crop-save>
                                                    <i class="fa-solid fa-crop-simple" aria-hidden="true"></i>
                                                    Save Crop
                                                </button>
                                            </footer>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="profile-field">
                                <label class="profile-label" for="name">Name</label>
                                <input id="name" name="name" type="text" class="profile-input" value="{{ old('name', $user->name) }}" required autocomplete="name" @error('name', 'default') aria-invalid="true" aria-describedby="name-server-error" data-ui-server-invalid @enderror>
                                @error('name')
                                    <div id="name-server-error" class="profile-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="profile-field">
                                <label class="profile-label" for="profile_username">Username</label>
                                <input id="profile_username" type="text" class="profile-input" value="{{ $user->username }}" readonly aria-readonly="true">
                                <div class="mt-1 text-xs text-gray-500">Contact authorized DAR staff to change the username.</div>
                            </div>

                            <div class="profile-field full">
                                <label class="profile-label" for="email">Email Address (Optional)</label>
                                <input id="email" name="email" type="email" class="profile-input" value="{{ old('email', $user->email) }}" autocomplete="email" @error('email', 'default') aria-invalid="true" aria-describedby="email-server-error" data-ui-server-invalid @enderror>
                                @error('email')
                                    <div id="email-server-error" class="profile-error">{{ $message }}</div>
                                @enderror

                                @if ($user->email && $user->email_verified_at === null)
                                    <div class="profile-verify-box">
                                        This email is not verified and cannot be used for password recovery yet.
                                    </div>
                                @endif
                            </div>

                            <div class="profile-field full">
                                <label class="profile-label" for="profile_current_password">Current Password for Email Changes</label>
                                <input id="profile_current_password"
                                       name="current_password"
                                       type="password"
                                       class="profile-input"
                                       autocomplete="current-password"
                                       placeholder="Required only when changing the email address" @error('current_password', 'default') aria-invalid="true" aria-describedby="profile_current_password-server-error" data-ui-server-invalid @enderror>
                                <div class="mt-1 text-xs text-gray-500">
                                    Name and profile-photo changes do not require this. Google-only users without a local password should contact authorized DAR staff to change their account email.
                                </div>
                                @error('current_password')
                                    <div id="profile_current_password-server-error" class="profile-error">{{ $message }}</div>
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

                            @if (session('email_verification_status'))
                                <span class="profile-saved">{{ session('email_verification_status') }}</span>
                            @endif
                        </div>
                    </form>
                </div>
            </section>


    </div>
    <div id="profile-pane-security" role="tabpanel" aria-labelledby="profile-tab-security" tabindex="0" data-account-pane="security" @if($profileInitialTab !== 'security') hidden @endif>
            <section id="profile-security" class="profile-panel">
                <div class="profile-panel-header">
                    <h3 class="profile-panel-title">Change Password</h3>
                    <p class="profile-panel-subtitle">Enter your current DAR-LTCMS password, then create a new one.</p>
                    @if($user->google_id)<p class="profile-panel-subtitle">Your Google password is managed by Google. If you have never set a DAR-LTCMS password, use Forgot Password with your verified recovery email or contact DAR staff.</p>@endif
                </div>

                <div class="profile-panel-body">
                    <form method="post" action="{{ route('password.update') }}" class="profile-form">
                        @csrf
                        @method('put')

                        <div class="profile-form-grid">
                            <div class="profile-field full">
                                <label class="profile-label" for="update_password_current_password">Current Password</label>
                                <input id="update_password_current_password" name="current_password" type="password" class="profile-input" autocomplete="current-password" @error('current_password', 'updatePassword') aria-invalid="true" aria-describedby="update_password_current_password-server-error" data-ui-server-invalid @enderror>
                                @error('current_password', 'updatePassword')
                                    <div id="update_password_current_password-server-error" class="profile-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="profile-field">
                                <label class="profile-label" for="update_password_password">New Password</label>
                                <input id="update_password_password" name="password" type="password" class="profile-input" autocomplete="new-password" @error('password', 'updatePassword') aria-invalid="true" aria-describedby="update_password_password-server-error" data-ui-server-invalid @enderror>
                                @error('password', 'updatePassword')
                                    <div id="update_password_password-server-error" class="profile-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="profile-field">
                                <label class="profile-label" for="update_password_password_confirmation">Confirm Password</label>
                                <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="profile-input" autocomplete="new-password" @error('password_confirmation', 'updatePassword') aria-invalid="true" aria-describedby="update_password_password_confirmation-server-error" data-ui-server-invalid @enderror>
                                @error('password_confirmation', 'updatePassword')
                                    <div id="update_password_password_confirmation-server-error" class="profile-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <x-password-requirements
                                password-id="update_password_password"
                                confirmation-id="update_password_password_confirmation"
                            />
                        </div>

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
</div>
