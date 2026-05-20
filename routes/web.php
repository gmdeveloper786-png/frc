<?php

use App\Http\Controllers\Web\AjaxChildController;
use App\Http\Controllers\Web\AjaxTherapistController;
use App\Http\Controllers\Web\AssessmentController;
use App\Http\Controllers\Web\Auth\LoginController;
use App\Http\Controllers\Web\Auth\PasswordController;
use App\Http\Controllers\Web\Auth\RegisterController;
use App\Http\Controllers\Web\BranchController;
use App\Http\Controllers\Web\ChildAssessmentController;
use App\Http\Controllers\Web\ChildController;
use App\Http\Controllers\Web\ChildEnrollmentController;
use App\Http\Controllers\Web\ChildPaymentController;
use App\Http\Controllers\Web\ChildProfileController;
use App\Http\Controllers\Web\ChildScheduleController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\DisabilityController;
use App\Http\Controllers\Web\EnrollmentController;
use App\Http\Controllers\Web\PaymentController;
use App\Http\Controllers\Web\ReportController;
use App\Http\Controllers\Web\SettingController;
use App\Http\Controllers\Web\ServiceController;
use App\Http\Controllers\Web\StaffProfileWebController;
use App\Http\Controllers\Web\RolePermissionController;
use App\Http\Controllers\Web\StaffUserController;
use App\Http\Controllers\Web\TherapistAssessmentController;
use App\Http\Controllers\Web\TherapistChildController;
use App\Http\Controllers\Web\TherapistController;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\TherapistProfileWebController;
use App\Http\Controllers\Web\TherapistSessionController;
use Illuminate\Support\Facades\Route;

