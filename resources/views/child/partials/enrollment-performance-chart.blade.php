@include('partials.session-performance-chart', [
    'chartId' => 'enrollmentPerformance',
    'chart' => $row['performance_chart'] ?? [
        'completed_sessions' => 0,
        'completed_with_feedback' => 0,
        'overall_percent' => 0,
        'overall_label' => null,
        'has_data' => false,
        'slices' => collect(\App\Support\SessionFeedbackRating::options())
            ->map(fn (string $label, int $level): array => [
                'level' => $level,
                'label' => $label,
                'count' => 0,
                'percent' => 0.0,
                'color' => \App\Support\SessionFeedbackRating::chartColor($level),
            ])
            ->values()
            ->all(),
    ],
    'wrapperClass' => 'child-enrollment-detail__section child-enrollment-detail__performance session-performance-chart',
    'headingId' => 'performance-heading',
    'completedNoFeedbackMessage' => 'Performance will appear here once your therapist submits feedback for completed sessions.',
    'noSessionsMessage' => 'Complete your first session to start tracking performance here.',
])
