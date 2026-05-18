<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ChildApprovedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly User $child) {}

    public function build(): self
    {
        $centreName = config('app.name', 'Faizan Rehabilitation Centre');

        return $this->subject('Your Faizan Rehab Account Has Been Approved')
            ->view('emails.child-approved')
            ->with([
                'childName'  => $this->child->full_name,
                'loginUrl'   => url(route('login')),
                'centreName' => $centreName,
            ]);
    }
}
