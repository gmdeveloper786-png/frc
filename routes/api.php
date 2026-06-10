<?php

use App\Http\Controllers\Api\AssessmentController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\ChildController;
use App\Http\Controllers\Api\DisabilityController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\StaffUserController as ApiStaffUserController;
use App\Http\Controllers\Api\TherapistController;
use App\Http\Controllers\Api\TherapistPortalApiController;
use App\Http\Controllers\Api\NotificationController as ApiNotificationController;
use Illuminate\Support\Facades\Route;

// ── Auth (public) ─────────────────────────────────────────────────────────────
Route::prefix('auth')->middleware('throttle:10,1')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);
});

// ── Authenticated API routes ──────────────────────────────────────────────────
// Name prefix avoids clashes with web Route::resource names during route caching (`php artisan optimize`).
Route::middleware(['auth:sanctum', 'active_user', 'approved_child', 'throttle:60,1'])->name('api.')->group(function () {

    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('me',           [AuthController::class, 'me']);

    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [ApiNotificationController::class, 'index'])->name('index');
        Route::get('/latest', [ApiNotificationController::class, 'latest'])->name('latest');
        Route::get('/unread-count', [ApiNotificationController::class, 'unreadCount'])->name('unread-count');
        Route::post('/mark-all-as-read', [ApiNotificationController::class, 'markAllRead'])->name('mark-all-read');
        Route::post('/bulk-mark-as-read', [ApiNotificationController::class, 'bulkMarkRead'])->name('bulk-mark-read');
        Route::post('/bulk-mark-as-unread', [ApiNotificationController::class, 'bulkMarkUnread'])->name('bulk-mark-unread');
        Route::post('/bulk-delete', [ApiNotificationController::class, 'bulkDelete'])->name('bulk-delete');
        Route::post('/delete-read', [ApiNotificationController::class, 'deleteRead'])->name('delete-read');
        Route::post('/{id}/mark-as-read', [ApiNotificationController::class, 'markRead'])->whereNumber('id')->name('mark-read');
        Route::post('/{id}/mark-as-unread', [ApiNotificationController::class, 'markUnread'])->whereNumber('id')->name('mark-unread');
        Route::delete('/{id}', [ApiNotificationController::class, 'destroy'])->whereNumber('id')->name('delete');
    });

    // Children
    Route::prefix('children')->group(function () {
        Route::middleware('permission:manage_children')->group(function () {
            Route::get('/',     [ChildController::class, 'index']);
            Route::get('/{id}', [ChildController::class, 'show'])->whereNumber('id');
            Route::put('/{id}', [ChildController::class, 'update'])->whereNumber('id');
        });
        Route::middleware('permission:approve_children')->group(function () {
            Route::post('/{id}/approve', [ChildController::class, 'approve'])->whereNumber('id');
            Route::post('/{id}/reject',  [ChildController::class, 'reject'])->whereNumber('id');
        });
    });

    // Disabilities
    Route::apiResource('disabilities', DisabilityController::class);

    // Services — published list must be registered before {service} routes
    Route::get('services/published', [ServiceController::class, 'published']);
    Route::apiResource('services', ServiceController::class);

    // Branches
    Route::apiResource('branches', BranchController::class);

    // Therapists
    Route::prefix('therapists')->group(function () {
        Route::get('/',                      [TherapistController::class, 'index']);
        Route::get('/{id}',                  [TherapistController::class, 'show'])->whereNumber('id');
        Route::get('/{id}/available-days',   [TherapistController::class, 'availableDays'])->whereNumber('id');
        Route::get('/{id}/available-slots',  [TherapistController::class, 'availableSlots'])->whereNumber('id');
        Route::get('/{id}/occupied-slots',   [TherapistController::class, 'occupiedSlots'])->whereNumber('id');
        Route::middleware('permission:manage_therapists')->group(function () {
            Route::post('/',       [TherapistController::class, 'store']);
            Route::put('/{id}',    [TherapistController::class, 'update'])->whereNumber('id');
            Route::delete('/{id}', [TherapistController::class, 'destroy'])->whereNumber('id');
        });
    });
    Route::get('branches/{branch}/therapists', [TherapistController::class, 'byBranch'])->whereNumber('branch');

    // Assessments
    Route::prefix('assessments')->group(function () {
        Route::middleware('permission:manage_assessments')->group(function () {
            Route::get('/',                [AssessmentController::class, 'index']);
            Route::post('/',               [AssessmentController::class, 'store']);
            Route::put('/{assessment}',    [AssessmentController::class, 'update']);
            Route::delete('/{assessment}', [AssessmentController::class, 'destroy']);
            Route::post('/{assessment}/cancel', [AssessmentController::class, 'cancel']);
        });
        Route::get('/{assessment}',            [AssessmentController::class, 'show']);
        Route::post('/{assessment}/complete', [AssessmentController::class, 'complete']);
        Route::post('/{assessment}/notes',    [AssessmentController::class, 'storeNote']);
    });
    Route::get('child/my-assessments', [AssessmentController::class, 'myAssessments']);

    // Therapist portal API (scoped data + actions)
    Route::middleware('role:therapist')->prefix('therapist')->group(function () {
        Route::get('dashboard', [TherapistPortalApiController::class, 'dashboard']);
        Route::get('my-assessments', [TherapistPortalApiController::class, 'myAssessments']);
        Route::get('my-assessments/today', [TherapistPortalApiController::class, 'assessmentsToday']);
        Route::get('my-assessments/upcoming', [TherapistPortalApiController::class, 'assessmentsUpcoming']);
        Route::get('my-assessments/completed', [TherapistPortalApiController::class, 'assessmentsCompleted']);
        Route::get('my-children', [TherapistPortalApiController::class, 'myChildren']);
        Route::get('my-schedule', [TherapistPortalApiController::class, 'mySchedule']);
        Route::get('my-sessions', [TherapistPortalApiController::class, 'mySessions']);
        Route::post('assessments/{assessment}/notes', [TherapistPortalApiController::class, 'storeAssessmentNote']);
        Route::post('sessions/{schedule}/start', [TherapistPortalApiController::class, 'sessionStart']);
        Route::post('sessions/{schedule}/complete', [TherapistPortalApiController::class, 'sessionComplete']);
        Route::post('sessions/{schedule}/cancel', [TherapistPortalApiController::class, 'sessionCancel']);
        Route::post('sessions/{schedule}/no-show', [TherapistPortalApiController::class, 'sessionNoShow']);
        Route::get('profile', [TherapistPortalApiController::class, 'profile']);
    });

    // Enrollments
    Route::prefix('enrollments')->group(function () {
        Route::middleware('permission:manage_enrollments')->group(function () {
            Route::get('/',                      [EnrollmentController::class, 'index']);
            Route::post('/',                     [EnrollmentController::class, 'store']);
            Route::put('/{enrollment}',          [EnrollmentController::class, 'update']);
            Route::post('/{enrollment}/approve', [EnrollmentController::class, 'approve']);
            Route::post('/{enrollment}/reject',  [EnrollmentController::class, 'reject']);
        });
        Route::get('/{enrollment}',               [EnrollmentController::class, 'show']);
        Route::get('/{enrollment}/fee-summary',   [EnrollmentController::class, 'feeSummary']);
    });
    Route::get('child/my-enrollment', [EnrollmentController::class, 'myEnrollment']);

    // Payments
    Route::prefix('payments')->group(function () {
        Route::post('/child-slip-upload', [PaymentController::class, 'childSlipUpload']);
        Route::middleware('permission:manage_payments')->group(function () {
            Route::get('/',          [PaymentController::class, 'index']);
            Route::post('/manual',   [PaymentController::class, 'manualPayment']);
        });
        Route::middleware('permission:verify_payments')->group(function () {
            Route::post('/{id}/approve', [PaymentController::class, 'verify'])->whereNumber('id');
            Route::post('/{id}/reject',  [PaymentController::class, 'reject'])->whereNumber('id');
        });
        Route::get('/{id}',         [PaymentController::class, 'show'])->whereNumber('id');
        Route::get('/{id}/receipt', [PaymentController::class, 'receipt'])->whereNumber('id');
    });
    Route::middleware('permission:manage_payments')->group(function () {
        Route::get('children/{child}/payments', [PaymentController::class, 'childPayments'])->whereNumber('child');
    });
    Route::middleware('permission:view_finance_reports')->get('finance/student-fees', [PaymentController::class, 'studentFees']);

    // Reports
    Route::middleware('permission:view_finance_reports')->prefix('reports')->group(function () {
        Route::get('/finance',  [ReportController::class, 'finance']);
        Route::get('/payments', [ReportController::class, 'payments']);
    });

    Route::middleware('role:super_admin')->prefix('super-admin/staff-users')->group(function () {
        Route::get('/', [ApiStaffUserController::class, 'index']);
        Route::post('/', [ApiStaffUserController::class, 'store']);
        Route::get('/{user}', [ApiStaffUserController::class, 'show'])->whereNumber('user');
        Route::put('/{user}', [ApiStaffUserController::class, 'update'])->whereNumber('user');
        Route::delete('/{user}', [ApiStaffUserController::class, 'destroy'])->whereNumber('user');
        Route::patch('/{user}/toggle-status', [ApiStaffUserController::class, 'toggleStatus'])->whereNumber('user');
    });
});
