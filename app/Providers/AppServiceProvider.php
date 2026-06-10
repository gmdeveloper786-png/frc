<?php

namespace App\Providers;

use App\Models\UserNotification;
use App\Services\SettingService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\Paginator;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Ensure global helpers (frc_pkr, etc.) load even if Composer's "files" autoload
        // was not regenerated on the server after deploy (common on shared hosting).
        $helpers = app_path('helpers.php');
        if (is_file($helpers)) {
            require_once $helpers;
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production') && config('app.debug')) {
            throw new \RuntimeException(
                'APP_DEBUG must be false in production. Set APP_DEBUG=false in .env before deploying.',
            );
        }

        Password::defaults(function () {
            return Password::min(8)->letters()->numbers();
        });

        if (config('app.force_https')) {
            URL::forceScheme('https');
        }

        Schema::defaultStringLength(191);
        Paginator::useBootstrapFive();

        Route::bind('notification', function (string $value) {
            $userId = auth()->id();
            if (! $userId) {
                abort(401);
            }

            $notification = UserNotification::query()->whereKey($value)->first();
            if ($notification === null) {
                abort(404);
            }

            if ((int) $notification->user_id !== (int) $userId) {
                abort(403, 'You do not have access to this notification.');
            }

            return $notification;
        });

        View::composer('*', function ($view): void {
            $view->with('frc', app(SettingService::class)->forViews());
        });
    }
}
