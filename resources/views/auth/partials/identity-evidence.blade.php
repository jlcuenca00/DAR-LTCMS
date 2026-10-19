<section class="registration-evidence" data-registration-evidence>
    <h2>Identity documents</h2>
    <p class="registration-help">Submit a clear ID image and selfie for DAR staff review. All details must be readable. These images are reviewed manually.</p>
    @if($errors->any())<p class="registration-help">Please select your images again after correcting any errors below.</p>@endif
    <div class="registration-field">
        <x-input-label class="form-label" for="id_type" value="ID type" />
        <select id="id_type" name="id_type" class="form-input" required>
            <option value="">Select an ID</option>
            @foreach(\App\Services\RegistrationEvidence::idTypes() as $value => $label)
                <option value="{{ $value }}" @selected(old('id_type') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('id_type')" class="mt-2" />
    </div>
    @foreach(['id_front' => 'ID front / passport information page', 'id_back' => 'ID back (optional)', 'selfie' => 'Selfie'] as $kind => $label)
        <div class="registration-field">
            <x-input-label class="form-label" :for="$kind" :value="$label" />
            <input id="{{ $kind }}" name="{{ $kind }}" type="file" accept="image/jpeg,image/png,image/webp" class="form-input" data-evidence-file="{{ $kind }}" @required($kind !== 'id_back')>
            <p class="registration-help">JPG, PNG, or WebP. Maximum 2 MB; minimum 160 × 160 pixels.</p>
            <img data-evidence-preview="{{ $kind }}" alt="Selected {{ $label }} preview" class="registration-evidence-preview" hidden>
            <x-input-error :messages="$errors->get($kind)" class="mt-2" />
        </div>
    @endforeach
    <div class="registration-camera">
        <p class="registration-help">For a camera selfie, face the camera in good lighting with your face fully visible. You can upload a clear selfie above if your camera is unavailable.</p>
        <button type="button" class="registration-camera-button" data-selfie-start>Open selfie camera</button>
        <video data-selfie-video autoplay playsinline muted hidden></video>
        <div data-selfie-controls hidden>
            <button type="button" class="registration-camera-button" data-selfie-capture>Capture selfie</button>
            <button type="button" class="registration-camera-button" data-selfie-stop>Close camera</button>
        </div>
        <p data-selfie-status role="status" class="registration-help"></p>
    </div>
    <label class="consent">
        <input type="checkbox" name="identity_consent" value="1" required @checked(old('identity_consent'))>
        <span>I consent to DAR staff reviewing my ID and selfie for account identity verification under the <a href="{{ route('privacy') }}" target="_blank" rel="noopener">Privacy Policy</a>.</span>
    </label>
    <x-input-error :messages="$errors->get('identity_consent')" class="mt-2" />
</section>
