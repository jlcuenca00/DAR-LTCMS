<?php

namespace App\Providers;

use App\Models\LandTransferApplication;
use App\Models\Parcel;
use App\Models\SystemNotification;
use App\Observers\LandTransferApplicationObserver;
use App\Policies\LandTransferApplicationPolicy;
use App\Policies\ParcelPolicy;
use App\Policies\SystemNotificationPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Gate;
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

        RateLimiter::for('password-change', function (Request $request) {
            $userKey = $request->user()?->getAuthIdentifier() ?? 'guest';

            return Limit::perMinute(6)->by($userKey.'|'.$request->ip());
        });

        LandTransferApplication::observe(LandTransferApplicationObserver::class);

        Gate::policy(Parcel::class, ParcelPolicy::class);
        Gate::policy(LandTransferApplication::class, LandTransferApplicationPolicy::class);
        Gate::policy(SystemNotification::class, SystemNotificationPolicy::class);

        // Use the shared DAR-LTCMS pagination UI across all paginated lists.
        // Desktop shows numbered pages with the first/last page visible, while
        // mobile keeps a compact Previous / Page x of y / Next layout.
        Paginator::defaultView('components.pagination');
        Paginator::defaultSimpleView('components.pagination');

        // Catch model-usage mistakes before they can reach production.
        // Production remains availability-first, while local/test/CI fail loudly
        // on silently discarded mass-assignment attributes.
        Model::preventSilentlyDiscardingAttributes(! app()->environment('production'));

        // Surface N+1 query problems during local development without affecting
        // production availability. Test/CI enforcement is audited separately.
        Model::preventLazyLoading(app()->environment('local'));
    }
}