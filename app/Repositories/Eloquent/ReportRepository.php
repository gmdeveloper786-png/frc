<?php

namespace App\Repositories\Eloquent;

use App\Models\Enrollment;
use App\Models\Payment;
use App\Repositories\Interfaces\ReportRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorConcrete;

class ReportRepository implements ReportRepositoryInterface
{
    /** Non-cash methods counted under Online/Bank card. */
    private const ONLINE_PAYMENT_METHODS = ['bank_transfer', 'easypaisa', 'jazzcash', 'card', 'other'];

    /** Enrollments included in “total expected” (matches dashboard fee totals). */
    private const EXPECTED_ENROLLMENT_STATUSES = ['approved', 'active', 'completed'];

    /** Enrollments included in “pending / overdue” remaining balance. */
    private const PENDING_ENROLLMENT_STATUSES = ['approved', 'active'];

    /**
     * Summary: expected/pending from enrollments; collected amounts from payments (same filters).
     */
    public function getFinanceSummary(array $filters = []): array
    {
        $enrollmentBase = $this->enrollmentFinanceQuery($filters);

        $totalExpected = (float) (clone $enrollmentBase)
            ->whereIn('status', self::EXPECTED_ENROLLMENT_STATUSES)
            ->sum('final_total');

        $totalPending = (float) (clone $enrollmentBase)
            ->whereIn('status', self::PENDING_ENROLLMENT_STATUSES)
            ->sum('remaining_amount');

        $paymentBase = $this->financePaymentRecordsQuery($filters);

        $paidQuery = (clone $paymentBase)->where('status', 'paid');
        $parsed = $this->parseFinanceFilters($filters);
        $methodFilter = $parsed['payment_method'];

        $cashReceived = (float) (clone $paidQuery)->where('payment_method', 'cash')->sum('amount');
        $onlineReceived = (float) (clone $paidQuery)->whereIn('payment_method', self::ONLINE_PAYMENT_METHODS)->sum('amount');

        return [
            'total_expected'       => $totalExpected,
            'total_paid'           => (float) (clone $paidQuery)->sum('amount'),
            'total_pending'        => $totalPending,
            'cash_received'        => $cashReceived,
            'online_received'      => $onlineReceived,
            'pending_verification' => (float) (clone $paymentBase)->where('status', 'pending_verification')->sum('amount'),
            'payment_method_filter' => $methodFilter,
        ];
    }

    public function getFinancePaymentRecordsCollection(array $filters = []): Collection
    {
        return $this->financePaymentRecordsQuery($filters)
            ->with(['child', 'enrollment.branch'])
            ->latest()
            ->get();
    }

    public function countFinancePaymentRecords(array $filters = []): int
    {
        return $this->financePaymentRecordsQuery($filters)->count();
    }

    public function chunkFinancePaymentRecords(array $filters, int $chunkSize, callable $callback): void
    {
        $this->financePaymentRecordsQuery($filters)
            ->with([
                'child:id,full_name,status,gr_number',
                'enrollment.branch:id,name',
                'enrollment.service:id,name',
                'enrollment.therapist:id,full_name',
                'enrollment:id,child_id,branch_id,service_id,therapist_id,final_total,paid_amount,remaining_amount,payment_status,status',
            ])
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->chunk($chunkSize, function (Collection $chunk) use ($callback): void {
                $callback($chunk);
            });
    }

    public function getStudentFeeRecords(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        /** @var LengthAwarePaginatorConcrete $paginator */
        $paginator = $this->financePaymentRecordsQuery($filters)
            ->with([
                'child',
                'enrollment.branch',
                'enrollment.service',
                'enrollment.therapist',
                'enrollment.child',
            ])
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate($perPage);

        return $paginator->withQueryString();
    }

