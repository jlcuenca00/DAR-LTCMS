<x-public-auth-layout title="Landowner Registration" :wide="true">
    <div class="registration-header">
        <h1 class="auth-heading">Create your landowner account</h1>
        <p class="auth-intro">Submit your details for DAR staff verification.</p>
    </div>
    @if ($errors->has('google'))<p class="signup-error" role="alert">{{ $errors->first('google') }}</p>@endif
    <div data-signup-method class="signup-method">
        <p class="signup-preparation"><strong>Before you start</strong><br>Prepare a valid ID and a clear selfie. You can take your selfie using your camera.</p>
    @if ($googleClientId)
        <div class="registration-google-section">
            <p class="registration-help">By continuing with Google, you consent to sharing your Google name and email for registration under our <a href="{{ route('privacy') }}" target="_blank" rel="noopener">Privacy Policy</a>.</p>

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
                <input id="google-registration-consent" type="hidden" name="privacy_consent" value="1">
            </form>
        </div>
    @endif


        @if($googleClientId)<div class="registration-divider" aria-hidden="true"><span></span><span>or use email</span><span></span></div>@endif
        <button type="button" class="login-button" data-signup-email hidden>Sign up with email</button>
    </div>
    @include('auth.partials.registration-wizard', ['googleSignup' => false])
    <div class="registration-signin"><span>Already registered?</span><a href="{{ route('login') }}">Sign in</a></div>
    @if ($googleClientId)
        <script>
            window.handleGoogleRegistration = function (response) {
                document.getElementById('google-registration-credential').value = response.credential;
                document.getElementById('google-registration-form').submit();
            };
        </script>
        <script src="https://accounts.google.com/gsi/client?hl=en" async defer></script>
    @endif
</x-public-auth-layout>
