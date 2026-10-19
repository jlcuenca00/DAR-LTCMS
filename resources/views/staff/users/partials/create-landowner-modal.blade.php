<dialog class="account-create-dialog" data-create-landowner-dialog aria-labelledby="create-landowner-title" aria-describedby="create-landowner-description">
    <form method="POST" action="{{ route('staff.records.landowners.store') }}" data-create-landowner-form>
        @csrf
        <header>
            <h2 id="create-landowner-title">Create Landowner Record</h2>
            <p id="create-landowner-description">Search existing records first to avoid duplicates. The new record is saved immediately; select Save Changes on the account page to link it.</p>
        </header>
        <div class="account-create-body">
            <p class="account-muted">Account name: <strong>{{ $user->name }}</strong>. Confirm the individual name fields below.</p>
            @if(filled($user->registration_details['address'] ?? null))
                <p class="account-muted">Submitted address: {{ $user->registration_details['address'] }}</p>
            @endif
            <div class="user-error" role="alert" data-create-landowner-errors hidden></div>
            <div class="account-create-grid">
                @foreach(['first_name' => 'First name', 'middle_name' => 'Middle name', 'last_name' => 'Last name', 'suffix' => 'Suffix'] as $field => $label)
                    <div class="user-field">
                        <label for="inline-landowner-{{ $field }}" class="user-label">{{ $label }}</label>
                        <input id="inline-landowner-{{ $field }}" name="{{ $field }}" class="user-input" maxlength="{{ $field === 'suffix' ? 50 : 255 }}" @required(in_array($field, ['first_name', 'last_name']))>
                    </div>
                @endforeach
                <div class="user-field">
                    <label for="inline-landowner-status" class="user-label">Registered owner status</label>
                    <select id="inline-landowner-status" name="registered_owner_status" class="user-select">
                        <option value="">Not specified</option>
                        @foreach(\App\Models\Landowner::registeredOwnerStatusOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="user-field" data-inline-spouse hidden>
                    <label for="inline-landowner-spouse" class="user-label">Name of spouse</label>
                    <input id="inline-landowner-spouse" name="spouse_name" class="user-input" maxlength="255" disabled>
                </div>
                <div class="user-field">
                    <label for="inline-landowner-contact" class="user-label">Contact number</label>
                    <input id="inline-landowner-contact" name="contact_number" type="tel" class="user-input" maxlength="100" value="{{ $user->registration_details['contact_number'] ?? '' }}">
                </div>
                <div class="user-field account-create-wide">
                    <label for="inline-landowner-address" class="user-label">Street / sitio / address line</label>
                    <input id="inline-landowner-address" name="address_line" class="user-input" maxlength="255" value="{{ mb_strlen($user->registration_details['address'] ?? '') <= 255 ? ($user->registration_details['address'] ?? '') : '' }}">
                </div>
                @foreach(['municipality' => 'Municipality / city', 'barangay' => 'Barangay', 'province' => 'Province'] as $field => $label)
                    <div class="user-field">
                        <label for="inline-landowner-{{ $field }}" class="user-label">{{ $label }}</label>
                        <input id="inline-landowner-{{ $field }}" name="{{ $field }}" class="user-input" maxlength="255" value="{{ $field === 'province' ? 'Negros Oriental' : '' }}">
                    </div>
                @endforeach
            </div>
        </div>
        <footer>
            <button type="button" class="staff-button staff-button-light" data-create-landowner-close>Cancel</button>
            <button type="submit" class="staff-button staff-button-primary">Create & Select</button>
        </footer>
    </form>
</dialog>
<p class="account-muted" role="status" data-create-landowner-status></p>
