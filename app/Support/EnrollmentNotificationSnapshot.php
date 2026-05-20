<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Enrollment;
use App\Support\Money;

/**
 * Point-in-time enrollment fields used to detect meaningful notification-worthy changes.
 */
final class EnrollmentNotificationSnapshot
{
    public function __construct(
        public readonly int $id,
        public readonly ?int $childId,
        public readonly ?int $therapistId,
        public readonly ?int $branchId,
        public readonly ?int $serviceId,
        public readonly string $status,
        public readonly string $finalTotal,
        public readonly string $paymentStatus,
        public readonly string $schedulesFingerprint,
    ) {}

    public static function fromEnrollment(Enrollment $enrollment): self
    {
        $enrollment->loadMissing(['schedules']);

        return new self(
            id: (int) $enrollment->id,
            childId: $enrollment->child_id ? (int) $enrollment->child_id : null,
            therapistId: $enrollment->therapist_id ? (int) $enrollment->therapist_id : null,
            branchId: $enrollment->branch_id ? (int) $enrollment->branch_id : null,
            serviceId: $enrollment->service_id ? (int) $enrollment->service_id : null,
            status: (string) $enrollment->status,
            finalTotal: Money::format($enrollment->final_total),
            paymentStatus: (string) ($enrollment->payment_status ?? ''),
            schedulesFingerprint: self::schedulesFingerprint($enrollment),
        );
    }

    public static function schedulesFingerprint(Enrollment $enrollment): string
    {
        return $enrollment->schedules
            ->map(static fn ($s): string => strtolower(trim((string) $s->day)) . '|' . trim((string) $s->time_slot))
            ->sort()
            ->values()
            ->implode(';');
    }

    public function isChildNotifiable(): bool
    {
        return in_array($this->status, ['active', 'approved', 'completed'], true);
    }
}
