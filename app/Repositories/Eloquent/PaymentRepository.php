<?php

namespace App\Repositories\Eloquent;

use App\Models\Payment;
use App\Repositories\Interfaces\PaymentRepositoryInterface;
use App\Support\Money;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorConcrete;
use Illuminate\Support\Facades\DB;

class PaymentRepository implements PaymentRepositoryInterface
{
    public function findById(int $id): ?Payment
    {
        return Payment::with([
            'enrollment' => fn ($q) => $q->withTrashed()->with('child'),
            'child',
            'receivedBy',
            'verifiedBy',
        ])->find($id);
    }

    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        /** @var LengthAwarePaginatorConcrete $paginator */
        $paginator = Payment::with(['child', 'enrollment.paidPayments', 'receivedBy', 'verifiedBy'])
            ->when($this->verificationStatusFilter($filters), fn($q, $st) => $q->where('status', $st))
            ->when(!empty($filters['child_id']),       fn($q) => $q->where('child_id', $filters['child_id']))
            ->when(!empty($filters['enrollment_id']),  fn($q) => $q->where('enrollment_id', $filters['enrollment_id']))
            ->when(!empty($filters['enrollment_payment_status']), fn($q) => $q->whereHas(
                'enrollment',
                fn($e) => $e->where('payment_status', $filters['enrollment_payment_status'])
            ))
            ->when(!empty($filters['payment_method']), fn($q) => $q->where('payment_method', $filters['payment_method']))
            ->when(!empty($filters['date_from']),      fn($q) => $q->whereDate('payment_date', '>=', $filters['date_from']))
            ->when(!empty($filters['date_to']),        fn($q) => $q->whereDate('payment_date', '<=', $filters['date_to']))
            ->when(!empty($filters['branch_id']),      fn($q) => $q->whereHas('enrollment', fn($e) => $e->where('branch_id', $filters['branch_id'])))
            ->when(!empty($filters['search']),         fn($q) => $q->where(function ($q) use ($filters) {
                $like = frc_like_pattern((string) $filters['search']);
                $q->where('receipt_number', 'like', $like)
                    ->orWhereHas('child', fn ($c) => $c->where('full_name', 'like', $like));
            }))
            ->latest()
            ->paginate($perPage);

        return $paginator->withQueryString();
    }

    public function getForChild(int $childId): Collection
    {
        return Payment::with(['enrollment'])
            ->where('child_id', $childId)
            ->latest()
            ->get();
    }

    public function paginateForChild(int $childId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        /** @var LengthAwarePaginatorConcrete $paginator */
        $paginator = $this->childPaymentsQuery($childId, $filters)
            ->with(['enrollment.service'])
            ->latest()
            ->paginate($perPage);

        return $paginator->withQueryString();
    }

    public function getForEnrollment(int $enrollmentId): Collection
    {
        return Payment::where('enrollment_id', $enrollmentId)
            ->latest()
            ->get();
    }

    public function getPendingVerification(): Collection
    {
        return Payment::with(['child', 'enrollment'])
            ->pendingVerification()
            ->latest()
            ->get();
    }

    public function create(array $data): Payment
    {
        if (array_key_exists('amount', $data)) {
            $data['amount'] = Money::round($data['amount']);
        }

        return Payment::create($data);
    }

    public function update(Payment $payment, array $data): Payment
    {
        if (array_key_exists('amount', $data)) {
            $data['amount'] = Money::round($data['amount']);
        }

        $payment->update($data);
        return $payment->fresh(['child', 'enrollment', 'receivedBy', 'verifiedBy']);
    }

    public function generateReceiptNumber(): string
    {
        return DB::transaction(function () {
            // Next sequence must be max(FRC-###### suffix) + 1, not based on latest row id:
            // otherwise a newer row with a lower receipt (e.g. id 10 → FRC-000001) can yield
            // FRC-000002 while id 5 already holds FRC-000002 → unique constraint violation.
            $maxSeq = 0;
            $receiptNumbers = Payment::withTrashed()
                ->whereNotNull('receipt_number')
                ->where('receipt_number', 'like', 'FRC-%')
                ->lockForUpdate()
                ->pluck('receipt_number');

            foreach ($receiptNumbers as $rn) {
                if (preg_match('/^FRC-(\d+)$/', (string) $rn, $matches)) {
                    $maxSeq = max($maxSeq, (int) $matches[1]);
                }
            }

            return 'FRC-' . str_pad((string) ($maxSeq + 1), 6, '0', STR_PAD_LEFT);
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function childPaymentsQuery(int $childId, array $filters = [])
    {
        return Payment::query()
            ->where('child_id', $childId)
            ->when($this->verificationStatusFilter($filters), fn ($q, $st) => $q->where('status', $st))
            ->when(! empty($filters['enrollment_id']), fn ($q) => $q->where('enrollment_id', $filters['enrollment_id']))
            ->when(! empty($filters['payment_method']), fn ($q) => $q->where('payment_method', $filters['payment_method']))
            ->when(! empty($filters['date_from']), fn ($q) => $q->whereDate('payment_date', '>=', $filters['date_from']))
            ->when(! empty($filters['date_to']), fn ($q) => $q->whereDate('payment_date', '<=', $filters['date_to']))
            ->when(! empty($filters['search']), fn ($q) => $q->where(function ($q) use ($filters): void {
                $term = trim((string) $filters['search']);
                $like = frc_like_pattern($term);
                $q->where('receipt_number', 'like', $like);

                $enrollmentId = ltrim($term, '#');
                if (ctype_digit($enrollmentId)) {
                    $q->orWhere('enrollment_id', (int) $enrollmentId);
                }
            }));
    }

    /** Payment row verification status (`payments.status`). Prefer explicit key; fall back to legacy `status`. */
    private function verificationStatusFilter(array $filters): ?string
    {
        if (! empty($filters['verification_status'])) {
            return (string) $filters['verification_status'];
        }

        return ! empty($filters['status']) ? (string) $filters['status'] : null;
    }
}
