<x-public-auth-layout title="Complete Landowner Registration">
    <div class="registration-header">
        <p class="registration-help text-center">Google connected · Complete your details</p>
        <h1 class="auth-heading">Tell us about yourself</h1>
        <p class="auth-intro">Confirm your full name and contact details so DAR staff can review your registration.</p>
    </div>

    <div class="registration-consent-block mb-4">
        <span class="form-label">Google account</span>
        <p class="break-words">{{ $identity['email'] }}</p>
        <p class="registration-help">This email will be used for your account. You will sign in with Google.</p>
    </div>

    <form method="POST" action="{{ route('register.google.complete') }}" class="registration-form">
        @csrf
        @include('auth.partials.landowner-details', ['initialName' => $identity['name']])
        <div class="registration-consent-block">
            <label class="consent">
                <input type="checkbox" name="privacy_consent" value="1" required @checked(old('privacy_consent'))>
                <span>I confirm these details are correct, consent to their use for registration and identity review under the <a href="{{ route('privacy') }}" target="_blank" rel="noopener">Privacy Policy</a>, and agree to the <a href="{{ route('terms') }}" target="_blank" rel="noopener">Terms of Service</a>.</span>
            </label>
            <x-input-error :messages="$errors->get('privacy_consent')" class="mt-2" />
        </div>
        <p class="registration-help">Your account will remain pending until DAR staff reviews your registration and links the correct landowner record.</p>
        <button type="submit" class="login-button registration-submit">Submit for DAR review</button>
    </form>
    <form method="POST" action="{{ route('register.google.cancel') }}" class="registration-signin">
        @csrf
        <button type="submit" class="underline">Cancel and choose another sign-up method</button>
    </form>
</x-public-auth-layout>
