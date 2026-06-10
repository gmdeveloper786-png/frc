<?php

namespace App\Providers;

use App\Models\Assessment;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserNotification;
use App\Policies\AssessmentPolicy;
use App\Policies\EnrollmentPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\UserNotificationPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class             => UserPolicy::class,
        Assessment::class       => AssessmentPolicy::class,
        Enrollment::class       => EnrollmentPolicy::class,
        Payment::class          => PaymentPolicy::class,
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
