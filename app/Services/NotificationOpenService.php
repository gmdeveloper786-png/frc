<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Assessment;
use App\Models\Enrollment;
use App\Models\EnrollmentSchedule;
use App\Models\Payment;
use App\Models\ProgressNote;
use App\Models\Role;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Validates notification target (module + record_id) and user access before redirecting to action_url.
 */
final class NotificationOpenService
{
    public const MSG_GENERIC_MISSING = 'This record is no longer available. It may have been deleted, cancelled, archived, or updated by admin.';

    public const MSG_UNAUTHORIZED = 'You do not have permission to access this record.';

    public const MSG_ASSESSMENT_MISSING = 'This assessment is no longer available. It may have been deleted, cancelled, archived, or updated by admin.';

    public const MSG_ENROLLMENT_MISSING = 'This enrollment is no longer available.';

    public const MSG_PAYMENT_MISSING = 'This payment record is no longer available.';

    public const MSG_SESSION_MISSING = 'This session is no longer available.';

    public const MSG_PROGRESS_NOTE_MISSING = 'This progress note is no longer available.';

    public const MSG_USER_MISSING = 'This user record is no longer available.';

    public function __construct(
        private readonly NotificationActionUrlAuthorizer $actionUrls,
    ) {}

    public function redirectAfterOpen(Request $request, UserNotification $notification): RedirectResponse
    {
        $user = $request->user();
        $rawUrl = $notification->action_url;
        $module = strtolower(trim((string) ($notification->module ?? '')));
        $recordId = $notification->record_id;

        if ($notification->type === UserNotification::TYPE_STAFF_ACCOUNT_CREATED) {
            $routeName = $user->loadMissing('role')->dashboardRouteName();
            if ($routeName !== null) {
                $relative = route($routeName, [], false);

                return $this->redirectToAuthorizedRelative($request, $user, $relative);
            }
        }

        $access = $this->evaluateRecordAccess($user, $notification, $module, $recordId);

        if ($access === 'missing') {
            Log::warning('notification.target_missing', [
                'notification_id' => $notification->id,
                'user_id'           => $user->id,
                'module'            => $module,
                'type'              => $notification->type,
                'record_id'         => $recordId,
            ]);

            return redirect()
                ->route('notifications.index')
                ->with('warning', $this->missingMessageForModule($module));
        }

        if ($access === 'unauthorized') {
            return redirect()
                ->route('notifications.index')
                ->with('error', self::MSG_UNAUTHORIZED);
        }

        // Staff enrollment notifications may store the child portal URL (`/my-enrollment`); fee-fully-paid rows always need a staff target.
        $actionPath = '/' . ltrim((string) (parse_url((string) $rawUrl, PHP_URL_PATH) ?? ''), '/');
        $targetsChildEnrollmentPortal = str_starts_with($actionPath, '/my-enrollment');
        if ($module === 'enrollments' && $recordId !== null && $recordId > 0 && $access === 'ok'
            && ($user->isSuperAdmin() || $user->isAdmin() || $user->isFinance())
            && ! $user->isChild()
            && ($targetsChildEnrollmentPortal || (string) $notification->type === UserNotification::TYPE_FEE_FULLY_PAID)) {
            $relative = $user->isFinance()
                ? route('finance.payments', [], false)
                : route('enrollments.show', $recordId, false);

            return $this->redirectToAuthorizedRelative($request, $user, $relative);
        }

        // Pending slip queue lives under different routes: `/payments/pending` (admin) vs `/finance/payments/pending` (finance).
        if ($module === 'payments' && $recordId !== null && $recordId > 0 && $access === 'ok'
            && (string) $notification->type === UserNotification::TYPE_PAYMENT_SLIP_UPLOADED
            && ($user->isFinance() || $user->isAdmin() || $user->isSuperAdmin())) {
            $relative = $user->isFinance()
                ? route('finance.payments.pending', [], false)
                : route('payments.pending', [], false);

            return $this->redirectToAuthorizedRelative($request, $user, $relative);
        }

        if ($module === 'assessments' && $recordId !== null && $recordId > 0 && $access === 'ok') {
            if (! in_array((string) $notification->type, UserNotification::ASSESSMENT_NOTIFICATION_TYPES, true)) {
                return redirect()
                    ->route('notifications.index')
                    ->with('error', self::MSG_UNAUTHORIZED);
            }

            $assessment = Assessment::query()->find($recordId);
            if ($assessment === null) {
                return redirect()
                    ->route('notifications.index')
                    ->with('warning', self::MSG_ASSESSMENT_MISSING);
            }

            $relative = $this->assessmentUrlPathForUser($user, $assessment);
            if ($relative === null) {
                return redirect()
                    ->route('notifications.index')
                    ->with('error', self::MSG_UNAUTHORIZED);
            }

            return $this->redirectToAuthorizedRelative($request, $user, $relative);
        }

        if (! filled($rawUrl)) {
            return redirect()
                ->route('notifications.index')
                ->with('success', 'Notification marked as read.');
        }

        $safe = $this->actionUrls->authorizedUrlFor($user, (string) $rawUrl);
        if ($safe === null) {
            return redirect()
                ->route('notifications.index')
                ->with('error', self::MSG_UNAUTHORIZED);
        }

        $path = parse_url($safe, PHP_URL_PATH) ?? '/';
        $query = parse_url($safe, PHP_URL_QUERY);
        $target = rtrim($request->root(), '/').$path.($query !== null && $query !== '' ? '?'.$query : '');

        return redirect()->to($target);
    }

