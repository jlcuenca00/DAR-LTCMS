<x-staff-shell
    title="Create Landowner Record"
    active="landowner-records"
>
    <x-slot name="actions">
        <a href="{{ route('staff.records.landowners.index') }}" class="staff-button staff-button-light">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Landowner Records
        </a>
    </x-slot>

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

    <form method="POST" action="{{ route('staff.records.landowners.store') }}" class="staff-panel overflow-hidden" data-autosave-key="landowner-record-create" data-autosave-label="landowner record draft">
        @csrf

        <div class="staff-panel-pad border-b border-gray-200">
            <h2 class="staff-panel-title">Landowner / Person Information</h2>
            <p class="staff-panel-subtitle">Create the registered-owner reference record first, including spouse name only when the owner status is Married.</p>
        </div>

        <div class="staff-panel-pad grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-xs font-black uppercase tracking-wider text-gray-600">First name</label>
                <input type="text" name="first_name" value="{{ old('first_name') }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-green-600 focus:ring-green-600" required>
            </div>
            <div>
                <label class="mb-1 block text-xs font-black uppercase tracking-wider text-gray-600">Middle name</label>
                <input type="text" name="middle_name" value="{{ old('middle_name') }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-green-600 focus:ring-green-600">
            </div>
            <div>
                <label class="mb-1 block text-xs font-black uppercase tracking-wider text-gray-600">Last name</label>
                <input type="text" name="last_name" value="{{ old('last_name') }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-green-600 focus:ring-green-600" required>
            </div>
            <div>
                <label class="mb-1 block text-xs font-black uppercase tracking-wider text-gray-600">Suffix</label>
                <input type="text" name="suffix" value="{{ old('suffix') }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-green-600 focus:ring-green-600">
            </div>
            <div>
                <label class="mb-1 block text-xs font-black uppercase tracking-wider text-gray-600">Registered owner status</label>
                <select name="registered_owner_status" class="w-full rounded-lg border-gray-300 text-sm focus:border-green-600 focus:ring-green-600">
                    <option value="">Not specified</option>
                    @foreach ($registeredOwnerStatusOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old('registered_owner_status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500">Used to match the registered owner as shown on the title.</p>
            </div>
            <div>
                <label class="mb-1 block text-xs font-black uppercase tracking-wider text-gray-600">Name of spouse <span class="normal-case text-gray-400">if married</span></label>
                <input type="text" name="spouse_name" value="{{ old('spouse_name') }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-green-600 focus:ring-green-600" placeholder="Required only when status is Married">
                <p class="mt-1 text-xs text-gray-500">Leave blank when the registered owner is not married or when not applicable.</p>
            </div>
            <div>
                <label class="mb-1 block text-xs font-black uppercase tracking-wider text-gray-600">Contact number</label>
                <input type="text" name="contact_number" value="{{ old('contact_number') }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-green-600 focus:ring-green-600">
            </div>
            <div>
                <label class="mb-1 block text-xs font-black uppercase tracking-wider text-gray-600">Linked landowner user account</label>
                <select name="user_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-green-600 focus:ring-green-600">
                    <option value="">No linked account</option>
                    @foreach ($landownerUsers as $user)
                        <option value="{{ $user->id }}" @selected((int) old('user_id') === (int) $user->id)>{{ $user->name }} — {{ $user->email }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block text-xs font-black uppercase tracking-wider text-gray-600">Address</label>
                <input type="text" name="address_line" value="{{ old('address_line') }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-green-600 focus:ring-green-600">
            </div>
            <div>
                <label class="mb-1 block text-xs font-black uppercase tracking-wider text-gray-600">Municipality</label>
                <input type="text" name="municipality" value="{{ old('municipality') }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-green-600 focus:ring-green-600">
            </div>
            <div>
                <label class="mb-1 block text-xs font-black uppercase tracking-wider text-gray-600">Barangay</label>
                <input type="text" name="barangay" value="{{ old('barangay') }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-green-600 focus:ring-green-600">
            </div>
            <div>
                <label class="mb-1 block text-xs font-black uppercase tracking-wider text-gray-600">Province</label>
                <input type="text" name="province" value="{{ old('province', 'Negros Oriental') }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-green-600 focus:ring-green-600">
            </div>
        </div>

        <div class="flex flex-col gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4 md:flex-row md:items-center md:justify-between">
            <p class="text-xs leading-relaxed text-gray-500">This creates the landowner record only. Parcel and landholding records are added separately through the system workflow.</p>
            <button type="submit" class="staff-button staff-button-primary">
                <i class="fa-solid fa-user-plus"></i>
                Create Landowner Record
            </button>
        </div>
    </form>
    @include('staff.partials.form-autosave')

</x-staff-shell>