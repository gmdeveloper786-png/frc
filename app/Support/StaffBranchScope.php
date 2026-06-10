<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Assessment;
use App\Models\Branch;
use App\Models\Enrollment;
use App\Models\EnrollmentSchedule;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class StaffBranchScope
{
    /** @return Collection<int, Branch> */
    public static function publishedBranchesFor(User $user): Collection
    {
        return Branch::query()
            ->published()
            ->forStaff($user)
            ->forDropdown()
            ->orderedForDropdown()
            ->get();
    }

    public static function enforceChildBranch(User $staff, User $child): void
    {
        abort_unless($child->isChild(), 404);
        abort_unless(
            Gate::forUser($staff)->allows('viewChild', $child),
            403,
            'You are not authorized to access this child.',
        );
    }

    public static function enforceTherapistBranch(User $staff, User $therapist): void
    {
        if ($staff->isSuperAdmin()) {
            return;
        }

        if ($staff->isAdmin() && $staff->branch_id) {
            $therapist->loadMissing('therapistProfile');
            abort_if(
                (int) $therapist->therapistProfile?->branch_id !== (int) $staff->branch_id,
                403,
                'This therapist belongs to another branch.'
            );

            return;
        }
    }

    public static function enforceBranchCatalogAccess(User $staff, int $branchId): void
    {
        if ($staff->isSuperAdmin()) {
            return;
        }

        if ($locked = self::lockedBranchId($staff)) {
            abort_if($branchId !== $locked, 403, 'This branch is outside your assignment.');
        }
    }

    public static function assertBranchAssignable(User $staff, int $branchId): void
    {
        if ($staff->isSuperAdmin()) {
            return;
        }

        if ($staff->isAdmin() && $staff->branch_id) {
            if ((int) $branchId !== (int) $staff->branch_id) {
                throw ValidationException::withMessages([
                    'branch_id' => ['You can only assign records to your branch.'],
                ]);
            }
        }
    }

    public static function lockedBranchId(User $staff): ?int
    {
        if ($staff->isAdmin() && ! $staff->isSuperAdmin() && $staff->branch_id) {
            return (int) $staff->branch_id;
        }

        return null;
    }

    public static function enforceAssessmentBranch(User $staff, Assessment $assessment): void
    {
        self::enforceRecordBranch($staff, (int) $assessment->branch_id, 'This assessment belongs to another branch.');
    }

    public static function enforceEnrollmentBranch(User $staff, Enrollment $enrollment): void
    {
        self::enforceRecordBranch($staff, (int) $enrollment->branch_id, 'This enrollment belongs to another branch.');
    }

    public static function enforcePaymentBranch(User $staff, Payment $payment): void
    {
        if ($staff->isSuperAdmin() || $staff->isFinance()) {
            return;
        }

        if ($staff->isAdmin() && $staff->branch_id) {
            $payment->loadMissing(['enrollment', 'child']);
            $branchId = (int) ($payment->enrollment?->branch_id ?? $payment->child?->branch_id ?? 0);
            abort_if(
                $branchId !== (int) $staff->branch_id,
                403,
                'This payment belongs to another branch.'
            );
        }
    }

    /** Branch admins see only payments for enrollments in their branch; super admin & finance see all. */
    public static function applyPaymentBranchScope(Builder $query, User $staff): Builder
    {
        if ($staff->isSuperAdmin() || $staff->isFinance()) {
            return $query;
        }

        if ($staff->isAdmin() && $staff->branch_id) {
            $branchId = (int) $staff->branch_id;

            return $query->where(function (Builder $q) use ($branchId): void {
                $q->whereHas('enrollment', fn (Builder $e) => $e->where('branch_id', $branchId))
                    ->orWhere(function (Builder $q2) use ($branchId): void {
                        $q2->whereNull('enrollment_id')
                            ->whereHas('child', fn (Builder $c) => $c->where('branch_id', $branchId));
                    });
            });
        }

        if ($staff->isAdmin()) {
            return $query->whereRaw('0 = 1');
        }

        return $query;
    }

    public static function pendingPaymentVerificationCount(User $staff): int
    {
        return (int) self::applyPaymentBranchScope(
            Payment::query()->where('status', 'pending_verification'),
            $staff,
        )->count();
    }

    public static function paymentFiltersForStaff(User $staff, array $filters = []): array
    {
        if ($locked = self::lockedBranchId($staff)) {
            $filters['branch_id'] = $locked;
        }

        return $filters;
    }

    /** Branch admins only see inbox items tied to their branch; super admin, finance, child, therapist see all own rows. */
    public static function applyNotificationInboxScope(Builder $query, User $staff): Builder
    {
        if ($staff->isSuperAdmin() || $staff->isFinance() || $staff->isApprovalDiscount() || $staff->isChild() || $staff->isTherapist()) {
            return $query;
        }

        if (! $staff->isAdmin() || ! $staff->branch_id) {
            return $query->whereRaw('0 = 1');
        }

        $branchId = (int) $staff->branch_id;

        return $query->where(function (Builder $outer) use ($branchId): void {
            $outer->whereNull('record_id')
                ->orWhere('module', 'users')
                ->orWhere(function (Builder $w) use ($branchId): void {
                    $w->where('module', 'children')
                        ->whereIn('record_id', User::query()->where('branch_id', $branchId)->select('id'));
                })
                ->orWhere(function (Builder $w) use ($branchId): void {
                    $w->where('module', 'enrollments')
                        ->whereIn('record_id', Enrollment::query()->where('branch_id', $branchId)->select('id'));
                })
                ->orWhere(function (Builder $w) use ($branchId): void {
                    $w->where('module', 'payments')
                        ->whereIn('record_id', Payment::query()
                            ->where(function (Builder $p) use ($branchId): void {
                                $p->whereHas('enrollment', fn (Builder $e) => $e->where('branch_id', $branchId))
                                    ->orWhere(function (Builder $p2) use ($branchId): void {
                                        $p2->whereNull('enrollment_id')
                                            ->whereHas('child', fn (Builder $c) => $c->where('branch_id', $branchId));
                                    });
                            })
                            ->select('id'));
                })
                ->orWhere(function (Builder $w) use ($branchId): void {
                    $w->where('module', 'assessments')
                        ->whereIn('record_id', Assessment::query()->where('branch_id', $branchId)->select('id'));
                })
                ->orWhere(function (Builder $w) use ($branchId): void {
                    $w->where('module', 'sessions')
                        ->whereIn('record_id', EnrollmentSchedule::query()
                            ->whereHas('enrollment', fn (Builder $e) => $e->where('branch_id', $branchId))
                            ->select('id'));
                });
        });
    }

    public static function notificationVisibleToStaff(UserNotification $notification, User $staff): bool
    {
        if ($staff->isSuperAdmin() || $staff->isFinance() || $staff->isApprovalDiscount() || $staff->isChild() || $staff->isTherapist()) {
            return true;
        }

        if (! $staff->isAdmin() || ! $staff->branch_id) {
            return false;
        }

        $scoped = UserNotification::query()
            ->whereKey($notification->id)
            ->where('user_id', $staff->id);

        self::applyNotificationInboxScope($scoped, $staff);

        return $scoped->exists();
    }

    private static function enforceRecordBranch(User $staff, int $recordBranchId, string $message): void
    {
        if ($staff->isSuperAdmin()) {
            return;
        }

        if ($staff->isAdmin() && $staff->branch_id) {
            abort_if((int) $recordBranchId !== (int) $staff->branch_id, 403, $message);
        }
    }
}
