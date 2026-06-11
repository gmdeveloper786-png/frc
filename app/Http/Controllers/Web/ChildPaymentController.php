<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChildPaymentListFilterRequest;
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

    public function index(ChildPaymentListFilterRequest $request): View
    {
        $child = auth()->user();
        $filters = $request->validated();

        return view('child.payments', [
            'payments'            => $this->paymentService->paginatePaymentsForChild($child->id, $filters, 15),
            'enrollmentOptions'   => $this->paymentService->getEnrollmentFilterOptionsForChild($child->id),
            'can_upload_fee_slip' => $this->childPortalService->getEnrollmentsForFeeSlipUpload($child)->isNotEmpty(),
            'filterActive'        => $request->hasAny([
                'search',
                'verification_status',
                'enrollment_id',
                'payment_method',
                'date_from',
                'date_to',
            ]),
        ]);
    }
}
