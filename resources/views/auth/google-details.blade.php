<x-public-auth-layout title="Complete Landowner Registration" :wide="true">
    <div class="registration-header"><h1 class="auth-heading">Complete your registration</h1><p class="auth-intro">Google connected · Add your details for DAR staff verification.</p></div>
    @include('auth.partials.registration-wizard', ['googleSignup' => true])
    <form method="POST" action="{{ route('register.google.cancel') }}" class="registration-signin">@csrf<button type="submit" class="signup-text-button">Cancel and choose another sign-up method</button></form>
</x-public-auth-layout>