    private function redirectToAuthorizedRelative(Request $request, User $user, string $relativePathOrUrl): RedirectResponse
    {
        $safe = $this->actionUrls->authorizedUrlFor($user, $relativePathOrUrl);
        if ($safe === null) {
            return redirect()
                ->route('notifications.index')
                ->with('error', self::MSG_UNAUTHORIZED);
        }

        $path = parse_url($safe, PHP_URL_PATH) ?? '/';
        $query = parse_url($safe, PHP_URL_QUERY);
        $target = rtrim($request->root(), '/').$path.($query !== null && $query !== '' ? '?'.$query : '');

        return redirect()->to($target);
    }

    /**
     * Relative URL for the correct assessment detail route for this user (passed to {@see NotificationActionUrlAuthorizer}).
     */
    private function assessmentUrlPathForUser(User $user, Assessment $assessment): ?string
    {
        if ($user->isChild()) {
            if (! $assessment->children()->where('users.id', $user->id)->exists()) {
                return null;
            }

            return route('child.assessments.show', $assessment, false);
        }

        if ($user->isTherapist()) {
            if ((int) $assessment->therapist_id !== (int) $user->id) {
                return null;
            }

            return route('therapist.assessments.show', $assessment, false);
        }

        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return route('assessments.show', $assessment, false);
        }

