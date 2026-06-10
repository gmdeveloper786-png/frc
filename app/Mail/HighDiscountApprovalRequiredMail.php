<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Enrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class HighDiscountApprovalRequiredMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly Enrollment $enrollment) {}

    public function build(): self
    {
        $this->enrollment->loadMissing(['child', 'branch', 'service', 'therapist', 'createdBy']);
        $child = $this->enrollment->child;
        $centreName = config('app.name', 'Faizan Rehabilitation Centre');
        $childLabel = $child?->full_name ?? ('Enrollment #' . $this->enrollment->id);

        $mail = $this->subject('High Discount Approval Required — ' . $childLabel)
            ->view('emails.high-discount-approval-required')
            ->with([
                'enrollment' => $this->enrollment,
                'child'      => $child,
                'centreName' => $centreName,
                'queueUrl'   => url(route('enrollments.high-discount')),
            ]);

        $discountFile = $this->enrollment->discount_file;
        if (filled($discountFile) && Storage::disk('private')->exists($discountFile)) {
            $extension = pathinfo($discountFile, PATHINFO_EXTENSION) ?: 'file';
            $mail->attach(Storage::disk('private')->path($discountFile), [
                'as'   => 'discount-support-' . $this->enrollment->id . '.' . $extension,
                'mime' => Storage::disk('private')->mimeType($discountFile) ?: 'application/octet-stream',
            ]);
        }

        return $mail;
    }
}
