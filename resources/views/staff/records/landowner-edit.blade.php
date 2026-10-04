<x-staff-shell
    title="Edit Landowner Record"
    active="landowner-records"
>
    

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-black">Please correct the following:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('staff.records.landowners.update', $landowner) }}" class="staff-panel overflow-hidden">
        @csrf
        <input type="hidden" name="expected_record_revision" value="{{ old('expected_record_revision', $landowner->record_revision) }}">
        @method('PATCH')

        <div class="staff-panel-pad border-b border-gray-200">
            <h2 class="staff-panel-title">Landowner / Person Information</h2>
            <p class="staff-panel-subtitle">These fields support registered-owner matching for clearance review. Spouse name is required only when the status is Married.</p>
        </div>

        <div class="staff-panel-pad grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label for="landowner-first_name" class="mb-1 block text-xs font-black uppercase tracking-wider text-gray-600">First name</label>
                <input id="landowner-first_name" type="text" name="first_name" value="{{ old('first_name', $landowner->first_name) }}" class="w-full rounded-lg border-gray-300 text-sm" required @error('first_name', 'default') aria-invalid="true" aria-describedby="landowner-first_name-server-error" data-ui-server-invalid @enderror>
                @error('first_name')<p id="landowner-first_name-server-error" class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="landowner-middle_name" class="mb-1 block text-xs font-black uppercase tracking-wider text-gray-600">Middle name</label>
                <input id="landowner-middle_name" type="text" name="middle_name" value="{{ old('middle_name', $landowner->middle_name) }}" class="w-full rounded-lg border-gray-300 text-sm" @error('middle_name', 'default') aria-invalid="true" aria-describedby="landowner-middle_name-server-error" data-ui-server-invalid @enderror>
                @error('middle_name')<p id="landowner-middle_name-server-error" class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="landowner-last_name" class="mb-1 block text-xs font-black uppercase tracking-wider text-gray-600">Last name</label>
                <input id="landowner-last_name" type="text" name="last_name" value="{{ old('last_name', $landowner->last_name) }}" class="w-full rounded-lg border-gray-300 text-sm" required @error('last_name', 'default') aria-invalid="true" aria-describedby="landowner-last_name-server-error" data-ui-server-invalid @enderror>
                @error('last_name')<p id="landowner-last_name-server-error" class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="landowner-suffix" class="mb-1 block text-xs font-black uppercase tracking-wider text-gray-600">Suffix</label>
                <input id="landowner-suffix" type="text" name="suffix" value="{{ old('suffix', $landowner->suffix) }}" class="w-full rounded-lg border-gray-300 text-sm" @error('suffix', 'default') aria-invalid="true" aria-describedby="landowner-suffix-server-error" data-ui-server-invalid @enderror>
                @error('suffix')<p id="landowner-suffix-server-error" class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="landowner-registered_owner_status" class="mb-1 block text-xs font-black uppercase tracking-wider text-gray-600">Registered owner status</label>
                <select id="landowner-registered_owner_status" name="registered_owner_status" class="w-full rounded-lg border-gray-300 text-sm" @error('registered_owner_status', 'default') aria-invalid="true" aria-describedby="landowner-registered_owner_status-server-error" data-ui-server-invalid @enderror>
                    <option value="">Not specified</option>
                    @foreach ($registeredOwnerStatusOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old('registered_owner_status', $landowner->registered_owner_status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('registered_owner_status')<p id="landowner-registered_owner_status-server-error" class="text-sm text-red-600">{{ $message }}</p>@enderror
                <p class="mt-1 text-xs text-gray-500">Used to match the registered owner as shown on the title.</p>
            </div>
            <div>
                <label for="landowner-spouse_name" class="mb-1 block text-xs font-black uppercase tracking-wider text-gray-600">Name of spouse <span class="normal-case text-gray-400">if married</span></label>
                <input id="landowner-spouse_name" type="text" name="spouse_name" value="{{ old('spouse_name', $landowner->spouse_name) }}" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Required only when status is Married" @error('spouse_name', 'default') aria-invalid="true" aria-describedby="landowner-spouse_name-server-error" data-ui-server-invalid @enderror>
                @error('spouse_name')<p id="landowner-spouse_name-server-error" class="text-sm text-red-600">{{ $message }}</p>@enderror
                <p class="mt-1 text-xs text-gray-500">This field is cleared when the registered owner status is not Married.</p>
            </div>
            <div>
                <label for="landowner-contact_number" class="mb-1 block text-xs font-black uppercase tracking-wider text-gray-600">Contact number</label>
                <input id="landowner-contact_number" type="text" name="contact_number" value="{{ old('contact_number', $landowner->contact_number) }}" class="w-full rounded-lg border-gray-300 text-sm" @error('contact_number', 'default') aria-invalid="true" aria-describedby="landowner-contact_number-server-error" data-ui-server-invalid @enderror>
                @error('contact_number')<p id="landowner-contact_number-server-error" class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="landowner-user_id" class="mb-1 block text-xs font-black uppercase tracking-wider text-gray-600">Linked landowner user account</label>
                <div data-remote-record-select data-lookup-url="{{ route('staff.lookups.landowner-users', ['landowner_id' => $landowner->id]) }}" class="space-y-2">
                    <input type="search" aria-label="Search Landowner accounts" placeholder="Search name, email, username, or account ID" autocomplete="off" class="w-full rounded-lg border-gray-300 text-sm" data-remote-record-search>
                    <select id="landowner-user_id" name="user_id" class="w-full rounded-lg border-gray-300 text-sm" data-remote-record-control data-placeholder="No linked account" @error('user_id', 'default') aria-invalid="true" aria-describedby="landowner-user_id-server-error" data-ui-server-invalid @enderror>
                        <option value="">No linked account</option>
                        @if ($selectedUser)
                            <option value="{{ $selectedUser->id }}" selected>{{ $selectedUser->name }} — {{ $selectedUser->email }}</option>
                        @endif
                    </select>
                @error('user_id')<p id="landowner-user_id-server-error" class="text-sm text-red-600">{{ $message }}</p>@enderror
                    <p class="text-xs text-gray-500" data-remote-record-status>Search to find an eligible account.</p>
                </div>
            </div>
            <div class="md:col-span-2">
                <label for="landowner-address_line" class="mb-1 block text-xs font-black uppercase tracking-wider text-gray-600">Address</label>
                <input id="landowner-address_line" type="text" name="address_line" value="{{ old('address_line', $landowner->address_line) }}" class="w-full rounded-lg border-gray-300 text-sm" @error('address_line', 'default') aria-invalid="true" aria-describedby="landowner-address_line-server-error" data-ui-server-invalid @enderror>
                @error('address_line')<p id="landowner-address_line-server-error" class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="landowner-municipality" class="mb-1 block text-xs font-black uppercase tracking-wider text-gray-600">Municipality</label>
                <input id="landowner-municipality" type="text" name="municipality" value="{{ old('municipality', $landowner->municipality) }}" class="w-full rounded-lg border-gray-300 text-sm" @error('municipality', 'default') aria-invalid="true" aria-describedby="landowner-municipality-server-error" data-ui-server-invalid @enderror>
                @error('municipality')<p id="landowner-municipality-server-error" class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="landowner-barangay" class="mb-1 block text-xs font-black uppercase tracking-wider text-gray-600">Barangay</label>
                <input id="landowner-barangay" type="text" name="barangay" value="{{ old('barangay', $landowner->barangay) }}" class="w-full rounded-lg border-gray-300 text-sm" @error('barangay', 'default') aria-invalid="true" aria-describedby="landowner-barangay-server-error" data-ui-server-invalid @enderror>
                @error('barangay')<p id="landowner-barangay-server-error" class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="landowner-province" class="mb-1 block text-xs font-black uppercase tracking-wider text-gray-600">Province</label>
                <input id="landowner-province" type="text" name="province" value="{{ old('province', $landowner->province ?? 'Negros Oriental') }}" class="w-full rounded-lg border-gray-300 text-sm" @error('province', 'default') aria-invalid="true" aria-describedby="landowner-province-server-error" data-ui-server-invalid @enderror>
                @error('province')<p id="landowner-province-server-error" class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex flex-col gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4 md:flex-row md:items-center md:justify-between">
            <p class="text-xs leading-relaxed text-gray-500">Saving this form does not edit computed hectares or transfer ownership.</p>
            <button type="submit" class="staff-button staff-button-primary">
                <i class="fa-solid fa-floppy-disk"></i>
                Save Landowner Record
            </button>
        </div>
    </form>
</x-staff-shell>

