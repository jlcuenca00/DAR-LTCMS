<nav x-data="{ open: false }" class="min-w-0 border-b border-gray-100 bg-white">
    @php
        $role = Auth::user()?->role;
    @endphp

    <div class="mx-auto w-full max-w-7xl min-w-0 px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 min-w-0 items-center justify-between gap-3">
            <div class="flex min-w-0 items-center">
                <div class="flex shrink-0 items-center">
                    <a href="{{ route('dashboard') }}" class="inline-flex min-h-11 min-w-11 items-center justify-center" aria-label="DAR-LTCMS dashboard">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <div class="hidden min-w-0 items-stretch gap-5 lg:ms-8 lg:flex xl:gap-7">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    @if($role === 'staff')
                        <x-nav-link :href="route('staff.dashboard')" :active="request()->routeIs('staff.dashboard')">
                            Staff Dashboard
                        </x-nav-link>
                        <x-nav-link :href="route('staff.parcel-map.index')" :active="request()->routeIs('staff.parcel-map.*')">
                            Parcel Map
                        </x-nav-link>
                        <x-nav-link :href="route('staff.legacy-records.index')" :active="request()->routeIs('staff.legacy-records.*')">
                            Legacy Records
                        </x-nav-link>
                    @endif

                    @if($role === 'landowner')
                        <x-nav-link :href="route('landowner.dashboard')" :active="request()->routeIs('landowner.dashboard')">
                            Landowner Dashboard
                        </x-nav-link>
                        <x-nav-link :href="route('landowner.parcel-map.index')" :active="request()->routeIs('landowner.parcel-map.*')">
                            My Parcel Map
                        </x-nav-link>
                        <x-nav-link :href="route('landowner.parcels.index')" :active="request()->routeIs('landowner.parcels.*')">
                            My Parcels
                        </x-nav-link>
                        <x-nav-link :href="route('landowner.applications.index')" :active="request()->routeIs('landowner.applications.*')">
                            My Applications
                        </x-nav-link>
                    @endif

                    @if($role === 'geodetic')
                        <x-nav-link :href="route('geodetic.dashboard')" :active="request()->routeIs('geodetic.dashboard')">
                            Geodetic Dashboard
                        </x-nav-link>
                        <x-nav-link :href="route('geodetic.parcel-map.index')" :active="request()->routeIs('geodetic.parcel-map.*')">
                            Parcel Map
                        </x-nav-link>
                        <x-nav-link :href="route('geodetic.parcels.index')" :active="request()->routeIs('geodetic.parcels.*')">
                            Parcel Reference
                        </x-nav-link>
                        <x-nav-link :href="route('geodetic.applications.index')" :active="request()->routeIs('geodetic.applications.*')">
                            Application Reference
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <div class="hidden min-w-0 shrink-0 items-center lg:flex lg:ms-4">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex min-h-11 max-w-[15rem] items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none">
                            <div class="truncate">{{ Auth::user()->name }}</div>
                            <div class="ms-1 shrink-0">
                                <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="flex shrink-0 items-center lg:hidden">
                <button
                    type="button"
                    @click="open = ! open"
                    :aria-expanded="open.toString()"
                    aria-controls="responsive-navigation"
                    aria-label="Toggle navigation menu"
                    class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-md p-2 text-gray-500 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-700 focus:bg-gray-100 focus:text-gray-700 focus:outline-none"
                >
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div id="responsive-navigation" x-cloak x-show="open" class="border-t border-gray-100 bg-white lg:hidden">
        <div class="max-h-[calc(100vh-4rem)] overflow-y-auto overscroll-contain">
            <div class="space-y-1 py-2">
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    {{ __('Dashboard') }}
                </x-responsive-nav-link>

                @if($role === 'staff')
                    <x-responsive-nav-link :href="route('staff.dashboard')" :active="request()->routeIs('staff.dashboard')">Staff Dashboard</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('staff.parcel-map.index')" :active="request()->routeIs('staff.parcel-map.*')">Parcel Map</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('staff.legacy-records.index')" :active="request()->routeIs('staff.legacy-records.*')">Legacy Records</x-responsive-nav-link>
                @endif

                @if($role === 'landowner')
                    <x-responsive-nav-link :href="route('landowner.dashboard')" :active="request()->routeIs('landowner.dashboard')">Landowner Dashboard</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('landowner.parcel-map.index')" :active="request()->routeIs('landowner.parcel-map.*')">My Parcel Map</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('landowner.parcels.index')" :active="request()->routeIs('landowner.parcels.*')">My Parcels</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('landowner.applications.index')" :active="request()->routeIs('landowner.applications.*')">My Applications</x-responsive-nav-link>
                @endif

                @if($role === 'geodetic')
                    <x-responsive-nav-link :href="route('geodetic.dashboard')" :active="request()->routeIs('geodetic.dashboard')">Geodetic Dashboard</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('geodetic.parcel-map.index')" :active="request()->routeIs('geodetic.parcel-map.*')">Parcel Map</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('geodetic.parcels.index')" :active="request()->routeIs('geodetic.parcels.*')">Parcel Reference</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('geodetic.applications.index')" :active="request()->routeIs('geodetic.applications.*')">Application Reference</x-responsive-nav-link>
                @endif
            </div>

            <div class="border-t border-gray-200 pb-2 pt-4">
                <div class="min-w-0 px-4">
                    <div class="truncate text-base font-medium text-gray-800">{{ Auth::user()->name }}</div>
                    <div class="break-all text-sm font-medium text-gray-500">{{ Auth::user()->email }}</div>
                    <div class="mt-1 text-xs font-medium uppercase text-gray-400">{{ Auth::user()->role }}</div>
                </div>

                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('profile.edit')">{{ __('Profile') }}</x-responsive-nav-link>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                            {{ __('Log Out') }}
                        </x-responsive-nav-link>
                    </form>
                </div>
            </div>
        </div>
    </div>
</nav>