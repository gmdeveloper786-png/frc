<?php

namespace App\Providers;

use App\Models\UserNotification;
use App\Services\SettingService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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
