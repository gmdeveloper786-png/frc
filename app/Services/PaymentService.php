<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use App\Support\Money;
use App\Support\StaffBranchScope;
use App\Repositories\Interfaces\EnrollmentRepositoryInterface;
use App\Repositories\Interfaces\PaymentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorConcrete;

class PaymentService
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly EnrollmentRepositoryInterface $enrollmentRepository,
        private readonly NotificationService $notificationService,
        private readonly SecureFileStorageService $secureFiles,
    ) {}

    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        /** @var LengthAwarePaginatorConcrete $paginator */
        $paginator = $this->paymentRepository->getAll($filters, $perPage);
        return $paginator->withQueryString();
    }

    public function findById(int $id): Payment
    {
        return $this->paymentRepository->findById($id) ?? abort(404, 'Payment not found.');
    }

    /**
     * @return Collection<int, Enrollment>
     */
    public function getEnrollmentsForManualPaymentPicker(User $staff, int $limit = 500): Collection
    {
        return $this->enrollmentRepository->getEligibleForManualPayment(
            $limit,
            StaffBranchScope::lockedBranchId($staff),
        );
    }

    /** All payments visible on child's portal (every verification status). */
    public function getPaymentsForChild(int $childId): Collection
    {
        return $this->paymentRepository->getForChild($childId);
    }

    /** Paginated list for child portal payment history page. */
    public function paginatePaymentsForChild(int $childId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        /** @var LengthAwarePaginatorConcrete $paginator */
        $paginator = $this->paymentRepository->paginateForChild($childId, $filters, $perPage);
        return $paginator->withQueryString();
    }

    /**
     * Enrollments that appear in this child's payment history (for filter dropdown).
     *
     * @return Collection<int, Enrollment>
     */
    public function getEnrollmentFilterOptionsForChild(int $childId): Collection
    {
        $enrollmentIds = Payment::query()
            ->where('child_id', $childId)
            ->distinct()
            ->pluck('enrollment_id');

        if ($enrollmentIds->isEmpty()) {
            return new Collection;
        }

        return Enrollment::query()
            ->with('service:id,name')
            ->where('child_id', $childId)
            ->whereIn('id', $enrollmentIds)
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Child uploads a payment slip (bank/easypaisa/jazzcash/card/other).
     */
    public function childUploadSlip(array $data, User $child, $slipFile): Payment
    {
        $enrollment = $this->enrollmentRepository->findById($data['enrollment_id']);

        if (! $enrollment || $enrollment->child_id !== $child->id) {
            throw ValidationException::withMessages(['enrollment_id' => ['Invalid enrollment.']]);
        }

        if (! $enrollment->isVisibleToChild()) {
            throw ValidationException::withMessages(['enrollment_id' => ['Enrollment is not active.']]);
        }

        if ($data['payment_method'] === 'cash') {
            throw ValidationException::withMessages(['payment_method' => ['Cash payment cannot be submitted by child.']]);
        }

        $amount = (float) (int) Money::round($data['amount']);
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => ['Amount must be greater than 0.']]);
        }

        $outstanding = $enrollment->outstandingForSlipUpload();
        if ($outstanding <= 0) {
            $pending = $enrollment->sumPendingVerificationAmount();
            $message = $pending > 0
                ? 'A payment slip of '.frc_pkr($pending).' is already pending verification for this programme.'
                : 'Your fee is fully paid. No payment slip is required.';

            throw ValidationException::withMessages(['enrollment_id' => [$message]]);
        }

        if ($amount > $outstanding) {
            throw ValidationException::withMessages(['amount' => ['Amount cannot exceed remaining fee.']]);
        }

        $slipPath = $this->secureFiles->store($slipFile, 'payments/slips');

        $payment = $this->paymentRepository->create([
            'enrollment_id'        => $data['enrollment_id'],
            'child_id'             => $child->id,
            'amount'               => $amount,
            'payment_method'       => $data['payment_method'],
            'transaction_reference' => $data['transaction_reference'] ?? null,
            'payment_slip'         => $slipPath,
            'payment_date'         => $data['payment_date'],
            'notes'                => $data['notes'] ?? null,
            'submitted_by_role'    => 'child',
            'status'               => 'pending_verification',
        ]);

        $this->notificationService->notifyPaymentSlipUploaded($payment);

        return $payment;
    }

    /**
     * Staff records manual desk cash payment only.
     */
    public function addManualPayment(array $data, User $staff): Payment
    {
        $enrollment = $this->enrollmentRepository->findById($data['enrollment_id']);

        if (! $enrollment || ! in_array($enrollment->status, ['approved', 'active'], true)) {
            throw ValidationException::withMessages(['enrollment_id' => ['Enrollment is not active.']]);
        }

        StaffBranchScope::enforceEnrollmentBranch($staff, $enrollment);

        $amount = Money::round($data['amount']);
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => ['Amount must be greater than 0.']]);
        }

        $collectible = $enrollment->outstandingForSlipUpload();
        if ($collectible <= 0) {
            $pending = $enrollment->sumPendingVerificationAmount();
            $message = $pending > 0
                ? 'A payment of '.frc_pkr($pending).' is already pending verification. Verify or reject it before recording another manual payment.'
                : 'This enrollment is already fully paid.';

            throw ValidationException::withMessages(['enrollment_id' => [$message]]);
        }

        if ($amount > $collectible) {
            throw ValidationException::withMessages(['amount' => ['Amount cannot exceed the available balance of '.frc_pkr($collectible).' (pending verification amounts are reserved).']]);
        }

        $receiptNumber = $this->paymentRepository->generateReceiptNumber();

        $role = $staff->role?->name;
        $remainingBefore = $enrollment->outstandingAmount();

        $payment = $this->paymentRepository->create([
            'enrollment_id'     => $data['enrollment_id'],
            'child_id'          => $enrollment->child_id,
            'received_by'       => $staff->id,
            'verified_by'       => $staff->id,
            'amount'            => $amount,
            'payment_method'    => 'cash',
            'transaction_reference' => null,
            'receipt_number'    => $receiptNumber,
            'payment_date'      => $data['payment_date'],
            'notes'             => $data['notes'] ?? null,
            'submitted_by_role' => $role,
            'status'            => 'paid',
            'verified_at'       => now(),
        ]);

        $this->enrollmentRepository->recalculatePaidAmount($enrollment);
        $enrollment->refresh();

        $this->notificationService->notifyManualPaymentAdded($payment);
        if ($remainingBefore > 0 && $enrollment->fresh()->outstandingAmount() <= 0) {
            $this->notificationService->notifyFeeFullyPaid($enrollment);
        }

        return $payment->fresh(['enrollment', 'child', 'receivedBy']);
    }

    /**
     * Verify (approve) a child-submitted payment.
     */
    public function verify(Payment $payment, User $verifiedBy): Payment
    {
        if ($payment->status !== 'pending_verification') {
            throw ValidationException::withMessages(['payment' => ['Payment is not pending verification.']]);
        }

        // Official digital receipt (FRC-…) issued when admin verifies the uploaded fee slip.
        $updates = [
            'status'         => 'paid',
            'verified_by'    => $verifiedBy->id,
            'verified_at'    => now(),
            'received_by'    => $verifiedBy->id,
        ];
        if (! filled($payment->receipt_number)) {
            $updates['receipt_number'] = $this->paymentRepository->generateReceiptNumber();
        }

        $this->paymentRepository->update($payment, $updates);

        $payment->refresh();

        $enrollment = $this->resolveEnrollment($payment);

        $this->notificationService->notifyPaymentApproved($payment);

        if ($enrollment === null) {
            return $payment;
        }

        $remainingBefore = $enrollment->outstandingAmount();

        $this->enrollmentRepository->recalculatePaidAmount($enrollment);
        $enrollment->refresh();
        $remainingAfter = $enrollment->outstandingAmount();

        if ($remainingBefore > 0 && $remainingAfter <= 0) {
            $this->notificationService->notifyFeeFullyPaid($enrollment);
        }

        return $payment;
    }

    /**
     * Reject a child-submitted payment.
     */
    public function reject(Payment $payment, User $rejectedBy, string $reason): Payment
    {
        if ($payment->status !== 'pending_verification') {
            throw ValidationException::withMessages(['payment' => ['Payment is not pending verification.']]);
        }

        $this->paymentRepository->update($payment, [
            'status'           => 'rejected',
            'rejection_reason' => $reason,
            'verified_by'      => $rejectedBy->id,
            'verified_at'      => now(),
        ]);

        $payment->refresh();

        $this->notificationService->notifyPaymentRejected($payment);

        return $payment;
    }

    /**
     * Resolve the enrollment linked to a payment, including soft-deleted records.
     */
    private function resolveEnrollment(Payment $payment): ?Enrollment
    {
        if (! $payment->enrollment_id) {
            return null;
        }

        if ($payment->relationLoaded('enrollment') && $payment->enrollment !== null) {
            return $payment->enrollment;
        }

        return Enrollment::withTrashed()->find($payment->enrollment_id);
    }
}
