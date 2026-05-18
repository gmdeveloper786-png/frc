<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Branch;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reportService) {}

    public function finance(Request $request): View
    {
        $filters  = $this->financeReportFilters($request);
        $summary  = $this->reportService->getFinanceSummary($filters);
        $perPage  = min(100, max(5, (int) $request->input('per_page', 15)));
        $records  = $this->reportService->getStudentFeeRecords($filters, $perPage);
        $branches = Branch::published()->get();

        return view('reports.finance', compact('summary', 'records', 'branches'));
    }

    public function financeExport(Request $request, string $format): StreamedResponse|\Illuminate\Http\Response|RedirectResponse
    {
        $format = strtolower($format);
        abort_unless(in_array($format, ['csv', 'pdf'], true), 404);

        $filters = $this->financeReportFilters($request);
        $summary = $this->reportService->getFinanceSummary($filters);
        $recordCount = $this->reportService->countFinancePaymentRecords($filters);

        if ($format === 'pdf' && $recordCount > ReportService::MAX_PDF_EXPORT_ROWS) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', sprintf(
                    'PDF export supports up to %d payment rows (%d match your filters). Please use CSV for the full export.',
                    ReportService::MAX_PDF_EXPORT_ROWS,
                    $recordCount,
                ));
        }

        $stamp = now()->format('Y-m-d_His');

        return match ($format) {
            'csv' => $this->financeCsvResponse($filters, $summary, $stamp, $recordCount),
            'pdf' => $this->financePdfResponse($filters, $summary, $stamp),
        };
    }

    public function financePrint(Request $request): View|RedirectResponse
    {
        $filters = $this->financeReportFilters($request);
        $recordCount = $this->reportService->countFinancePaymentRecords($filters);

        if ($recordCount > ReportService::MAX_PDF_EXPORT_ROWS) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', sprintf(
                    'Print view supports up to %d payment rows (%d match your filters). Narrow filters or export CSV.',
                    ReportService::MAX_PDF_EXPORT_ROWS,
                    $recordCount,
                ));
        }

        $summary = $this->reportService->getFinanceSummary($filters);
        $rows = $this->reportService->financePaymentExportRows($filters);

        return view('reports.finance-print', compact('rows', 'summary'));
    }

    /** @return array<string, mixed> */
    private function financeReportFilters(Request $request): array
    {
        return [
            'branch_id'                   => $request->input('branch_id'),
            'payment_method'              => $request->input('payment_method'),
            'date_from'                   => $request->input('date_from'),
            'date_to'                     => $request->input('date_to'),
            'enrollment_payment_status'   => $request->input('enrollment_payment_status'),
            'verification_status'         => $request->input('verification_status'),
            'child_search'                => $request->input('child_search'),
            'receipt_number'              => $request->input('receipt_number'),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function financeCsvResponse(array $filters, array $summary, string $stamp, int $recordCount): StreamedResponse
    {
        $filename = 'finance-report_' . $stamp . '.csv';

        return response()->streamDownload(function () use ($filters, $summary, $stamp, $recordCount): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            // Helps Excel detect comma delimiter when opening the file directly.
            fputcsv($out, ['sep=,']);

            fputcsv($out, ['Faizan Rehabilitation Centre — Finance Report']);
            fputcsv($out, ['Generated', now()->format('d M Y, h:i A')]);
            fputcsv($out, ['Export file', 'finance-report_' . $stamp . '.csv']);
            fputcsv($out, []);

            fputcsv($out, ['SUMMARY']);
            fputcsv($out, ['Metric', 'Amount (PKR)']);
            $summaryRows = [
                'Total Expected'       => (float) ($summary['total_expected'] ?? 0),
                'Total Paid'           => (float) ($summary['total_paid'] ?? 0),
                'Pending / Overdue'    => (float) ($summary['total_pending'] ?? 0),
                'Cash Received'        => (float) ($summary['cash_received'] ?? 0),
                'Online / Bank'        => (float) ($summary['online_received'] ?? 0),
                'Pending Verification' => (float) ($summary['pending_verification'] ?? 0),
            ];
            foreach ($summaryRows as $label => $amount) {
                fputcsv($out, [$label, $this->financeCsvAmount($amount)]);
            }

            fputcsv($out, []);
            fputcsv($out, ['PAYMENT RECORDS (' . $recordCount . ')']);
            fputcsv($out, [
                '#',
                'Receipt Number',
                'Child Name',
                'Child Status',
                'Branch',
                'Enrollment Total (PKR)',
                'Enrollment Paid (PKR)',
                'Enrollment Remaining (PKR)',
                'Enrollment Payment Status',
                'Payment Amount (PKR)',
                'Verification Status',
                'Payment Method',
                'Payment Date',
                'Enrollment ID',
            ]);

            $rowNumber = 0;
            $this->reportService->chunkFinancePaymentRecords($filters, function (Collection $chunk) use ($out, &$rowNumber): void {
                foreach ($chunk as $p) {
                    /** @var Payment $p */
                    $enr = $p->enrollment;
                    $rowNumber++;
                    fputcsv($out, [
                        $rowNumber,
                        $p->hasPrintableReceipt() ? $p->receipt_number : '',
                        $p->child?->full_name ?? '',
                        $p->child ? Str::title(str_replace('_', ' ', (string) $p->child->status)) : '',
                        $enr?->branch?->name ?? '',
                        $enr ? $this->financeCsvAmount((float) $enr->final_total) : '',
                        $enr ? $this->financeCsvAmount((float) $enr->paid_amount) : '',
                        $enr ? $this->financeCsvAmount((float) $enr->remaining_amount) : '',
                        $enr ? Payment::labelForEnrollmentPaymentStatus($enr->payment_status) : '',
                        $this->financeCsvAmount((float) $p->amount),
                        Payment::labelForVerificationStatus($p->status) ?: '',
                        Payment::labelForPaymentMethod($p->payment_method) ?: '',
                        $p->payment_date?->format('d M Y') ?? '',
                        $p->enrollment_id ? (string) $p->enrollment_id : '',
                    ]);
                }
            });
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function financeCsvAmount(float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function financePdfResponse(array $filters, array $summary, string $stamp): \Illuminate\Http\Response
    {
        @ini_set('memory_limit', '512M');

        $rows = $this->reportService->financePaymentExportRows($filters);

        $pdf = Pdf::loadView('reports.finance-pdf', compact('rows', 'summary'))
            ->setPaper('a4', 'landscape')
            ->setOption('isRemoteEnabled', false)
            ->setOption('isHtml5ParserEnabled', true);

        return $pdf->download('finance-report_' . $stamp . '.pdf');
    }
}
