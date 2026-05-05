    <x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit User Account
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
                    Edit Authorized User
                </h3>

                <p class="text-sm text-gray-600 mt-1">
                    Update account details, role assignment, account status, and landowner account linkage.
                </p>

                <p class="text-xs text-gray-500 mt-2">
                    This only controls system access. It does not transfer ownership or mutate registry records.
                </p>

                @if (auth()->id() === $user->id)
                    <div class="mt-4 bg-blue-50 border border-blue-200 text-blue-800 text-sm p-3 rounded">
                        You are editing your own account. For safety, you cannot change your own role or deactivate your own account.
                    </div>
                @endif
            </div>

            <form method="POST"
                  action="{{ route('staff.users.update', $user) }}"
                  class="bg-white shadow-sm sm:rounded-lg p-6 border space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Name
                    </label>

                    <input type="text"
                           name="name"
                           value="{{ old('name', $user->name) }}"
                           required
                           class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Email
                    </label>

                    <input type="email"
                           name="email"
                           value="{{ old('email', $user->email) }}"
                           required
                           class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            New Password
                        </label>

                        <input type="password"
                               name="password"
                               class="w-full border-gray-300 rounded-md shadow-sm text-sm">

                        <p class="text-xs text-gray-500 mt-1">
                            Leave blank to keep current password.
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Confirm New Password
                        </label>

                        <input type="password"
                               name="password_confirmation"
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
                            <option value="staff" {{ old('role', $user->role) === 'staff' ? 'selected' : '' }}>
                                Staff
                            </option>
                            <option value="landowner" {{ old('role', $user->role) === 'landowner' ? 'selected' : '' }}>
                                Landowner
                            </option>
                            <option value="geodetic" {{ old('role', $user->role) === 'geodetic' ? 'selected' : '' }}>
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
                                   {{ old('is_active', $user->is_active) ? 'checked' : '' }}
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
                                {{ (string) old('landowner_id', $linkedLandownerId) === (string) $landowner->id ? 'selected' : '' }}>
                                {{ $landowner->full_name }} — ID {{ $landowner->id }}
                            </option>
                        @endforeach
                    </select>

                    <p class="text-xs text-gray-500 mt-1">
                        Required when the role is Landowner. This controls which landowner records the user can view.
                    </p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t">
                    <a href="{{ route('staff.users.index') }}"
                       class="px-4 py-2 border rounded-md text-sm text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>

                    <button type="submit"
                            class="px-4 py-2 bg-gray-900 text-white rounded-md text-sm font-semibold hover:bg-gray-800">
                        Save Changes
                    </button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>