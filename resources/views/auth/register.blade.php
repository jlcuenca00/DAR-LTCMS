<x-public-auth-layout title="Landowner Registration">
    <div class="registration-header">
        <h1 class="auth-heading">Landowner Registration</h1>
        <p class="auth-intro">
            Create an account for DAR review. Your records stay locked until staff verifies your identity and links the correct landowner record.
        </p>
    </div>

    @if ($errors->has('google'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first('google') }}
        </div>
    @endif

    <form id="manual-registration-form" method="POST" action="{{ route('register') }}" class="registration-form">
        @csrf

        <div class="registration-field">
            <x-input-label class="form-label" for="name" :value="__('Full name')" />
            <x-text-input id="name" class="form-input" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="registration-field">
            <x-input-label class="form-label" for="username" :value="__('Username')" />
            <x-text-input id="username" class="form-input" type="text" name="username" :value="old('username')" required autocomplete="username" />
            <p class="registration-help">Use letters, numbers, dashes, or underscores. You will use this to sign in.</p>
            <x-input-error :messages="$errors->get('username')" class="mt-2" />
        </div>

        <div class="registration-field">
            <x-input-label class="form-label" for="email" :value="__('Email (optional)')" />
            <x-text-input id="email" class="form-input" type="email" name="email" :value="old('email')" autocomplete="email" />
            <p class="registration-help">Leave this blank if you do not use email. Password recovery will require help from DAR staff.</p>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="registration-field">
            <x-input-label class="form-label" for="password" :value="__('Password')" />
            <x-text-input id="password" class="form-input" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="registration-field">
            <x-input-label class="form-label" for="password_confirmation" :value="__('Confirm password')" />
            <x-text-input id="password_confirmation" class="form-input" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="registration-consent-block">
            <label class="consent">
                <input id="registration-privacy-consent" form="manual-registration-form" type="checkbox" name="privacy_consent" value="1" class="rounded border-gray-300 text-green-700 focus:ring-green-600" @checked(old('privacy_consent')) required>
                <span>I have read the <a href="{{ route('privacy') }}" target="_blank" rel="noopener">Privacy Policy</a>, consent to the use of my account details for registration and identity review, and agree to the <a href="{{ route('terms') }}" target="_blank" rel="noopener">Terms of Service</a>.</span>
            </label>
            <x-input-error :messages="$errors->get('privacy_consent')" class="mt-2" />
        </div>

        <button type="submit" class="login-button registration-submit">
            {{ __('Create Landowner Account') }}
        </button>

    </form>

    @if ($googleClientId)
        <div class="registration-divider" aria-hidden="true">
            <span></span>
            <span>or</span>
            <span></span>
        </div>

        <div class="registration-google-section">
            <p id="google-consent-error" role="alert" class="hidden registration-google-error">
                Please accept the privacy notice above before continuing with Google.
            </p>

            <div class="flex justify-center">
                <div id="g_id_onload"
                    data-client_id="{{ $googleClientId }}"
                    data-callback="handleGoogleRegistration"
                    data-ux_mode="popup"
                    data-auto_prompt="false">
                </div>
                <div class="g_id_signin"
                    data-type="standard"
                    data-size="large"
                    data-theme="outline"
                    data-text="continue_with"
                    data-shape="pill"
                    data-locale="en"
                    data-logo_alignment="left">
                </div>
            </div>

            <form id="google-registration-form" method="POST" action="{{ route('register.google') }}" class="hidden">
                @csrf
                <input id="google-registration-credential" type="hidden" name="credential">
                <input type="hidden" name="intent" value="register">
                <input id="google-registration-consent" type="hidden" name="privacy_consent" value="0">
            </form>
        </div>
    @endif

    <div class="registration-signin">
        <span>Already registered?</span>
        <a href="{{ route('login') }}">{{ __('Sign in') }}</a>
    </div>

    @if ($googleClientId)
        <script>
            window.handleGoogleRegistration = function (response) {
                const consent = document.getElementById('registration-privacy-consent');
                const consentError = document.getElementById('google-consent-error');

                if (! consent.checked) {
                    consentError.classList.remove('hidden');
                    consent.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    consent.focus();
                    return;
                }

                consentError.classList.add('hidden');
                document.getElementById('google-registration-credential').value = response.credential;
                document.getElementById('google-registration-consent').value = '1';
                document.getElementById('google-registration-form').submit();
            };
        </script>
        <script src="https://accounts.google.com/gsi/client?hl=en" async defer></script>
    @endif
</x-public-auth-layout>
