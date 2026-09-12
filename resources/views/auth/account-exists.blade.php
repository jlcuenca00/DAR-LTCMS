<x-public-auth-layout title="Account Already Exists">
    <h1 class="auth-heading">This account already exists.</h1>
    <p class="auth-intro">
        An account is already registered with these Google account details.
        Sign in to your existing account instead. If you registered with a username
        and password, use those details to sign in.
    </p>
    <a href="{{ route('login') }}" class="login-button">Proceed to sign in</a>
</x-public-auth-layout>
