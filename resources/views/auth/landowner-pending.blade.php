<x-public-auth-layout title="Registration Status">
    @php
        $declined = $user?->registration_status === \App\Models\User::REGISTRATION_DECLINED;
    @endphp

    <div class="space-y-5 text-center">
        <div class="mx-auto grid h-14 w-14 place-items-center rounded-full {{ $declined ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">
            <i class="fa-solid {{ $declined ? 'fa-circle-exclamation' : 'fa-hourglass-half' }} text-xl"></i>
        </div>

        <div>
            <h1 class="auth-heading">
                {{ $declined ? 'Registration needs attention' : 'Registration received' }}
            </h1>
            <p class="mt-2 text-sm leading-6 text-gray-600">
                @if ($declined)
                    DAR staff could not approve this registration. Your land records, parcel maps, and clearance outputs remain locked. Please contact the DAR Negros Oriental Provincial Office so staff can check your identity and account details.
                @else
                    Your landowner account is waiting for DAR staff review. You can sign in, but land records, parcel maps, and clearance outputs will stay locked until your identity is verified and your account is linked to the correct landowner record.
                @endif
            </p>
        </div>

        <div class="rounded-lg border px-4 py-3 text-left text-sm leading-6 {{ $declined ? 'border-red-200 bg-red-50 text-red-900' : 'border-amber-200 bg-amber-50 text-amber-900' }}">
            <strong>Next step:</strong>
            {{ $declined
                ? 'contact authorized DAR staff for assistance.'
                : 'wait for DAR staff confirmation, or visit/contact the DAR Negros Oriental Provincial Office if they need to verify your details.' }}
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full rounded-lg bg-green-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-green-800">
                Sign out
            </button>
        </form>
    </div>
</x-public-auth-layout>
