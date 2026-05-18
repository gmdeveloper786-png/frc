<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $service) {}

    public function finance(Request $request): JsonResponse
    {
        abort_if(! $request->user()->hasPermission('view_finance_reports'), 403);

        $data = $this->service->getFinanceSummary($request->only([
            'branch_id',
            'date_from',
            'date_to',
            'payment_method',
            'enrollment_payment_status',
            'verification_status',
            'child_search',
            'receipt_number',
        ]));

        return response()->json(['data' => $data]);
    }

    public function payments(Request $request): JsonResponse
    {
        abort_if(! $request->user()->hasPermission('view_finance_reports'), 403);

        $data = $this->service->getStudentFeeRecords($request->all());

        return response()->json(['data' => $data]);
    }
}
