<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectPaymentRequest;
use App\Http\Requests\StoreChildPaymentSlipRequest;
use App\Http\Requests\StoreManualPaymentRequest;
use App\Http\Requests\VerifyPaymentRequest;
use App\Models\Branch;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\ChildPortalService;
use App\Services\PaymentService;
use App\Services\ReceiptService;
use Illuminate\Http\RedirectResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly ReceiptService $receiptService,
        private readonly UserRepositoryInterface $userRepository,
        private readonly ChildPortalService $childPortalService,
    ) {}

    public function index(Request $request): View
    {
        $payments = $this->paymentService->getAll($request->only([
            'status',
            'verification_status',
            'enrollment_payment_status',
            'child_id',
            'payment_method',
            'date_from',
            'date_to',
            'branch_id',
            'search',
        ]));
        $branches = Branch::published()->get();

        return view('payments.index', compact('payments', 'branches'));
    }

    public function pendingVerification(): View
    {
        $payments = $this->paymentService->getAll(['status' => 'pending_verification']);

        return view('payments.pending', compact('payments'));
    }

    public function createManual(Request $request): View
    {
        $enrollments = $this->paymentService->getEnrollmentsForManualPaymentPicker(500);

        $preselectId = $request->integer('enrollment_id');
        if ($preselectId > 0 && ! $enrollments->contains('id', $preselectId)) {
            $extra = Enrollment::query()
                ->select(['id', 'child_id', 'final_total', 'paid_amount', 'remaining_amount'])
                ->with(['child:id,full_name'])
                ->active()
                ->where('remaining_amount', '>', 0)
                ->find($preselectId);
            if ($extra) {
                $enrollments->prepend($extra);
            }
        }

        $enrollmentLookup = $enrollments->mapWithKeys(static function (Enrollment $e): array {
            return [
                $e->id => [
                    'id'               => $e->id,
                    'child_name'       => $e->child?->full_name ?? '—',
                    'final_total'      => (float) $e->getRawOriginal('final_total'),
                    'paid_amount'      => (float) $e->getRawOriginal('paid_amount'),
                    'remaining_amount' => (float) $e->getRawOriginal('remaining_amount'),
                ],
            ];
        });

        return view('payments.manual-create', compact('enrollments', 'enrollmentLookup'));
    }

    public function storeManual(StoreManualPaymentRequest $request): RedirectResponse
    {
        $payment = $this->paymentService->addManualPayment($request->validated(), $request->user());

        $receiptRoute = $request->user()->isFinance() ? 'finance.payments.receipt' : 'payments.receipt';
        $listRoute     = $request->user()->isFinance() ? 'finance.payments' : 'payments.index';

        if ($payment->hasPrintableReceipt()) {
            return redirect()->route($receiptRoute, $payment->id)->with('success', 'Cash payment recorded.');
        }

        return redirect()->route($listRoute)->with('success', 'Payment recorded.');
    }

    public function childSlipCreate(Request $request): View
    {
        $child = $request->user();
        $eligible = $this->childPortalService->getEnrollmentsForFeeSlipUpload($child);
        $requestedId = $request->query('enrollment_id');

        $enrollment = null;
        if ($requestedId !== null && $requestedId !== '') {
            $enrollment = $eligible->firstWhere('id', (int) $requestedId);
        }
        if (! $enrollment) {
            $enrollment = $eligible->first();
        }

        $canUpload = $enrollment instanceof Enrollment;
        $hasVisibleEnrollments = $this->childPortalService->childHasVisibleEnrollment($child);

        return view('payments.upload-slip', compact('enrollment', 'canUpload', 'eligible', 'hasVisibleEnrollments'));
    }

    public function childSlipStore(StoreChildPaymentSlipRequest $request): RedirectResponse
    {
        $payment = $this->paymentService->childUploadSlip(
            $request->validated(),
            $request->user(),
            $request->file('payment_slip'),
        );

        return redirect()->route('dashboard.child')->with(
            'success',
            'Payment slip submitted for verification ('.frc_pkr($payment->amount).').'
        );
    }

    public function verify(VerifyPaymentRequest $request, int $id): RedirectResponse
    {
        $payment = $this->paymentService->findById($id);
        $this->paymentService->verify($payment, $request->user());

        return redirect()->back()->with('success', 'Payment verified successfully.');
    }

    public function reject(RejectPaymentRequest $request, int $id): RedirectResponse
    {
        $payment = $this->paymentService->findById($id);
        $this->paymentService->reject($payment, $request->user(), $request->rejection_reason);

        return redirect()->back()->with('success', 'Payment rejected.');
    }

    public function show(int $id): View
    {
        $payment = $this->paymentService->findById($id);
        $this->authorizePaymentDetailAccess($payment);

        return view('payments.show', compact('payment'));
    }

    public function receipt(Request $request, int $id): View|\Illuminate\Http\Response
    {
        $payment = $this->paymentService->findById($id);
        $this->authorizeReceiptAccess($payment);
        abort_unless($payment->hasPrintableReceipt(), 404);

        $receipt = $this->receiptService->getReceiptData($payment);

        if ($request->boolean('pdf')) {
            $safeName = preg_replace('/[^A-Za-z0-9\-_]/', '_', (string) $payment->receipt_number) ?: 'receipt';
            $pdf = Pdf::loadView('payments.receipt-pdf', compact('payment', 'receipt'))
                ->setPaper('a4', 'portrait');

            return $request->boolean('inline')
                ? $pdf->stream($safeName . '.pdf')
                : $pdf->download($safeName . '.pdf');
        }

        return view('payments.receipt', compact('payment', 'receipt'));
    }

    /** Staff/finance or payment permissions; children only their own enrollment payments. */
    private function authorizePaymentDetailAccess(Payment $payment): void
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            abort(403);
        }

        if ($user->isChild()) {
            abort_unless((int) $payment->child_id === (int) $user->id, 403);

            return;
        }

        abort_unless(
            $user->hasPermission('manage_payments') || $user->hasPermission('verify_payments'),
            403,
        );
    }

    /** Children may view only their own receipts; staff with payment permissions may view any. */
    private function authorizeReceiptAccess(Payment $payment): void
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            abort(403);
        }

        if ($user->isChild()) {
            abort_unless((int) $payment->child_id === (int) $user->id, 403);

            return;
        }

        abort_unless($user->hasPermission('manage_payments'), 403);
    }
}
