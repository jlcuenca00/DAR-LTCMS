<x-guest-layout>
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-black text-gray-900">Landowner Registration</h1>
        <p class="mt-2 text-sm leading-6 text-gray-600">
            Create an account for DAR review. Your records stay locked until staff verifies your identity and links the correct landowner record.
        </p>
    </div>

    @if ($errors->has('google'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first('google') }}
        </div>
    @endif

    @if ($googleClientId)
        <div class="space-y-3">
            <label class="flex items-start gap-2 text-sm text-gray-600">
                <input id="google-privacy-consent" type="checkbox" class="mt-1 rounded border-gray-300 text-green-700 focus:ring-green-600">
                <span>I agree that DAR-LTCMS may use my account details to process this registration and verify my identity.</span>
            </label>
            <p id="google-consent-error" class="hidden text-sm font-semibold text-red-600">
                Please accept the privacy notice before continuing with Google.
            </p>

            <script src="https://accounts.google.com/gsi/client" async defer></script>
            <div class="flex justify-center">
                <div id="g_id_onload"
                    data-client_id="{{ $googleClientId }}"
                    data-callback="handleGoogleRegistration"
                    data-ux_mode="popup"
                    data-auto_prompt="false">
                </div>
                <div class="g_id_signin"
                    data-type="standard"
                    data-size="large"
                    data-theme="outline"
                    data-text="continue_with"
                    data-shape="rectangular"
                    data-logo_alignment="left">
                </div>
            </div>

            <form id="google-registration-form" method="POST" action="{{ route('register.google') }}" class="hidden">
                @csrf
                <input id="google-registration-credential" type="hidden" name="credential">
                <input type="hidden" name="intent" value="register">
                <input id="google-registration-consent" type="hidden" name="privacy_consent" value="0">
            </form>
        </div>

        <div class="my-6 flex items-center gap-3 text-xs font-bold uppercase text-gray-400">
            <span class="h-px flex-1 bg-gray-200"></span>
            Or register manually
            <span class="h-px flex-1 bg-gray-200"></span>
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="name" :value="__('Full name')" />
            <x-text-input id="name" class="mt-1 block w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="username" :value="__('Username')" />
            <x-text-input id="username" class="mt-1 block w-full" type="text" name="username" :value="old('username')" required autocomplete="username" />
            <p class="mt-1 text-xs text-gray-500">Use letters, numbers, dashes, or underscores. You will use this to sign in.</p>
            <x-input-error :messages="$errors->get('username')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email (optional)')" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" autocomplete="email" />
            <p class="mt-1 text-xs leading-5 text-gray-500">Leave this blank if you do not use email. Password recovery will require help from DAR staff.</p>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm password')" />
            <x-text-input id="password_confirmation" class="mt-1 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <label class="flex items-start gap-2 text-sm text-gray-600">
            <input type="checkbox" name="privacy_consent" value="1" class="mt-1 rounded border-gray-300 text-green-700 focus:ring-green-600" @checked(old('privacy_consent')) required>
            <span>I agree that DAR-LTCMS may use my account details to process this registration and verify my identity.</span>
        </label>
        <x-input-error :messages="$errors->get('privacy_consent')" class="mt-2" />

        <x-primary-button class="w-full justify-center">
            {{ __('Create Landowner Account') }}
        </x-primary-button>

        <div class="text-center">
            <a class="text-sm font-semibold text-green-700 underline hover:text-green-900" href="{{ route('login') }}">
                {{ __('Already registered? Sign in') }}
            </a>
        </div>
    </form>

    @if ($googleClientId)
        <script>
            window.handleGoogleRegistration = function (response) {
                const consent = document.getElementById('google-privacy-consent');
                const consentError = document.getElementById('google-consent-error');

                if (! consent.checked) {
                    consentError.classList.remove('hidden');
                    return;
                }

                consentError.classList.add('hidden');
                document.getElementById('google-registration-credential').value = response.credential;
                document.getElementById('google-registration-consent').value = '1';
                document.getElementById('google-registration-form').submit();
            };
        </script>
    @endif
</x-guest-layout>
