<?php

namespace App\Providers;

use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\UserNotification;
use App\Policies\EnrollmentPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\UserNotificationPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Enrollment::class      => EnrollmentPolicy::class,
        Payment::class         => PaymentPolicy::class,
        UserNotification::class => UserNotificationPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
