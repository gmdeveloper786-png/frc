<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentNote;
use App\Models\User;
use Illuminate\Support\Collection;

/** Who may see which rows in `assessment_notes` (structured notes). */
class AssessmentNoteVisibilityService
{
    /**
     * Structured notes visible to the viewer for this assessment (never for child/finance).
     *
     * @return Collection<int, \App\Models\AssessmentNote>
     */
    public function visibleNotes(Assessment $assessment, User $viewer): Collection
    {
        if ($viewer->isChild() || $viewer->isFinance()) {
            return collect();
        }

        if (! $assessment->relationLoaded('assessmentNotes')) {
            $assessment->load(['assessmentNotes.therapist', 'assessmentNotes.child', 'assessmentNotes.createdBy']);
        }

        $notes = $assessment->assessmentNotes->sortByDesc('created_at')->values();

        if ($viewer->isSuperAdmin()) {
            return $notes;
        }

        if ($viewer->hasPermission('manage_assessments')) {
            return $notes->where('status', 'completed')->values();
        }

        if ($viewer->isTherapist() && (int) $assessment->therapist_id === (int) $viewer->id) {
            return $notes->where('therapist_id', $viewer->id)->values();
        }

        return collect();
    }

    public function canCreateStructuredNote(User $user, Assessment $assessment): bool
    {
        return $user->isTherapist()
            && $assessment->status === 'publish'
            && (int) $assessment->therapist_id === (int) $user->id;
    }

    public function canManageNote(User $user, Assessment $assessment, AssessmentNote $note): bool
    {
        return $user->isTherapist()
            && (int) $assessment->therapist_id === (int) $user->id
            && (int) $note->assessment_id === (int) $assessment->id
            && (int) $note->therapist_id === (int) $user->id;
    }
}
