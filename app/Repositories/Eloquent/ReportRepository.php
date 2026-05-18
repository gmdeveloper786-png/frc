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

    /**
     * Summary metrics use the same filtered payment query as the finance report table.
     * Total fee / remaining use each distinct enrollment once (multiple payments for one enrollment do not multiply fee totals).
     */
    public function getFinanceSummary(array $filters = []): array
    {
        $base = $this->financePaymentRecordsQuery($filters);

        $enrollmentIds = (clone $base)
            ->whereNotNull('enrollment_id')
            ->distinct()
            ->pluck('enrollment_id');

        $totalExpected = $enrollmentIds->isEmpty()
            ? 0.0
            : (float) Enrollment::query()->whereIn('id', $enrollmentIds)->sum('final_total');

        $totalPending = $enrollmentIds->isEmpty()
            ? 0.0
            : (float) Enrollment::query()->whereIn('id', $enrollmentIds)->sum('remaining_amount');

        $paidQuery = (clone $base)->where('status', 'paid');

        $totalPaid = (float) (clone $paidQuery)->sum('amount');
        $cashReceived = (float) (clone $paidQuery)->where('payment_method', 'cash')->sum('amount');
        $onlineReceived = (float) (clone $paidQuery)->whereIn('payment_method', self::ONLINE_PAYMENT_METHODS)->sum('amount');

        $pendingVerification = (float) (clone $base)->where('status', 'pending_verification')->sum('amount');

        return [
            'total_expected'       => $totalExpected,
            'total_paid'           => $totalPaid,
            'total_pending'        => $totalPending,
            'cash_received'        => $cashReceived,
            'online_received'      => $onlineReceived,
            'pending_verification' => $pendingVerification,
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
            ->select([
                'payments.id',
                'payments.child_id',
                'payments.enrollment_id',
                'payments.receipt_number',
                'payments.amount',
                'payments.status',
                'payments.payment_method',
                'payments.payment_date',
            ])
            ->with([
                'child:id,full_name,status',
                'enrollment:id,branch_id,final_total,paid_amount,remaining_amount,payment_status',
                'enrollment.branch:id,name',
            ])
            ->latest('payments.id')
            ->chunk($chunkSize, $callback);
    }

    public function getStudentFeeRecords(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        /** @var LengthAwarePaginatorConcrete $paginator */
        $paginator = $this->financePaymentRecordsQuery($filters)
            ->with(['child', 'enrollment.branch'])
            ->latest()
            ->paginate($perPage);

        return $paginator->withQueryString();
    }

    private function financePaymentRecordsQuery(array $filters): Builder
    {
        $branchId = filled($filters['branch_id'] ?? null) ? (int) $filters['branch_id'] : null;
        $paymentMethod = filled($filters['payment_method'] ?? null) ? (string) $filters['payment_method'] : null;
        $dateFrom = filled($filters['date_from'] ?? null) ? (string) $filters['date_from'] : null;
        $dateTo = filled($filters['date_to'] ?? null) ? (string) $filters['date_to'] : null;
        $enrollmentPaymentStatus = filled($filters['enrollment_payment_status'] ?? null)
            ? (string) $filters['enrollment_payment_status'] : null;
        $verificationStatus = filled($filters['verification_status'] ?? null)
            ? (string) $filters['verification_status'] : null;
        $childSearch = isset($filters['child_search']) ? trim((string) $filters['child_search']) : '';
        $receiptNumber = isset($filters['receipt_number']) ? trim((string) $filters['receipt_number']) : '';

        return Payment::query()
            ->when($branchId, fn($q) => $q->whereHas('enrollment', fn($e) => $e->where('branch_id', $branchId)))
            ->when($paymentMethod, fn($q) => $q->where('payment_method', $paymentMethod))
            ->when($dateFrom, fn($q) => $q->whereDate('payment_date', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->whereDate('payment_date', '<=', $dateTo))
            ->when($enrollmentPaymentStatus, fn($q) => $q->whereHas(
                'enrollment',
                fn($e) => $e->where('payment_status', $enrollmentPaymentStatus)
            ))
            ->when($verificationStatus, fn($q) => $q->where('status', $verificationStatus))
            ->when($childSearch !== '', fn($q) => $q->whereHas(
                'child',
                fn($c) => $c->where('full_name', 'like', '%' . $childSearch . '%')
            ))
            ->when($receiptNumber !== '', fn($q) => $q->where('receipt_number', 'like', '%' . $receiptNumber . '%'));
    }
}
