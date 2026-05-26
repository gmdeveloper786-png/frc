<?php

namespace App\Services;

use App\Models\Payment;
use App\Repositories\Interfaces\ReportRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorConcrete;

class ReportService
{
    /** PDF / print exports above this row count should use CSV instead. */
    public const MAX_PDF_EXPORT_ROWS = 1000;

    public const EXPORT_CHUNK_SIZE = 250;

    public function __construct(
        private readonly ReportRepositoryInterface $repository,
    ) {}

    public function getFinanceSummary(array $filters = []): array
    {
        return $this->repository->getFinanceSummary($filters);
    }

    public function getStudentFeeRecords(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        /** @var LengthAwarePaginatorConcrete $paginator */
        $paginator = $this->repository->getStudentFeeRecords($filters, $perPage);
        return $paginator->withQueryString();
    }

    public function getFinancePaymentRecordsCollection(array $filters = []): Collection
    {
        return $this->repository->getFinancePaymentRecordsCollection($filters);
    }

    public function countFinancePaymentRecords(array $filters = []): int
    {
        return $this->repository->countFinancePaymentRecords($filters);
    }

    /**
     * @param  callable(\Illuminate\Support\Collection<int, \App\Models\Enrollment>): void  $callback
     */
    public function chunkFinancePaymentRecords(array $filters, callable $callback, int $chunkSize = self::EXPORT_CHUNK_SIZE): void
    {
        $this->repository->chunkFinancePaymentRecords($filters, $chunkSize, $callback);
    }

    /**
     * Plain string rows for PDF/print (avoids holding full Eloquent graphs in memory).
     *
     * @return list<array<string, string>>
     */
    public function financePaymentExportRows(array $filters): array
    {
        $rows = [];
        $this->chunkFinancePaymentRecords($filters, function (Collection $chunk) use (&$rows): void {
            foreach ($chunk as $payment) {
                /** @var Payment $payment */
                $rows[] = $this->mapPaymentToExportRow($payment);
            }
        });

        return $rows;
    }

    /**
     * @return array<string, string>
     */
    private function mapPaymentToExportRow(Payment $payment): array
    {
        $enrollment = $payment->enrollment;

        return [
            'receipt'            => $payment->hasPrintableReceipt() ? (string) $payment->receipt_number : '—',
            'enrollment_id'      => $enrollment ? '#' . $enrollment->id : '—',
            'child_name'         => (string) ($payment->child?->full_name ?? $enrollment?->child?->full_name ?? '—'),
            'child_gr_number'    => (string) ($payment->child?->gr_number ?? $enrollment?->child?->gr_number ?? '—'),
            'child_status'       => ($payment->child ?? $enrollment?->child)
                ? Str::title(str_replace('_', ' ', (string) ($payment->child ?? $enrollment->child)->status))
                : '—',
            'branch'             => (string) ($enrollment?->branch?->name ?? '—'),
            'enrollment_total'   => $enrollment ? frc_money($enrollment->final_total) : '—',
            'enrollment_paid'    => $enrollment ? frc_money($enrollment->paid_amount) : '—',
            'enrollment_remaining' => $enrollment ? frc_money($enrollment->remaining_amount) : '—',
            'enrollment_payment_status' => $enrollment
                ? Payment::labelForEnrollmentPaymentStatus($enrollment->payment_status)
                : '—',
            'verification_status' => Payment::labelForVerificationStatus($payment->status) ?: '—',
            'amount'             => frc_money($payment->amount),
            'payment_method'     => Payment::labelForPaymentMethod($payment->payment_method) ?: '—',
            'payment_date'       => $payment->payment_date?->format('d M Y') ?? '—',
        ];
    }
}
