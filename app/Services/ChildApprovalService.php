<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\ChildApprovedMail;
use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class ChildApprovalService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly NotificationService $notificationService,
    ) {}

    public function approve(User $child, User $approvedBy): ChildApprovalOutcome
    {
        if (! $child->isChild()) {
            throw ValidationException::withMessages(['user' => ['User is not a child.']]);
        }

        if (in_array($child->status, ['approved', 'active'], true)) {
            throw ValidationException::withMessages([
                'status' => ['This child account is already approved.'],
            ]);
        }

        $previousStatus = $child->status;

        $this->userRepository->update($child, [
            'status'             => 'approved',
            'approved_by'        => $approvedBy->id,
            'approved_at'        => now(),
            'rejected_by'        => null,
            'rejected_at'        => null,
            'rejection_reason'   => null,
        ]);

        $child->refresh();

        $this->notificationService->notifyChildApproved($child);

        $shouldSendApprovalEmail = in_array($previousStatus, ['pending', 'rejected', 'inactive'], true);

        $emailAttempted = false;
        $emailSent = false;

        if ($shouldSendApprovalEmail) {
            if (! filled($child->email)) {
                Log::warning('Child approval email skipped: child has no email address.', [
                    'child_id' => $child->id,
                ]);
            } else {
                $emailAttempted = true;
                try {
                    Mail::to($child->email)->send(new ChildApprovedMail($child));
                    $emailSent = true;
                    $this->notificationService->notifyChildApprovalEmailSent($child);
                } catch (\Throwable $e) {
                    Log::error('Child approval email failed.', [
                        'child_id'  => $child->id,
                        'email'     => $child->email,
                        'exception' => $e->getMessage(),
                    ]);
                    report($e);
                    $this->notificationService->notifyChildApprovalEmailFailed($child, $e->getMessage());
                }
            }
        }

        return new ChildApprovalOutcome($child, $emailAttempted, $emailSent);
    }

    public function reject(User $child, User $rejectedBy, string $reason): User
    {
        if (! $child->isChild()) {
            throw ValidationException::withMessages(['user' => ['User is not a child.']]);
        }

        $this->userRepository->update($child, [
            'status'           => 'rejected',
            'rejected_by'      => $rejectedBy->id,
            'rejected_at'      => now(),
            'rejection_reason' => $reason,
            'approved_by'      => null,
            'approved_at'      => null,
        ]);

        $child->refresh();

        $this->notificationService->notifyChildRejected($child, $reason);

        return $child;
    }
}
