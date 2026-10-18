@php
    $profileUser = $user ?? auth()->user();
    $isLandownerProfile = $profileUser?->isLandowner() ?? false;
    $isGeodeticProfile = $profileUser?->isGeodetic() ?? false;

    $profileContext = match (true) {
        $isLandownerProfile => [
            'portal' => 'Landowner Portal',
            'badge' => 'Own Records Account',
            'note' => 'Manage your login profile and password.',
        ],
        $isGeodeticProfile => [
            'portal' => 'Geodetic Portal',
            'badge' => 'Limited Access',
            'note' => 'Manage your login profile and password.',
        ],
        default => [
            'portal' => 'Legal Clearance Staff Workspace',
            'badge' => 'Legal Clearance Staff',
            'note' => 'Manage your login profile and password. Role assignment and account status remain controlled through User Management.',
        ],
    };
@endphp



@if ($isGeodeticProfile)
    <x-geodetic-shell title="Profile Settings" active="profile">
        @include('profile.partials.account-settings-content', ['user' => $profileUser, 'context' => $profileContext])
    </x-geodetic-shell>
@elseif ($isLandownerProfile)
    <x-landowner-shell title="Profile Settings" active="profile">
        @include('profile.partials.account-settings-content', ['user' => $profileUser, 'context' => $profileContext])
    </x-landowner-shell>
@else
    <x-staff-shell title="Profile Settings" active="profile" maxWidth="max-w-5xl">
        @include('profile.partials.account-settings-content', ['user' => $profileUser, 'context' => $profileContext])
    </x-staff-shell>
@endif
