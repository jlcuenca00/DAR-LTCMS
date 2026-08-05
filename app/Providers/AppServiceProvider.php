<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Password::defaults(fn () => Password::min(12)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols());

        // Surface N+1 query problems during development and testing without
        // affecting production availability.
        Model::preventLazyLoading(app()->environment('local'));
    }
}
