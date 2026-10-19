<x-public-auth-layout title="Registration Status">
    @php
        $declined = $user?->registration_status === \App\Models\User::REGISTRATION_DECLINED;
    @endphp

    <div class="space-y-5 text-center">
        <div class="registration-status-icon {{ $declined ? 'declined' : '' }}" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                @if ($declined)
                    <path d="M12 5v9m0 4h.01" />
                @else
                    <path d="m5 12 4 4L19 6" />
                @endif
            </svg>
        </div>

        <div>
            <h1 class="auth-heading">
                {{ $declined ? 'Registration needs attention' : 'Registration received' }}
            </h1>
            <p class="mt-2 text-sm leading-6 text-gray-600">
                @if ($declined)
                    Your registration needs further review. Your land records remain unavailable. Please check your email for a review update.
                @else
                    Your registration is pending verification. Please allow a few business days for DAR staff to review your details.
                @endif
            </p>
        </div>

        <div class="rounded-lg border px-4 py-3 text-left text-sm leading-6 {{ $declined ? 'border-red-200 bg-red-50 text-red-900' : 'border-amber-200 bg-amber-50 text-amber-900' }}">
            <strong>Next step:</strong>
            {{ $declined
                ? 'check your email for your registration review update.'
                : 'wait for verification. You will receive an email once your registration has been reviewed.' }}
        </div>

        @if (session('registration_email_status'))
            <p role="status" class="text-sm text-green-700">{{ session('registration_email_status') }}</p>
        @endif

        @if (session('registration_email_warning'))
            <p role="status" class="text-sm text-gray-600">{{ session('registration_email_warning') }}</p>
        @endif

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full rounded-lg bg-green-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-green-800">
                Sign out
            </button>
        </form>
    </div>
</x-public-auth-layout>
