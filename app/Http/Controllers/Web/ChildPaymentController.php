<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\ChildPortalService;
use App\Services\PaymentService;
use Illuminate\View\View;

/** Authenticated child portal — payment history (`/my-payments`). Not staff {@see ChildController}. */
class ChildPaymentController extends Controller
{
    public function __construct(
        private readonly ChildPortalService $childPortalService,
        private readonly PaymentService $paymentService,
    ) {}

    public function index(): View
    {
        $child = auth()->user();

        return view('child.payments', [
            'payments'            => $this->paymentService->paginatePaymentsForChild($child->id, 15),
            'can_upload_fee_slip' => $this->childPortalService->getEnrollmentsForFeeSlipUpload($child)->isNotEmpty(),
        ]);
    }
}
