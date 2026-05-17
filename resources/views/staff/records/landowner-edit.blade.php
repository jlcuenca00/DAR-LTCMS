<x-staff-shell
    title="Edit Landowner Record"
    active="landowner-records"
>
    <x-slot name="actions">
        <a href="{{ route('staff.records.landowners.show', $landowner) }}" class="staff-button staff-button-light">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Details
        </a>
    </x-slot>

    <section class="staff-scope-banner">
        <div>
            <h3>Administrative Details Only</h3>
            <p>
                Edit identity, contact, address, and optional portal account linkage only. Current hectares are not directly editable here because they are computed from active landholding records.
            </p>
        </div>
        <span class="staff-scope-pill">No Ownership Mutation</span>
    </section>

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
        @method('PATCH')

        <div class="staff-panel-pad border-b border-gray-200">
            <h2 class="staff-panel-title">Landowner / Person Information</h2>
            <p class="staff-panel-subtitle">These fields support records management, account linking, and privacy-controlled access.</p>
        </div>

        <div class="staff-panel-pad grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-xs font-black uppercase tracking-wider text-gray-600">First name</label>
                <input type="text" name="first_name" value="{{ old('first_name', $landowner->first_name) }}" class="w-full rounded-lg border-gray-300 text-sm" required>
            </div>
            <div>
                <label class="mb-1 block text-xs font-black uppercase tracking-wider text-gray-600">Middle name</label>
                <input type="text" name="middle_name" value="{{ old('middle_name', $landowner->middle_name) }}" class="w-full rounded-lg border-gray-300 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-black uppercase tracking-wider text-gray-600">Last name</label>
                <input type="text" name="last_name" value="{{ old('last_name', $landowner->last_name) }}" class="w-full rounded-lg border-gray-300 text-sm" required>
            </div>
            <div>
                <label class="mb-1 block text-xs font-black uppercase tracking-wider text-gray-600">Suffix</label>
                <input type="text" name="suffix" value="{{ old('suffix', $landowner->suffix) }}" class="w-full rounded-lg border-gray-300 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-black uppercase tracking-wider text-gray-600">Contact number</label>
                <input type="text" name="contact_number" value="{{ old('contact_number', $landowner->contact_number) }}" class="w-full rounded-lg border-gray-300 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-black uppercase tracking-wider text-gray-600">Linked landowner user account</label>
                <select name="user_id" class="w-full rounded-lg border-gray-300 text-sm">
                    <option value="">No linked account</option>
                    @foreach ($landownerUsers as $user)
                        <option value="{{ $user->id }}" @selected((int) old('user_id', $landowner->user_id) === (int) $user->id)>{{ $user->name }} — {{ $user->email }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block text-xs font-black uppercase tracking-wider text-gray-600">Address</label>
                <input type="text" name="address_line" value="{{ old('address_line', $landowner->address_line) }}" class="w-full rounded-lg border-gray-300 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-black uppercase tracking-wider text-gray-600">Municipality</label>
                <input type="text" name="municipality" value="{{ old('municipality', $landowner->municipality) }}" class="w-full rounded-lg border-gray-300 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-black uppercase tracking-wider text-gray-600">Barangay</label>
                <input type="text" name="barangay" value="{{ old('barangay', $landowner->barangay) }}" class="w-full rounded-lg border-gray-300 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-black uppercase tracking-wider text-gray-600">Province</label>
                <input type="text" name="province" value="{{ old('province', $landowner->province ?? 'Negros Oriental') }}" class="w-full rounded-lg border-gray-300 text-sm">
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
