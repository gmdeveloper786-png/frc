<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectPaymentRequest;
use App\Http\Requests\StoreChildPaymentSlipRequest;
use App\Http\Requests\StoreManualPaymentRequest;
use App\Http\Requests\VerifyPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Repositories\Interfaces\PaymentRepositoryInterface;
use App\Services\PaymentService;
use App\Services\ReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly ReceiptService $receiptService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        abort_if(! $request->user()->hasPermission('manage_payments'), 403);

        $payments = $this->paymentService->getAll($request->only([
            'status',
            'verification_status',
            'enrollment_payment_status',
            'child_id',
            'enrollment_id',
            'payment_method',
            'date_from',
            'date_to',
            'branch_id',
        ]));

        return response()->json(['data' => PaymentResource::collection($payments)]);
    }

    public function childSlipUpload(StoreChildPaymentSlipRequest $request): JsonResponse
    {
        $payment = $this->paymentService->childUploadSlip(
            $request->validated(),
            $request->user(),
            $request->file('payment_slip'),
        );

        return response()->json(['message' => 'Payment slip submitted for verification.', 'data' => new PaymentResource($payment)], 201);
    }

    public function manualPayment(StoreManualPaymentRequest $request): JsonResponse
    {
        $payment = $this->paymentService->addManualPayment($request->validated(), $request->user());

        return response()->json(['message' => 'Manual payment added successfully.', 'data' => new PaymentResource($payment)], 201);
    }

    public function show(int $id, Request $request): JsonResponse
    {
        $payment = $this->paymentService->findById($id);
        $user    = $request->user();

        if ($user->isChild() && $payment->child_id !== $user->id) {
            abort(403);
        }

        return response()->json(['data' => new PaymentResource($payment)]);
    }

    public function verify(VerifyPaymentRequest $request, int $id): JsonResponse
    {
        $payment = $this->paymentService->findById($id);
        $updated = $this->paymentService->verify($payment, $request->user());

        return response()->json(['message' => 'Payment verified.', 'data' => new PaymentResource($updated)]);
    }

    public function reject(RejectPaymentRequest $request, int $id): JsonResponse
    {
        $payment = $this->paymentService->findById($id);
        $updated = $this->paymentService->reject($payment, $request->user(), $request->rejection_reason);

        return response()->json(['message' => 'Payment rejected.', 'data' => new PaymentResource($updated)]);
    }

    public function childPayments(int $child, Request $request): JsonResponse
    {
        abort_if(! $request->user()->hasPermission('manage_payments'), 403);

        $payments = $this->paymentRepository->getForChild($child);

        return response()->json(['data' => PaymentResource::collection($payments)]);
    }

    public function studentFees(Request $request): JsonResponse
    {
        abort_if(! $request->user()->hasPermission('view_finance_reports'), 403);

        $records = app(\App\Services\ReportService::class)->getStudentFeeRecords($request->all());

        return response()->json(['data' => $records]);
    }

    public function receipt(int $id, Request $request): JsonResponse
    {
        $payment = $this->paymentService->findById($id);
        $user    = $request->user();

        if ($user->isChild() && $payment->child_id !== $user->id) {
            abort(403);
        }

        abort_unless($payment->hasPrintableReceipt(), 404);

        $receiptData = $this->receiptService->getReceiptData($payment);

        return response()->json(['data' => $receiptData]);
    }
}
