<?php

namespace App\Repositories\Interfaces;

use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PaymentRepositoryInterface
{
    public function findById(int $id): ?Payment;
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function getForChild(int $childId): Collection;
    public function paginateForChild(int $childId, int $perPage = 15): LengthAwarePaginator;
    public function getForEnrollment(int $enrollmentId): Collection;
    public function getPendingVerification(): Collection;
    public function create(array $data): Payment;
    public function update(Payment $payment, array $data): Payment;
    public function generateReceiptNumber(): string;
}
