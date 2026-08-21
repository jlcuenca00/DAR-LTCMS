<?php

namespace App\Providers;

use App\Http\Controllers\Staff\ProtectedStorageController;
use App\Models\LandTransferApplication;
use App\Observers\LandTransferApplicationObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Route;
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

        // Source scans are sensitive administrative reference files. Keep their
        // delivery behind the same authenticated Staff boundary as source-package
        // records instead of exposing storage/app/public through a web symlink.
        Route::middleware(['web', 'auth', 'role:staff'])
            ->get('/staff/protected-storage/{path}', ProtectedStorageController::class)
            ->where('path', '.*')
            ->name('staff.protected-storage.show');

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