<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Create User Account
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if ($errors->any())
                <div class="bg-red-100 text-red-800 p-3 rounded border border-red-200">
                    <div class="font-semibold mb-2">Please fix the following:</div>
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6 border">
                <h3 class="text-lg font-semibold text-gray-900">
                    New Authorized User
                </h3>

                <p class="text-sm text-gray-600 mt-1">
                    Create a system account for DAR staff, landowner, or geodetic personnel.
                    Landowner accounts must be linked to a landowner record to preserve privacy.
                </p>

                <p class="text-xs text-gray-500 mt-2">
                    This only controls system access. It does not transfer ownership or mutate registry records.
                </p>
            </div>

            <form method="POST"
                  action="{{ route('staff.users.store') }}"
                  class="bg-white shadow-sm sm:rounded-lg p-6 border space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Name
                    </label>

                    <input type="text"
                           name="name"
                           value="{{ old('name') }}"
                           required
                           class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Email
                    </label>

                    <input type="email"
                           name="email"
                           value="{{ old('email') }}"
                           required
                           class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Password
                        </label>

                        <input type="password"
                               name="password"
                               required
                               class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Confirm Password
                        </label>

                        <input type="password"
                               name="password_confirmation"
                               required
                               class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Role
                        </label>

                        <select name="role"
                                required
                                class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">Select role</option>
                            <option value="staff" {{ old('role') === 'staff' ? 'selected' : '' }}>
                                Staff
                            </option>
                            <option value="landowner" {{ old('role') === 'landowner' ? 'selected' : '' }}>
                                Landowner
                            </option>
                            <option value="geodetic" {{ old('role') === 'geodetic' ? 'selected' : '' }}>
                                Geodetic
                            </option>
                        </select>

                        <p class="text-xs text-gray-500 mt-1">
                            Staff can process records. Landowners can only view their own records.
                            Geodetic access remains limited/read-only.
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Account Status
                        </label>

                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox"
                                   name="is_active"
                                   value="1"
                                   {{ old('is_active', '1') ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-gray-900 shadow-sm">
                            <span class="text-sm text-gray-700">Active account</span>
                        </label>

                        <p class="text-xs text-gray-500 mt-1">
                            Inactive users cannot log in or continue using the system.
                        </p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Linked Landowner Record
                    </label>

                    <select name="landowner_id"
                            class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                        <option value="">No linked landowner record</option>

                        @foreach ($landowners as $landowner)
                            <option value="{{ $landowner->id }}"
                                {{ (string) old('landowner_id') === (string) $landowner->id ? 'selected' : '' }}>
                                {{ $landowner->full_name }} — ID {{ $landowner->id }}
                            </option>
                        @endforeach
                    </select>

                    <p class="text-xs text-gray-500 mt-1">
                        Required only when creating a landowner account. The account will only access records linked to this landowner.
                    </p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t">
                    <a href="{{ route('staff.users.index') }}"
                       class="px-4 py-2 border rounded-md text-sm text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>

                    <button type="submit"
                            class="px-4 py-2 bg-gray-900 text-white rounded-md text-sm font-semibold hover:bg-gray-800">
                        Create User
                    </button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>