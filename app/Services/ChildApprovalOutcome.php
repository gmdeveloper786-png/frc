<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

/**
 * Result of {@see ChildApprovalService::approve()} including optional approval email outcome.
 */
final class ChildApprovalOutcome
{
    public function __construct(
        public readonly User $child,
        public readonly bool $emailAttempted,
        public readonly bool $emailSent,
    ) {}

    public function successMessage(): string
    {
        if ($this->emailAttempted && $this->emailSent) {
            return 'Child approved successfully. Approval email has been sent.';
        }
        if ($this->emailAttempted && ! $this->emailSent) {
            return 'Child approved successfully, but email could not be sent. Please check mail settings.';
        }

        return "{$this->child->full_name} has been approved successfully.";
    }
}
