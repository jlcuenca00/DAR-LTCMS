<div class="registration-field">
    <x-input-label class="form-label" for="name" value="Full legal name" />
    <x-text-input id="name" class="form-input" type="text" name="name" :value="old('name', $initialName ?? '')" required maxlength="255" autocomplete="name" autofocus />
    <p class="registration-help">Use your name as it appears on your official documents.</p>
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>
<div class="registration-field">
    <x-input-label class="form-label" for="contact_number" value="Contact number" />
    <x-text-input id="contact_number" class="form-input" type="tel" name="contact_number" :value="old('contact_number')" required maxlength="30" autocomplete="tel" placeholder="e.g. 0917 123 4567" />
    <x-input-error :messages="$errors->get('contact_number')" class="mt-2" />
</div>
<div class="registration-field">
    <x-input-label class="form-label" for="address" value="Current address" />
    <textarea id="address" name="address" class="form-input w-full" rows="3" required maxlength="500" autocomplete="street-address">{{ old('address') }}</textarea>
    <p class="registration-help">Include your street or sitio, barangay, city or municipality, and province.</p>
    <x-input-error :messages="$errors->get('address')" class="mt-2" />
</div>
<div class="registration-field">
    <x-input-label class="form-label" for="date_of_birth" value="Date of birth (optional)" />
    <x-text-input id="date_of_birth" class="form-input" type="date" name="date_of_birth" :value="old('date_of_birth')" :max="now()->toDateString()" autocomplete="bday" />
    <p class="registration-help">Helps staff distinguish people with similar names.</p>
    <x-input-error :messages="$errors->get('date_of_birth')" class="mt-2" />
</div>
<div class="registration-field">
    <x-input-label class="form-label" for="registered_owner_status" value="Civil / registered owner status (optional)" />
    <select id="registered_owner_status" name="registered_owner_status" class="form-input" data-registration-owner-status>
        <option value="">Not specified</option>
        @foreach(\App\Models\Landowner::registeredOwnerStatusOptions() as $value => $label)
            <option value="{{ $value }}" @selected(old('registered_owner_status') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('registered_owner_status')" class="mt-2" />
</div>
<div class="registration-field" data-registration-spouse>
    <x-input-label class="form-label" for="spouse_name" value="Name of spouse (if married)" />
    <x-text-input id="spouse_name" class="form-input" type="text" name="spouse_name" :value="old('spouse_name')" maxlength="255" />
    <x-input-error :messages="$errors->get('spouse_name')" class="mt-2" />
</div>
<script>
    (() => {
        const status = document.querySelector('[data-registration-owner-status]');
        const group = document.querySelector('[data-registration-spouse]');
        const spouse = group.querySelector('input');
        const update = () => {
            const married = status.value === 'married';
            group.hidden = !married;
            spouse.disabled = !married;
            spouse.required = married;
        };
        status.addEventListener('change', update);
        update();
    })();
</script>
