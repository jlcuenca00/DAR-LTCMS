        <div class="registration-field">
            <x-input-label class="form-label" for="username" :value="__('Username')" />
            <x-text-input id="username" class="form-input" type="text" name="username" :value="old('username')" required autocomplete="username" />
            <p class="registration-help">Use letters, numbers, dashes, or underscores. You will use this to sign in.</p>
            <x-input-error :messages="$errors->get('username')" class="mt-2" />
        </div>

        <div class="registration-field">
            <x-input-label class="form-label" for="email" :value="__('Email address')" />
            <x-text-input id="email" class="form-input" type="email" name="email" :value="old('email')" required autocomplete="email" />
            <p class="registration-help">We will email your registration review update here. Verify the link we send before using email password recovery.</p>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="registration-field">
            <x-input-label class="form-label" for="password" :value="__('Password')" />
            <x-text-input id="password" class="form-input" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="registration-field">
            <x-input-label class="form-label" for="password_confirmation" :value="__('Confirm password')" />
            <x-text-input id="password_confirmation" class="form-input" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

