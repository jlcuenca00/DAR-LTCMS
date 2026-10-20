<div class="registration-field upload-card">
    <x-input-label class="form-label" :for="$kind" :value="$label" />
    <input id="{{ $kind }}" name="{{ $kind }}" type="file" accept="image/jpeg,image/png,image/webp" class="form-input" data-evidence-file="{{ $kind }}" @required($kind !== 'id_back')>
    <p class="registration-help">JPG, PNG, or WebP · Up to 2 MB · At least 160 × 160 pixels</p>
    <img data-evidence-preview="{{ $kind }}" alt="Selected {{ $label }} preview" class="registration-evidence-preview" hidden>
    <p data-evidence-name="{{ $kind }}" class="registration-help" aria-live="polite"></p>
    <x-input-error :messages="$errors->get($kind)" class="mt-2" />
</div>
