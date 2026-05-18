<?php

namespace App\Notifications;

use App\Models\Assessment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AssessmentCompletedNotification extends Notification
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
            'type'          => 'assessment_completed',
            'title'         => 'Assessment completed',
            'message'       => 'Assessment on '.$this->assessment->date->format('d M Y').' has been marked completed.',
            'assessment_id' => $this->assessment->id,
        ];
    }
}
