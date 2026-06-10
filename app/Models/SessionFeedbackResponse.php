<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionFeedbackResponse extends Model
{
    protected $fillable = [
        'enrollment_schedule_id',
        'occurrence_date',
        'service_feedback_question_id',
        'rating',
        'answered_by',
    ];

    protected $casts = [
        'occurrence_date' => 'date',
        'rating'          => 'integer',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(EnrollmentSchedule::class, 'enrollment_schedule_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ServiceFeedbackQuestion::class, 'service_feedback_question_id')->withTrashed();
    }

    public function answeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'answered_by');
    }
}
