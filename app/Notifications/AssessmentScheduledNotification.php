<?php

namespace App\Notifications;

use App\Models\Assessment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AssessmentScheduledNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Assessment $assessment) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->assessment->loadMissing('therapist');

        $tn = $this->assessment->therapist?->full_name;

        return [
            'type'          => 'assessment_scheduled',
            'title'         => 'Assessment Scheduled',
            'message'       => $tn
                ? "Your assessment has been scheduled on {$this->assessment->date->format('d M Y')} ({$this->assessment->day}) at {$this->assessment->time}. Therapist: {$tn}."
                : "Your assessment has been scheduled on {$this->assessment->date->format('d M Y')} ({$this->assessment->day}) at {$this->assessment->time}.",
            'assessment_id' => $this->assessment->id,
        ];
    }
}