    /**
     * Enrollment scope for fee totals (expected / pending).
     *
     * Payment-specific filters (method, verification, payment dates) apply only to
     * {@see financePaymentRecordsQuery()} so summary cards do not mix full enrollment
     * fees with a single payment channel.
     */
    private function enrollmentFinanceQuery(array $filters): Builder
    {
        $parsed = $this->parseFinanceFilters($filters);

        return Enrollment::query()
            ->whereIn('status', self::EXPECTED_ENROLLMENT_STATUSES)
            ->when($parsed['branch_id'], fn ($q) => $q->where('branch_id', $parsed['branch_id']))
            ->when($parsed['therapist_id'], fn ($q) => $q->where('therapist_id', $parsed['therapist_id']))
            ->when($parsed['service_id'], fn ($q) => $q->where('service_id', $parsed['service_id']))
            ->when($parsed['enrollment_payment_status'], fn ($q) => $q->where('payment_status', $parsed['enrollment_payment_status']))
            ->when($parsed['child_search'] !== '', function ($q) use ($parsed): void {
                $like = frc_like_pattern($parsed['child_search']);
                $q->whereHas('child', function ($c) use ($like): void {
                    $c->where('full_name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('gr_number', 'like', $like);
                });
            })
            ->when($parsed['gr_number'] !== '', fn ($q) => $q->whereHas(
                'child',
                fn ($c) => $c->where('gr_number', 'like', frc_like_pattern($parsed['gr_number'])),
            ));
    }

    private function financePaymentRecordsQuery(array $filters): Builder
    {
        $parsed = $this->parseFinanceFilters($filters);

        return Payment::query()
            ->whereHas('enrollment', fn ($e) => $e->whereIn('status', self::EXPECTED_ENROLLMENT_STATUSES))
            ->when($parsed['branch_id'], fn ($q) => $q->whereHas('enrollment', fn ($e) => $e->where('branch_id', $parsed['branch_id'])))
            ->when($parsed['therapist_id'], fn ($q) => $q->whereHas('enrollment', fn ($e) => $e->where('therapist_id', $parsed['therapist_id'])))
            ->when($parsed['service_id'], fn ($q) => $q->whereHas('enrollment', fn ($e) => $e->where('service_id', $parsed['service_id'])))
            ->when($parsed['payment_method'], fn ($q) => $q->where('payment_method', $parsed['payment_method']))
            ->when($parsed['date_from'], fn ($q) => $q->whereDate('payment_date', '>=', $parsed['date_from']))
            ->when($parsed['date_to'], fn ($q) => $q->whereDate('payment_date', '<=', $parsed['date_to']))
            ->when($parsed['enrollment_payment_status'], fn ($q) => $q->whereHas(
                'enrollment',
                fn ($e) => $e->where('payment_status', $parsed['enrollment_payment_status']),
            ))
            ->when($parsed['verification_status'], fn ($q) => $q->where('status', $parsed['verification_status']))
            ->when($parsed['child_search'] !== '', function ($q) use ($parsed): void {
                $like = frc_like_pattern($parsed['child_search']);
                $q->whereHas('child', function ($c) use ($like): void {
                    $c->where('full_name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('gr_number', 'like', $like);
                });
            })
            ->when($parsed['gr_number'] !== '', function ($q) use ($parsed): void {
                $like = frc_like_pattern($parsed['gr_number']);
                $q->where(function ($inner) use ($like): void {
                    $inner->whereHas('child', fn ($c) => $c->where('gr_number', 'like', $like))
                        ->orWhereHas('enrollment.child', fn ($c) => $c->where('gr_number', 'like', $like));
                });
            });
    }

    /**
     * @return array{
     *     branch_id: ?int,
     *     therapist_id: ?int,
     *     service_id: ?int,
     *     payment_method: ?string,
     *     date_from: ?string,
     *     date_to: ?string,
     *     enrollment_payment_status: ?string,
     *     verification_status: ?string,
     *     child_search: string,
     *     gr_number: string,
     * }
     */
    private function parseFinanceFilters(array $filters): array
    {
        $paymentMethods = ['cash', 'bank_transfer', 'easypaisa', 'jazzcash', 'card', 'other'];
        $paymentStatuses = ['unpaid', 'partial_paid', 'fully_paid', 'overdue'];
        $verificationStatuses = ['pending_verification', 'paid', 'rejected', 'cancelled', 'refunded'];

        $paymentMethod = filled($filters['payment_method'] ?? null) ? (string) $filters['payment_method'] : null;
        $enrollmentPaymentStatus = filled($filters['enrollment_payment_status'] ?? null)
            ? (string) $filters['enrollment_payment_status'] : null;
        $verificationStatus = filled($filters['verification_status'] ?? null)
            ? (string) $filters['verification_status'] : null;

        return [
            'branch_id'                 => filled($filters['branch_id'] ?? null) ? (int) $filters['branch_id'] : null,
            'therapist_id'              => filled($filters['therapist_id'] ?? null) ? (int) $filters['therapist_id'] : null,
            'service_id'                => filled($filters['service_id'] ?? null) ? (int) $filters['service_id'] : null,
            'payment_method'            => in_array($paymentMethod, $paymentMethods, true) ? $paymentMethod : null,
            'date_from'                 => filled($filters['date_from'] ?? null) ? (string) $filters['date_from'] : null,
            'date_to'                   => filled($filters['date_to'] ?? null) ? (string) $filters['date_to'] : null,
            'enrollment_payment_status' => in_array($enrollmentPaymentStatus, $paymentStatuses, true)
                ? $enrollmentPaymentStatus : null,
            'verification_status'       => in_array($verificationStatus, $verificationStatuses, true)
                ? $verificationStatus : null,
            'child_search'              => isset($filters['child_search']) ? trim((string) $filters['child_search']) : '',
            'gr_number'                 => isset($filters['gr_number']) ? trim((string) $filters['gr_number']) : '',
        ];
    }
}
