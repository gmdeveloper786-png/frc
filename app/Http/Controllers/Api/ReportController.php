<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FinanceReportFilterRequest;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $service) {}

    public function finance(FinanceReportFilterRequest $request): JsonResponse
    {
        abort_if(! $request->user()->hasPermission('view_finance_reports'), 403);

        $data = $this->service->getFinanceSummary($request->validated());

        return response()->json(['data' => $data]);
    }

    public function payments(FinanceReportFilterRequest $request): JsonResponse
    {
        abort_if(! $request->user()->hasPermission('view_finance_reports'), 403);

        $filters = $request->validated();
        $perPage = (int) ($filters['per_page'] ?? 15);
        $data = $this->service->getStudentFeeRecords($filters, $perPage);

        return response()->json(['data' => $data]);
    }
}
