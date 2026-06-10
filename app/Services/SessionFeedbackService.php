<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Enrollment;
use App\Models\EnrollmentSchedule;
use App\Models\Service;
use App\Models\ServiceFeedbackQuestion;
use App\Models\SessionFeedbackResponse;
use App\Models\User;
use App\Support\SessionFeedbackRating;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class SessionFeedbackService
{
    public function __construct(
        private readonly ChildScheduleService $childSchedule,
    ) {}

    /** @return Collection<int, ServiceFeedbackQuestion> */
    public function activeQuestionsForService(?int $serviceId): Collection
    {
        if ($serviceId === null || $serviceId < 1) {
            return collect();
        }

        return ServiceFeedbackQuestion::query()
            ->where('service_id', $serviceId)
            ->active()
            ->ordered()
            ->get();
    }

    /** @return Collection<int, ServiceFeedbackQuestion> */
    public function activeQuestionsForSchedule(EnrollmentSchedule $schedule): Collection
    {
        $schedule->loadMissing('enrollment');

        return $this->activeQuestionsForService((int) ($schedule->enrollment?->service_id ?? 0));
    }

    /**
     * @param  array<int|string, mixed>  $ratings
     */
    public function validateRatingsForSchedule(EnrollmentSchedule $schedule, array $ratings): void
    {
        $questions = $this->activeQuestionsForSchedule($schedule);
        if ($questions->isEmpty()) {
            return;
        }

        $errors = [];
        foreach ($questions as $question) {
            $rating = $ratings[$question->id] ?? $ratings[(string) $question->id] ?? null;
            if ($rating === null || $rating === '') {
                $errors["ratings.{$question->id}"] = 'Please rate: ' . $question->question_text;

                continue;
            }

            if (! is_numeric($rating) || ! SessionFeedbackRating::isValid((int) $rating)) {
                $errors["ratings.{$question->id}"] = 'Please select a valid progress level.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<int|string, mixed>  $ratings
     */
    public function saveResponses(
        EnrollmentSchedule $schedule,
        string $occurrenceDateIso,
        User $therapist,
        array $ratings,
    ): void {
        $questions = $this->activeQuestionsForSchedule($schedule);
        if ($questions->isEmpty()) {
            return;
        }

        $this->validateRatingsForSchedule($schedule, $ratings);

        $date = Carbon::parse($occurrenceDateIso)->toDateString();

        foreach ($questions as $question) {
            $rating = (int) ($ratings[$question->id] ?? $ratings[(string) $question->id]);

            SessionFeedbackResponse::query()->updateOrCreate(
                [
                    'enrollment_schedule_id'       => $schedule->id,
                    'occurrence_date'              => $date,
                    'service_feedback_question_id' => $question->id,
                ],
                [
                    'rating'      => $rating,
                    'answered_by' => $therapist->id,
                ],
            );
        }
    }

  /**
     * @return array{
     *     items: list<array{question: string, rating: int, rating_label: string, rating_percent: int}>,
     *     overall: float|null,
     *     overall_label: string|null,
     *     overall_percent: int|null
     * }
     */
    public function summaryForSchedule(EnrollmentSchedule $schedule, string $occurrenceDateIso): array
    {
        $date = Carbon::parse($occurrenceDateIso)->toDateString();

        $responses = SessionFeedbackResponse::query()
            ->with('question')
            ->where('enrollment_schedule_id', $schedule->id)
            ->whereDate('occurrence_date', $date)
            ->get();

        if ($responses->isEmpty()) {
            return [
                'items'           => [],
                'overall'         => null,
                'overall_label'   => null,
                'overall_percent' => null,
            ];
        }

        $items = $responses
            ->sortBy(fn (SessionFeedbackResponse $r) => $r->question?->sort_order ?? 0)
            ->map(fn (SessionFeedbackResponse $r): array => [
                'question'       => (string) ($r->question?->question_text ?? 'Question'),
                'rating'         => (int) $r->rating,
                'rating_label'   => SessionFeedbackRating::label((int) $r->rating),
                'rating_percent' => SessionFeedbackRating::ratingPercent((int) $r->rating),
            ])
            ->values()
            ->all();

        $overall = round(collect($items)->avg('rating'), 1);

        return [
            'items'           => $items,
            'overall'         => $overall,
            'overall_label'   => SessionFeedbackRating::overallLabel($overall),
            'overall_percent' => SessionFeedbackRating::averagePercent($overall),
        ];
    }

    /**
     * Distribution of completed session feedback across the five progress levels (for child enrollment detail).
     *
     * @return array{
     *     completed_sessions: int,
     *     completed_with_feedback: int,
     *     overall_percent: int|null,
     *     overall_label: string|null,
     *     slices: list<array{level: int, label: string, count: int, percent: float, color: string}>,
     *     has_data: bool
     * }
     */
    public function enrollmentPerformanceChart(Enrollment $enrollment): array
    {
        return $this->buildPerformanceChartFromCompletedRows($this->completedRowsForEnrollment($enrollment));
    }

    /**
     * Performance charts keyed by service id, plus an "all" aggregate (therapist child profile).
     *
     * @param  \Illuminate\Support\Collection<int, Enrollment>  $enrollments
     * @return array{
     *     filter_options: list<array{key: string, label: string}>,
     *     charts: array<string, array<string, mixed>>,
     *     default_key: string,
     *     show_filter: bool
     * }
     */
    public function performanceChartsForEnrollments(Collection $enrollments): array
    {
        $enrollments = $enrollments->values();

        if ($enrollments->isEmpty()) {
            return [
                'filter_options' => [],
                'charts'         => [],
                'default_key'    => 'all',
                'show_filter'    => false,
            ];
        }

        $allRows = collect();
        $charts = [];
        $filterOptions = [];

        foreach ($enrollments->groupBy('service_id') as $serviceId => $group) {
            $serviceId = (int) $serviceId;
            if ($serviceId < 1) {
                continue;
            }

            $rows = collect();
            foreach ($group as $enrollment) {
                $rows = $rows->concat($this->completedRowsForEnrollment($enrollment));
            }

            $key = (string) $serviceId;
            $charts[$key] = $this->buildPerformanceChartFromCompletedRows($rows);
            $filterOptions[] = [
                'key'   => $key,
                'label' => (string) ($group->first()->service?->name ?? 'Service'),
            ];
            $allRows = $allRows->concat($rows);
        }

        if ($filterOptions === []) {
            return [
                'filter_options' => [],
                'charts'         => [],
                'default_key'    => 'all',
                'show_filter'    => false,
            ];
        }

        $charts['all'] = $this->buildPerformanceChartFromCompletedRows($allRows);
        $showFilter = count($filterOptions) > 1;

        if ($showFilter) {
            array_unshift($filterOptions, ['key' => 'all', 'label' => 'All services']);
            $defaultKey = 'all';
        } else {
            $defaultKey = $filterOptions[0]['key'];
        }

        return [
            'filter_options' => $filterOptions,
            'charts'         => $charts,
            'default_key'    => $defaultKey,
            'show_filter'    => $showFilter,
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    private function completedRowsForEnrollment(Enrollment $enrollment): Collection
    {
        return $this->childSchedule
            ->getExpandedOccurrencesForEnrollmentId((int) $enrollment->id)
            ->filter(fn (array $row): bool => ($row['status'] ?? '') === 'completed');
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $completedRows
     * @return array{
     *     completed_sessions: int,
     *     completed_with_feedback: int,
     *     overall_percent: int|null,
     *     overall_label: string|null,
     *     slices: list<array{level: int, label: string, count: int, percent: float, color: string}>,
     *     has_data: bool
     * }
     */
    private function buildPerformanceChartFromCompletedRows(Collection $completedRows): array
    {
        $pairs = $completedRows->map(fn (array $row): array => [
            'schedule_id' => (int) $row['schedule_id'],
            'date'        => $row['session_date']->toDateString(),
        ]);

        $scheduleIds = $pairs->pluck('schedule_id')->unique()->filter()->values();

        $responsesBySession = $scheduleIds->isEmpty()
            ? collect()
            : SessionFeedbackResponse::query()
                ->whereIn('enrollment_schedule_id', $scheduleIds->all())
                ->get()
                ->groupBy(fn (SessionFeedbackResponse $response): string => $response->enrollment_schedule_id . '|' . $response->occurrence_date->toDateString());

        $levelCounts = array_fill(1, 5, 0);
        $sessionAverages = [];

        foreach ($pairs as $pair) {
            $key = $pair['schedule_id'] . '|' . $pair['date'];
            $group = $responsesBySession->get($key);
            if ($group === null || $group->isEmpty()) {
                continue;
            }

            $average = round((float) $group->avg('rating'), 1);
            $level = max(SessionFeedbackRating::MIN, min(SessionFeedbackRating::MAX, (int) round($average)));
            $levelCounts[$level]++;
            $sessionAverages[] = $average;
        }

        $completedWithFeedback = array_sum($levelCounts);
        $overallAverage = $sessionAverages !== [] ? round(collect($sessionAverages)->avg(), 1) : null;

        $slices = [];
        foreach (SessionFeedbackRating::options() as $level => $label) {
            $count = $levelCounts[$level];
            $slices[] = [
                'level'   => $level,
                'label'   => $label,
                'count'   => $count,
                'percent' => $completedWithFeedback > 0
                    ? round(($count / $completedWithFeedback) * 100, 1)
                    : 0.0,
                'color'   => SessionFeedbackRating::chartColor($level),
            ];
        }

        return [
            'completed_sessions'      => $completedRows->count(),
            'completed_with_feedback' => $completedWithFeedback,
            'overall_percent'         => SessionFeedbackRating::averagePercent($overallAverage),
            'overall_label'           => SessionFeedbackRating::overallLabel($overallAverage),
            'slices'                  => $slices,
            'has_data'                => $completedWithFeedback > 0,
        ];
    }

    /**
     * @param  list<array{id?: int|null, text: string}>  $questions
     */
    public function syncServiceQuestions(Service $service, array $questions, int $userId): void
    {
        $normalized = [];
        foreach ($questions as $index => $row) {
            $text = trim((string) ($row['text'] ?? ''));
            if ($text === '') {
                continue;
            }

            $normalized[] = [
                'id'   => isset($row['id']) ? (int) $row['id'] : null,
                'text' => $text,
                'sort' => $index,
            ];
        }

        $keptIds = collect($normalized)->pluck('id')->filter()->all();

        $service->feedbackQuestions()
            ->when($keptIds !== [], fn ($q) => $q->whereNotIn('id', $keptIds))
            ->get()
            ->each(function (ServiceFeedbackQuestion $question): void {
                if ($question->responses()->exists()) {
                    $question->update(['is_active' => false]);
                } else {
                    $question->delete();
                }
            });

        foreach ($normalized as $row) {
            if ($row['id']) {
                $question = ServiceFeedbackQuestion::query()
                    ->where('service_id', $service->id)
                    ->whereKey($row['id'])
                    ->first();

                if ($question !== null) {
                    $question->update([
                        'question_text' => $row['text'],
                        'sort_order'    => $row['sort'],
                        'is_active'     => true,
                        'updated_by'    => $userId,
                    ]);

                    continue;
                }
            }

            ServiceFeedbackQuestion::query()->create([
                'service_id'    => $service->id,
                'question_text' => $row['text'],
                'sort_order'    => $row['sort'],
                'is_active'     => true,
                'created_by'    => $userId,
                'updated_by'    => $userId,
            ]);
        }
    }
}
