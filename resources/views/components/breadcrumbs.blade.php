@props(['title' => null])

@php
    $routeName = request()->route()?->getName();
    $items = [];

    $add = function (string $label, ?string $route = null) use (&$items) {
        $items[] = [
            'label' => $label,
            'url' => $route && \Illuminate\Support\Facades\Route::has($route) ? route($route) : null,
        ];
    };

    if (str_starts_with((string) $routeName, 'staff.')) {
        $add('Dashboard', 'staff.dashboard');

        $groups = [
            'staff.applications.' => ['Applications', 'staff.applications.index'],
            'staff.records.landowners.' => ['Landowners', 'staff.records.landowners.index'],
            'staff.records.parcels.' => ['Parcels', 'staff.records.parcels.index'],
            'staff.users.' => ['Users', 'staff.users.index'],
            'staff.legacy-records.' => ['Source Records', 'staff.legacy-records.index'],
            'staff.source-record-packages.' => ['Source Records', 'staff.legacy-records.index'],
            'staff.source-record-package-imports.' => ['Source Records', 'staff.legacy-records.index'],
            'staff.reports.monitoring.' => ['Monitoring Reports', 'staff.reports.monitoring.index'],
            'staff.audit-logs.' => ['Audit Logs', 'staff.audit-logs.index'],
            'staff.parcel-map.' => ['Parcel Map', 'staff.parcel-map.index'],
        ];
    } elseif (str_starts_with((string) $routeName, 'landowner.')) {
        $add('Dashboard', 'landowner.dashboard');

        $groups = [
            'landowner.applications.' => ['My Applications', 'landowner.applications.index'],
            'landowner.parcels.' => ['My Parcels', 'landowner.parcels.index'],
            'landowner.parcel-map.' => ['Parcel Map', 'landowner.parcel-map.index'],
        ];
    } elseif (str_starts_with((string) $routeName, 'geodetic.')) {
        $add('Dashboard', 'geodetic.dashboard');

        $groups = [
            'geodetic.parcels.' => ['Parcel Records', 'geodetic.parcels.index'],
            'geodetic.parcel-map.' => ['Parcel Map', 'geodetic.parcel-map.index'],
        ];
    } else {
        $groups = [];
    }

    foreach ($groups as $prefix => [$label, $route]) {
        if (str_starts_with((string) $routeName, $prefix)) {
            $add($label, $route);
            break;
        }
    }

    $normalizedTitle = trim((string) $title);
    $lastLabel = $items[count($items) - 1]['label'] ?? null;

    if ($normalizedTitle !== '' && $normalizedTitle !== $lastLabel && ! str_ends_with((string) $routeName, '.index') && ! str_ends_with((string) $routeName, '.dashboard')) {
        $add($normalizedTitle);
    } elseif (! empty($items)) {
        $items[count($items) - 1]['url'] = null;
    }
@endphp

@if (count($items) > 1 || (count($items) === 1 && ! str_ends_with((string) $routeName, '.dashboard')))
    <nav class="app-breadcrumbs" aria-label="Breadcrumb">
        @foreach ($items as $index => $item)
            @if ($index > 0)
                <i class="fa-solid fa-chevron-right app-breadcrumb-separator" aria-hidden="true"></i>
            @endif

            @if ($item['url'])
                <a href="{{ $item['url'] }}">
                    @if ($index === 0)
                        <i class="fa-solid fa-house app-breadcrumb-home" aria-hidden="true"></i>
                    @endif
                    {{ $item['label'] }}
                </a>
            @else
                <span aria-current="page">{{ $item['label'] }}</span>
            @endif
        @endforeach
    </nav>
@endif

@once
    <style>
        .app-breadcrumbs {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin: 0 0 22px;
            padding: 2px 0 12px;
            border-bottom: 2px solid #d8e5db;
            color: #475569;
            font-size: 15px;
            font-weight: 750;
            line-height: 1.35;
        }

        .app-breadcrumbs a {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: #166534;
            text-decoration: none;
        }

        .app-breadcrumbs a:hover {
            color: #14532d;
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .app-breadcrumb-separator {
            font-size: 10px;
            color: #94a3b8;
        }

        .app-breadcrumb-home {
            font-size: 13px;
        }

        .app-breadcrumbs span[aria-current="page"] {
            color: #111827;
            font-weight: 900;
        }

        @media (max-width: 640px) {
            .app-breadcrumbs {
                gap: 8px;
                margin-bottom: 18px;
                font-size: 14px;
            }
        }
    </style>
@endonce
