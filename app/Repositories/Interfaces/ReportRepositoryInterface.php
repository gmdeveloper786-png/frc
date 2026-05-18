<?php

namespace App\Repositories\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ReportRepositoryInterface
{
    public function getFinanceSummary(array $filters = []): array;

    /** Payment rows matching finance report filters (no pagination). */
    public function getFinancePaymentRecordsCollection(array $filters = []): Collection;

    public function countFinancePaymentRecords(array $filters = []): int;

    /**
     * @param  callable(\Illuminate\Support\Collection<int, \App\Models\Payment>): void  $callback
     */
    public function chunkFinancePaymentRecords(array $filters, int $chunkSize, callable $callback): void;

    public function getStudentFeeRecords(array $filters = [], int $perPage = 15): LengthAwarePaginator;
}
