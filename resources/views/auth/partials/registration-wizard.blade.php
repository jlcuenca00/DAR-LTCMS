<div data-registration-wizard data-has-errors="{{ $errors->any() ? 'true' : 'false' }}">
    <div class="signup-progress" data-signup-progress hidden>
        <p data-step-status role="status">Step 1 of 4 · Your details</p>
        <ol aria-label="Registration progress">
            @foreach(['Your details', 'Valid ID', 'Selfie', 'Review'] as $step)
                <li data-step-marker><span>{{ $loop->iteration }}</span>{{ $step }}</li>
            @endforeach
        </ol>
    </div>
    <form id="manual-registration-form" method="POST" action="{{ $googleSignup ? route('register.google.complete') : route('register') }}" class="registration-form signup-form" enctype="multipart/form-data" data-signup-form data-registration-evidence>
        @csrf
        <section data-signup-step data-step-title="Your details">
            <h2 tabindex="-1">Your details</h2>
            <p class="step-intro">Use the details shown on your official documents.</p>
            @if($googleSignup)
                <div class="registration-consent-block"><strong>Google connected</strong><p data-google-email>{{ $identity['email'] }}</p><p class="registration-help">You will sign in with Google. No separate password is needed.</p></div>
            @else
                <div class="signup-credentials">@include('auth.partials.registration-credentials')</div>
            @endif
            <div class="signup-personal">@include('auth.partials.landowner-details', ['initialName' => $googleSignup ? $identity['name'] : ''])</div>
        </section>
        <section data-signup-step data-step-title="Valid ID">
            <h2 tabindex="-1">Upload your valid ID</h2>
            <p class="step-intro">Keep all edges visible and the text readable. Avoid glare or blurry photos.</p>
    <div class="registration-field">
        <x-input-label class="form-label" for="id_type" value="ID type" />
        <select id="id_type" name="id_type" class="form-input" required>
            <option value="">Select an ID</option>
        
            @include('auth.partials.registration-upload', ['kind' => 'id_front', 'label' => 'ID front / passport information page'])
            @include('auth.partials.registration-upload', ['kind' => 'id_back', 'label' => 'ID back (optional)'])
        </section>
        <section data-signup-step data-step-title="Selfie">
            <h2 tabindex="-1">Add your selfie</h2>
            <p class="step-intro">Face the camera in good lighting. DAR staff will compare your selfie with your ID.</p>
    <div class="registration-camera">
        <p class="registration-help">For a camera selfie, face the camera in good lighting with your face fully visible. You can upload a clear selfie below if your camera is unavailable.</p>
        <button type="button" class="registration-camera-button" data-selfie-start>Open selfie camera / retake</button>
        <video data-selfie-video autoplay playsinline muted hidden></video>
        <div data-selfie-controls hidden>
            <button type="button" class="registration-camera-button" data-selfie-capture>Capture selfie</button>
            <button type="button" class="registration-camera-button" data-selfie-stop>Close camera</button>
        </div>
        <p data-selfie-status role="status" class="registration-help"></p>
    </div>

            @include('auth.partials.registration-upload', ['kind' => 'selfie', 'label' => 'Upload a selfie instead'])
        </section>
        <section data-signup-step data-step-title="Review">
            <h2 tabindex="-1">Review and submit</h2>
            <p class="step-intro">Check your details and photos before sending them to DAR staff.</p>
            <div data-signup-review></div>
            <div class="registration-consent-block signup-consents">
                <label class="consent"><input type="checkbox" name="identity_consent" value="1" required @checked(old('identity_consent'))><span>I consent to DAR staff reviewing my ID and selfie for account identity verification under the <a href="{{ route('privacy') }}" target="_blank" rel="noopener">Privacy Policy</a>.</span></label>
                <x-input-error :messages="$errors->get('identity_consent')" class="mt-2" />
                <label class="consent"><input id="registration-privacy-consent" form="manual-registration-form" type="checkbox" name="privacy_consent" value="1" required @checked(old('privacy_consent'))><span>I have read the <a href="{{ route('privacy') }}" target="_blank" rel="noopener">Privacy Policy</a>, consent to the use of my account details for registration and identity review, and agree to the <a href="{{ route('terms') }}" target="_blank" rel="noopener">Terms of Service</a>.</span></label>
                <x-input-error :messages="$errors->get('privacy_consent')" class="mt-2" />
            </div>
            <p class="registration-help">Please allow a few business days for verification. You will receive an email with the review result.</p>
        </section>
        @if($errors->any())<p class="signup-error" role="alert" data-server-errors>Please correct the highlighted fields. Select your ID photos and selfie again before submitting.</p>@endif
        <div class="signup-actions">
            <button type="button" class="signup-secondary" data-signup-back hidden>Back</button>
            <button type="button" class="login-button" data-signup-next hidden>Continue</button>
            <button type="submit" class="login-button registration-submit" data-signup-submit>Submit for verification</button>
        </div>
    </form>
</div>
