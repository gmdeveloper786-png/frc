<?php

namespace App\Notifications;

use App\Models\Assessment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AssessmentAssignedToTherapistNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Assessment $assessment) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'          => 'assessment_assigned_therapist',
            'title'         => 'New assessment assigned',
            'message'       => 'You have been assigned an assessment on '.$this->assessment->date->format('d M Y').' ('.$this->assessment->day.') at '.$this->assessment->time.'.',
            'assessment_id' => $this->assessment->id,
        ];
    }
}
