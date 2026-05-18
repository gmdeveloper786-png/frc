<?php

namespace App\Providers;

use App\Repositories\Eloquent\AssessmentRepository;
use App\Repositories\Eloquent\BranchRepository;
use App\Repositories\Eloquent\DisabilityRepository;
use App\Repositories\Eloquent\EnrollmentRepository;
use App\Repositories\Eloquent\PaymentRepository;
use App\Repositories\Eloquent\ReportRepository;
use App\Repositories\Eloquent\ServiceRepository;
use App\Repositories\Eloquent\TherapistRepository;
use App\Repositories\Eloquent\UserRepository;
use App\Repositories\Interfaces\AssessmentRepositoryInterface;
use App\Repositories\Interfaces\BranchRepositoryInterface;
use App\Repositories\Interfaces\DisabilityRepositoryInterface;
use App\Repositories\Interfaces\EnrollmentRepositoryInterface;
use App\Repositories\Interfaces\PaymentRepositoryInterface;
use App\Repositories\Interfaces\ReportRepositoryInterface;
use App\Repositories\Interfaces\ServiceRepositoryInterface;
use App\Repositories\Interfaces\TherapistRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(DisabilityRepositoryInterface::class, DisabilityRepository::class);
        $this->app->bind(ServiceRepositoryInterface::class, ServiceRepository::class);
        $this->app->bind(BranchRepositoryInterface::class, BranchRepository::class);
        $this->app->bind(TherapistRepositoryInterface::class, TherapistRepository::class);
        $this->app->bind(AssessmentRepositoryInterface::class, AssessmentRepository::class);
        $this->app->bind(EnrollmentRepositoryInterface::class, EnrollmentRepository::class);
        $this->app->bind(PaymentRepositoryInterface::class, PaymentRepository::class);
        $this->app->bind(ReportRepositoryInterface::class, ReportRepository::class);
    }
}
