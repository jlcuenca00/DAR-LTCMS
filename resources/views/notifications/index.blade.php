@php
    $role = auth()->user()?->role;
@endphp

@if ($role === \App\Models\User::ROLE_STAFF)
    <x-staff-shell title="Notifications" active="notifications">
        @include('notifications.partials.list')
    </x-staff-shell>
@elseif ($role === \App\Models\User::ROLE_LANDOWNER)
    <x-landowner-shell title="Notifications" active="notifications">
        @include('notifications.partials.list')
    </x-landowner-shell>
@elseif ($role === \App\Models\User::ROLE_GEODETIC)
    <x-geodetic-shell title="Notifications" active="notifications">
        @include('notifications.partials.list')
    </x-geodetic-shell>
@else
    <x-app-layout>
        <div class="mx-auto max-w-5xl px-4 py-8">
            @include('notifications.partials.list')
        </div>
    </x-app-layout>
@endif
