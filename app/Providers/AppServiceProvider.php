<?php

namespace App\Providers;

use App\Models\LandTransferApplication;
use App\Observers\LandTransferApplicationObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
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
        Password::defaults(fn () => Password::min(8)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols());

        LandTransferApplication::observe(LandTransferApplicationObserver::class);

        // Use the shared DAR-LTCMS pagination UI across all paginated lists.
        // Desktop shows numbered pages with the first/last page visible, while
        // mobile keeps a compact Previous / Page x of y / Next layout.
        Paginator::defaultView('components.pagination');
        Paginator::defaultSimpleView('components.pagination');

        // Surface N+1 query problems during development and testing without
        // affecting production availability.
        Model::preventLazyLoading(app()->environment('local'));
    }
}