// ── Guest routes ──────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('login',           [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login',          [LoginController::class, 'login'])->name('login.post');
    Route::get('register',        [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('register',       [RegisterController::class, 'register'])->name('register.post');
    Route::get('forgot-password', [PasswordController::class, 'showForgotForm'])->name('password.request');
    Route::post('forgot-password', [PasswordController::class, 'sendResetLink'])->name('password.email');
    Route::get('reset-password/{token}', [PasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('reset-password', [PasswordController::class, 'resetPassword'])->name('password.update');
});

// ── Logout ────────────────────────────────────────────────────────────────────
Route::post('logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// ── Authenticated routes ──────────────────────────────────────────────────────
Route::middleware(['auth', 'active_user'])->group(function () {

    // Dashboards
    Route::middleware('role:super_admin')->get('/super-admin/dashboard', [DashboardController::class, 'superAdmin'])->name('dashboard.super-admin');
    Route::middleware('role:admin')->get('/admin/dashboard', [DashboardController::class, 'admin'])->name('dashboard.admin');
    Route::middleware('role:therapist')->get('/therapist/dashboard', [DashboardController::class, 'therapist'])->name('dashboard.therapist');
    Route::middleware('role:finance')->get('/finance/dashboard', [DashboardController::class, 'finance'])->name('dashboard.finance');
    Route::middleware(['role:child', 'approved_child'])->get('/child/dashboard', [DashboardController::class, 'child'])->name('dashboard.child');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/profile', [StaffProfileWebController::class, 'show'])->name('profile');
        Route::put('/profile/password', [StaffProfileWebController::class, 'updatePassword'])->name('profile.password');
    });

    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/mark-all-as-read', [NotificationController::class, 'markAllRead'])->name('mark-all-read');
        Route::post('/bulk-mark-as-read', [NotificationController::class, 'bulkMarkRead'])->name('bulk-mark-read');
        Route::post('/bulk-mark-as-unread', [NotificationController::class, 'bulkMarkUnread'])->name('bulk-mark-unread');
        Route::delete('/bulk-delete', [NotificationController::class, 'bulkDelete'])->name('bulk-delete');
        Route::delete('/delete-read', [NotificationController::class, 'deleteRead'])->name('delete-read');
        Route::get('/{notification}/open', [NotificationController::class, 'open'])->name('open')->whereNumber('notification');
        Route::get('/{notification}/follow', [NotificationController::class, 'follow'])->name('follow')->whereNumber('notification');
        Route::post('/{notification}/mark-as-read', [NotificationController::class, 'markRead'])->name('mark-read')->whereNumber('notification');
        Route::post('/{notification}/mark-as-unread', [NotificationController::class, 'markUnread'])->name('mark-unread')->whereNumber('notification');
        Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('delete')->whereNumber('notification');
    });

    Route::get('/payments/{id}', [PaymentController::class, 'show'])
        ->name('payments.show')
        ->where('id', '[0-9]+');

    Route::get('/payments/{id}/receipt', [PaymentController::class, 'receipt'])
        ->name('payments.receipt')
        ->where('id', '[0-9]+');

    Route::middleware(['can:viewFullSchedule,enrollment'])->group(function () {
        Route::get('/enrollments/{enrollment}/schedule', [EnrollmentController::class, 'fullSchedule'])
            ->name('enrollments.schedule');
        Route::get('/enrollments/{enrollment}/schedule/occurrences/{schedule}', [EnrollmentController::class, 'fullScheduleOccurrence'])
            ->name('enrollments.schedule.show');
    });

    Route::middleware('permission:manage_settings')->group(function () {
        Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    });

    // ── Staff modules (permission-based — any role with the matching permission) ─
    Route::middleware('permission:manage_enrollments,manage_assessments,manage_therapists')->group(function () {
        Route::get('/ajax/branches/{branch}/therapists', [AjaxTherapistController::class, 'therapistsByBranch'])
            ->name('ajax.branches.therapists');
        Route::get('/ajax/therapists/{therapist}/available-days', [AjaxTherapistController::class, 'availableDays'])
            ->name('ajax.therapists.available-days')->whereNumber('therapist');
        Route::get('/ajax/therapists/{therapist}/available-slots', [AjaxTherapistController::class, 'availableSlots'])
            ->name('ajax.therapists.available-slots')->whereNumber('therapist');
        Route::get('/ajax/therapists/{therapist}/occupied-slots', [AjaxTherapistController::class, 'occupiedSlots'])
            ->name('ajax.therapists.occupied-slots')->whereNumber('therapist');
        Route::get('/ajax/children/approved-search', [AjaxChildController::class, 'searchApproved'])
            ->name('ajax.children.approved-search');
    });

    Route::middleware('permission:approve_children')->prefix('children')->name('children.')->group(function () {
        Route::get('/pending', [ChildController::class, 'pendingApprovals'])->name('pending');
        Route::post('/{id}/approve', [ChildController::class, 'approve'])->name('approve')->where('id', '[0-9]+');
        Route::post('/{id}/reject', [ChildController::class, 'reject'])->name('reject')->where('id', '[0-9]+');
    });

    Route::middleware('permission:manage_children')->prefix('children')->name('children.')->group(function () {
        Route::get('/', [ChildController::class, 'index'])->name('index');
        Route::get('/{id}/edit', [ChildController::class, 'edit'])->name('edit')->where('id', '[0-9]+');
        Route::put('/{id}', [ChildController::class, 'update'])->name('update')->where('id', '[0-9]+');
        Route::delete('/{id}', [ChildController::class, 'destroy'])->name('destroy')->where('id', '[0-9]+');
        Route::get('/{id}', [ChildController::class, 'show'])->name('show')->where('id', '[0-9]+');
    });

    Route::middleware('permission:manage_disabilities')->group(function () {
        Route::resource('disabilities', DisabilityController::class);
    });
    Route::middleware('permission:manage_services')->group(function () {
        Route::resource('services', ServiceController::class);
    });
    Route::middleware('permission:manage_branches')->group(function () {
        Route::resource('branches', BranchController::class);
    });
    Route::middleware('permission:manage_therapists')->group(function () {
        Route::resource('therapists', TherapistController::class);
    });

    Route::middleware('permission:manage_assessments')->prefix('assessments')->name('assessments.')->group(function () {
        Route::get('/', [AssessmentController::class, 'index'])->name('index');
        Route::get('/create', [AssessmentController::class, 'create'])->name('create');
        Route::post('/', [AssessmentController::class, 'store'])->name('store');
        Route::get('/{assessment}', [AssessmentController::class, 'show'])->name('show');
        Route::get('/{assessment}/edit', [AssessmentController::class, 'edit'])->name('edit');
        Route::put('/{assessment}', [AssessmentController::class, 'update'])->name('update');
        Route::delete('/{assessment}', [AssessmentController::class, 'destroy'])->name('destroy');
        Route::post('/{assessment}/complete', [AssessmentController::class, 'complete'])->name('complete');
        Route::post('/{assessment}/cancel', [AssessmentController::class, 'cancel'])->name('cancel');
        Route::post('/{assessment}/notes', [AssessmentController::class, 'storeNote'])->name('notes');
    });

    Route::middleware('permission:manage_enrollments')->prefix('enrollments')->name('enrollments.')->group(function () {
        Route::get('/', [EnrollmentController::class, 'index'])->name('index');
        Route::get('/create', [EnrollmentController::class, 'create'])->name('create');
        Route::post('/', [EnrollmentController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [EnrollmentController::class, 'edit'])->name('edit')->where('id', '[0-9]+');
        Route::put('/{id}', [EnrollmentController::class, 'update'])->name('update')->where('id', '[0-9]+');
        Route::get('/{id}', [EnrollmentController::class, 'show'])->name('show')->where('id', '[0-9]+');
        Route::post('/{id}/approve', [EnrollmentController::class, 'approve'])->name('approve')->where('id', '[0-9]+');
        Route::post('/{id}/reject', [EnrollmentController::class, 'reject'])->name('reject')->where('id', '[0-9]+');
        Route::delete('/{id}', [EnrollmentController::class, 'destroy'])->name('destroy')->where('id', '[0-9]+');
    });

    Route::middleware('permission:approve_high_discount')->group(function () {
        Route::get('/enrollments/high-discount', [EnrollmentController::class, 'pendingHighDiscount'])->name('enrollments.high-discount');
    });

    Route::middleware('permission:manage_payments')->group(function () {
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('/manual-payment/create', [PaymentController::class, 'createManual'])->name('payments.manual.create');
        Route::post('/manual-payment', [PaymentController::class, 'storeManual'])->name('payments.manual.store');
    });

    Route::middleware('permission:verify_payments')->group(function () {
        Route::get('/payments/pending', [PaymentController::class, 'pendingVerification'])->name('payments.pending');
        Route::post('/payments/{id}/verify', [PaymentController::class, 'verify'])->name('payments.verify')->where('id', '[0-9]+');
        Route::post('/payments/{id}/reject', [PaymentController::class, 'reject'])->name('payments.reject')->where('id', '[0-9]+');
    });

    Route::middleware('permission:view_finance_reports')->prefix('reports')->name('reports.')->group(function () {
        Route::get('/finance', [ReportController::class, 'finance'])->name('finance');
        Route::get('/finance/export/{format}', [ReportController::class, 'financeExport'])->name('finance.export')->where('format', 'csv|pdf');
        Route::get('/finance/print', [ReportController::class, 'financePrint'])->name('finance.print');
    });

    // ── Super Admin only ─────────────────────────────────────────────────────
    Route::middleware('role:super_admin')->prefix('super-admin')->name('super-admin.')->group(function () {
        Route::get('/staff-users', [StaffUserController::class, 'index'])->name('staff-users.index');
        Route::get('/staff-users/create', [StaffUserController::class, 'create'])->name('staff-users.create');
        Route::post('/staff-users', [StaffUserController::class, 'store'])->name('staff-users.store');
        Route::get('/staff-users/{user}/edit', [StaffUserController::class, 'edit'])->name('staff-users.edit');
        Route::put('/staff-users/{user}', [StaffUserController::class, 'update'])->name('staff-users.update');
        Route::delete('/staff-users/{user}', [StaffUserController::class, 'destroy'])->name('staff-users.destroy');
        Route::patch('/staff-users/{user}/toggle-status', [StaffUserController::class, 'toggleStatus'])->name('staff-users.toggle-status');

        Route::get('/roles', [RolePermissionController::class, 'index'])->name('roles.index');
        Route::get('/roles/{role}/edit', [RolePermissionController::class, 'edit'])->name('roles.edit');
        Route::put('/roles/{role}', [RolePermissionController::class, 'update'])->name('roles.update');
    });

    // ── Finance routes ────────────────────────────────────────────────────────
    Route::middleware('role:finance')->group(function () {
        Route::get('/finance/profile', [StaffProfileWebController::class, 'show'])->name('finance.profile');
        Route::put('/finance/profile/password', [StaffProfileWebController::class, 'updatePassword'])->name('finance.profile.password');
        Route::get('/finance/payments',          [PaymentController::class, 'index'])->name('finance.payments');
        Route::get('/finance/payments/pending',  [PaymentController::class, 'pendingVerification'])->name('finance.payments.pending');
        Route::get('/finance/payments/{id}', [PaymentController::class, 'show'])->name('finance.payments.show')->where('id', '[0-9]+');
        Route::post('/finance/payments/{id}/verify', [PaymentController::class, 'verify'])->name('finance.payments.verify');
        Route::post('/finance/payments/{id}/reject', [PaymentController::class, 'reject'])->name('finance.payments.reject');
        Route::get('/finance/payments/{id}/receipt', [PaymentController::class, 'receipt'])->name('finance.payments.receipt');
        Route::get('/finance/manual-payment/create', [PaymentController::class, 'createManual'])->name('finance.payments.manual.create');
        Route::post('/finance/manual-payment',       [PaymentController::class, 'storeManual'])->name('finance.payments.manual.store');
        Route::get('/finance/reports',               [ReportController::class, 'finance'])->name('finance.reports');
        Route::get('/finance/reports/export/{format}', [ReportController::class, 'financeExport'])->name('finance.reports.export')->where('format', 'csv|pdf');
        Route::get('/finance/reports/print',         [ReportController::class, 'financePrint'])->name('finance.reports.print');
    });

    // ── Therapist routes ──────────────────────────────────────────────────────
    Route::middleware('role:therapist')->prefix('therapist')->name('therapist.')->group(function () {
        Route::get('/schedule', function (\Illuminate\Http\Request $request) {
            return redirect()->route('therapist.sessions.index', $request->query());
        })->name('schedule');
        Route::get('/sessions', [TherapistSessionController::class, 'index'])->name('sessions.index');
        Route::get('/sessions/{schedule}/group', [TherapistSessionController::class, 'showGroupOccurrence'])->name('sessions.group-show');
        Route::post('/sessions/{schedule}/group/start', [TherapistSessionController::class, 'startGroup'])->name('sessions.group-start');
        Route::post('/sessions/{schedule}/group/complete', [TherapistSessionController::class, 'completeGroup'])->name('sessions.group-complete');
        Route::post('/sessions/{schedule}/group/cancel', [TherapistSessionController::class, 'cancelGroup'])->name('sessions.group-cancel');
        Route::get('/sessions/{schedule}/show', [TherapistSessionController::class, 'showOccurrence'])->name('sessions.show');
        Route::get('/sessions/{schedule}/occurrence-detail', [TherapistSessionController::class, 'occurrenceDetail'])->name('sessions.occurrence-detail');

        Route::post('/sessions/{schedule}/start', [TherapistSessionController::class, 'start'])->name('sessions.start');
        Route::post('/sessions/{schedule}/complete', [TherapistSessionController::class, 'complete'])->name('sessions.complete');
        Route::post('/sessions/{schedule}/cancel', [TherapistSessionController::class, 'cancel'])->name('sessions.cancel');
        Route::post('/sessions/{schedule}/no-show', [TherapistSessionController::class, 'noShow'])->name('sessions.no-show');
        Route::post('/sessions/{schedule}/notes', [TherapistSessionController::class, 'updateNotes'])->name('sessions.notes');

        Route::get('/children', [TherapistChildController::class, 'index'])->name('children.index');
        Route::get('/children/{child}', [TherapistChildController::class, 'show'])->name('children.show')->whereNumber('child');

        Route::get('/notifications', fn () => redirect()->route('notifications.index'))->name('notifications.index');

        Route::get('/profile', [TherapistProfileWebController::class, 'show'])->name('profile');
        Route::put('/profile/password', [TherapistProfileWebController::class, 'updatePassword'])->name('profile.password');

        Route::prefix('assessments')->name('assessments.')->group(function () {
            Route::get('/', [TherapistAssessmentController::class, 'index'])->name('index');
            Route::get('/{assessment}', [TherapistAssessmentController::class, 'show'])->name('show');
            Route::post('/{assessment}/notes', [TherapistAssessmentController::class, 'storeNote'])->name('notes');
            Route::put('/{assessment}/notes/{note}', [TherapistAssessmentController::class, 'updateNote'])->name('notes.update');
            Route::delete('/{assessment}/notes/{note}', [TherapistAssessmentController::class, 'destroyNote'])->name('notes.destroy');
            Route::post('/{assessment}/complete', [TherapistAssessmentController::class, 'complete'])->name('complete');
        });
    });

    // ── Authenticated child portal (`/my-*`, role child + approved_child) ───────
    // Keep separate from admin `ChildController` under `/children/*` — see controller docblocks.
    Route::middleware(['role:child', 'approved_child'])->group(function () {
        Route::get('/my-assessments',      fn () => view('child.assessments', [
            'assessments' => app(\App\Services\AssessmentService::class)->getForChild(auth()->id()),
        ]))->name('child.assessments');

        Route::get('/my-assessments/{assessment}', [ChildAssessmentController::class, 'show'])->name('child.assessments.show');

        Route::get('/my-enrollment', [ChildEnrollmentController::class, 'index'])->name('child.enrollment');
        Route::get('/my-enrollment/{enrollment}', [ChildEnrollmentController::class, 'show'])->name('child.enrollment.show');

        Route::get('/upload-slip',   [PaymentController::class, 'childSlipCreate'])->name('child.upload-slip');
        Route::post('/upload-slip',  [PaymentController::class, 'childSlipStore'])->name('child.upload-slip.store');

        Route::get('/my-payments', [ChildPaymentController::class, 'index'])->name('child.payments');

        Route::get('/my-schedule', [ChildScheduleController::class, 'index'])->name('child.schedule.index');
        Route::get('/my-schedule/{schedule}', [ChildScheduleController::class, 'show'])->name('child.schedule.show');

        Route::get('/my-profile', [ChildProfileController::class, 'edit'])->name('child.profile.edit');
        Route::put('/my-profile', [ChildProfileController::class, 'update'])->name('child.profile.update');
        Route::put('/my-profile/password', [ChildProfileController::class, 'updatePassword'])->name('child.profile.password');
    });
});

// ── Root redirect ─────────────────────────────────────────────────────────────
Route::get('/', function () {
    if (auth()->check()) {
        $role = auth()->user()->role?->name;
        return match ($role) {
            'super_admin' => redirect()->route('dashboard.super-admin'),
            'admin'       => redirect()->route('dashboard.admin'),
            'therapist'   => redirect()->route('dashboard.therapist'),
            'finance'     => redirect()->route('dashboard.finance'),
            'child'       => redirect()->route('dashboard.child'),
            default       => redirect()->route('login'),
        };
    }
    return redirect()->route('login');
});