        return null;
    }

    /**
     * @return 'ok'|'missing'|'unauthorized'|'skip'
     */
    private function evaluateRecordAccess(User $user, UserNotification $notification, string $module, ?int $recordId): string
    {
        if ($recordId === null || $recordId <= 0) {
            return 'skip';
        }

        return match ($module) {
            'assessments'     => $this->accessAssessment($user, $recordId),
            'enrollments'     => $this->accessEnrollment($user, $recordId, $notification->type),
            'payments'        => $this->accessPayment($user, $recordId),
            'sessions'        => $this->accessSession($user, $recordId),
            'progress_notes'  => $this->accessProgressNote($user, $recordId),
            'children'        => $this->accessChildRecord($user, $recordId, $notification->type),
            default           => 'skip',
        };
    }

    private function missingMessageForModule(string $module): string
    {
        return match ($module) {
            'assessments'    => self::MSG_ASSESSMENT_MISSING,
            'enrollments'    => self::MSG_ENROLLMENT_MISSING,
            'payments'       => self::MSG_PAYMENT_MISSING,
            'sessions'       => self::MSG_SESSION_MISSING,
            'progress_notes' => self::MSG_PROGRESS_NOTE_MISSING,
            'children'       => self::MSG_USER_MISSING,
            default          => self::MSG_GENERIC_MISSING,
        };
    }

    /** @return 'ok'|'missing'|'unauthorized' */
    private function accessAssessment(User $user, int $assessmentId): string
    {
        $assessment = Assessment::query()->find($assessmentId);
        if ($assessment === null) {
            return 'missing';
        }

        if ($user->isFinance()) {
            return 'unauthorized';
        }

        if ($user->isTherapist()) {
            return (int) $assessment->therapist_id === (int) $user->id ? 'ok' : 'unauthorized';
        }

        if ($user->isChild()) {
            $linked = $assessment->children()->where('users.id', $user->id)->exists();

            return $linked ? 'ok' : 'unauthorized';
        }

        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return 'ok';
        }

        return 'unauthorized';
    }

    /** @return 'ok'|'missing'|'unauthorized' */
    private function accessEnrollment(User $user, int $enrollmentId, string $type): string
    {
        $enrollment = Enrollment::query()->find($enrollmentId);
        if ($enrollment === null) {
            return 'missing';
        }

        if ($user->isFinance()) {
            $financeTypes = [
                UserNotification::TYPE_FEE_FULLY_PAID,
            ];
            if (in_array($type, $financeTypes, true)) {
                return 'ok';
            }

            return str_starts_with($type, 'high_discount') ? 'ok' : 'unauthorized';
        }

        if ($user->isTherapist()) {
            if ((int) $enrollment->therapist_id === (int) $user->id) {
                return 'ok';
            }

            $assigned = $enrollment->schedules()->where('therapist_id', $user->id)->exists();

            return $assigned ? 'ok' : 'unauthorized';
        }

        if ($user->isChild()) {
            return (int) $enrollment->child_id === (int) $user->id ? 'ok' : 'unauthorized';
        }

        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return 'ok';
        }

        return 'unauthorized';
    }

    /** @return 'ok'|'missing'|'unauthorized' */
    private function accessPayment(User $user, int $paymentId): string
    {
        $payment = Payment::query()->find($paymentId);
        if ($payment === null) {
            return 'missing';
        }

        if ($user->isTherapist()) {
            return 'unauthorized';
        }

        if ($user->isChild()) {
            return (int) $payment->child_id === (int) $user->id ? 'ok' : 'unauthorized';
        }

        if ($user->isFinance() || $user->isAdmin() || $user->isSuperAdmin()) {
            return 'ok';
        }

        return 'unauthorized';
    }

    /** @return 'ok'|'missing'|'unauthorized' */
    private function accessSession(User $user, int $scheduleId): string
    {
        $schedule = EnrollmentSchedule::query()->find($scheduleId);
        if ($schedule === null) {
            return 'missing';
        }

        $schedule->loadMissing('enrollment');

        if ($user->isFinance()) {
            return 'unauthorized';
        }

        if ($user->isTherapist()) {
            return (int) $schedule->therapist_id === (int) $user->id ? 'ok' : 'unauthorized';
        }

        if ($user->isChild()) {
            $enrollment = $schedule->enrollment;
            if ($enrollment === null) {
                return 'missing';
            }

            return (int) $enrollment->child_id === (int) $user->id ? 'ok' : 'unauthorized';
        }

        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return 'ok';
        }

        return 'unauthorized';
    }

    /** @return 'ok'|'missing'|'unauthorized' */
    private function accessProgressNote(User $user, int $noteId): string
    {
        $note = ProgressNote::query()->find($noteId);
        if ($note === null) {
            return 'missing';
        }

        if ($user->isFinance() || $user->isChild()) {
            return 'unauthorized';
        }

        if ($user->isTherapist()) {
            return (int) $note->therapist_id === (int) $user->id ? 'ok' : 'unauthorized';
        }

        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return 'ok';
        }

        return 'unauthorized';
    }

    /** @return 'ok'|'missing'|'unauthorized' */
    private function accessChildRecord(User $user, int $childUserId, string $type): string
    {
        $childUser = User::query()->find($childUserId);
        if ($childUser === null) {
            return 'missing';
        }

        if ($user->isChild()) {
            return (int) $user->id === $childUserId ? 'ok' : 'unauthorized';
        }

        if ($user->isTherapist() || $user->isFinance()) {
            return 'unauthorized';
        }

        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return $childUser->role?->name === Role::CHILD ? 'ok' : 'unauthorized';
        }

        return 'unauthorized';
    }
}
