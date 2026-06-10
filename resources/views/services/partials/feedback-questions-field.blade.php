@php
    $existing = $service?->feedbackQuestions ?? collect();
    $oldRows = old('feedback_questions');
    $rows = [];
    if (is_array($oldRows) && $oldRows !== []) {
        foreach ($oldRows as $row) {
            $text = trim((string) ($row['text'] ?? ''));
            if ($text !== '') {
                $rows[] = ['id' => $row['id'] ?? null, 'text' => $text];
            }
        }
    } elseif ($existing->isNotEmpty()) {
        foreach ($existing as $question) {
            $rows[] = ['id' => $question->id, 'text' => $question->question_text];
        }
    }
    if ($rows === []) {
        $rows[] = ['id' => null, 'text' => ''];
    }
@endphp

<div class="mb-4">
    <label class="d-block mb-2 fw-semibold" style="color:var(--navy);">Session feedback questions</label>
    <p class="small text-muted mb-3">Therapists rate progress (No progress → Excellent progress) when completing a session for this service.</p>
    <div id="serviceFeedbackQuestions" class="d-flex flex-column gap-2">
        @foreach($rows as $index => $row)
            <div class="service-feedback-question-row d-flex gap-2 align-items-start">
                @if(! empty($row['id']))
                    <input type="hidden" name="feedback_questions[{{ $index }}][id]" value="{{ $row['id'] }}">
                @endif
                <input
                    type="text"
                    name="feedback_questions[{{ $index }}][text]"
                    value="{{ $row['text'] }}"
                    class="form-control @error('feedback_questions.' . $index . '.text') is-invalid @enderror"
                    placeholder="e.g. How was the child's engagement during the session?"
                >
                <button type="button" class="btn-outline-teal service-feedback-remove-btn" style="padding:8px 12px;flex-shrink:0;" title="Remove question" aria-label="Remove question">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        @endforeach
    </div>
    @error('feedback_questions') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    @error('feedback_questions.*.text') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    <button type="button" id="serviceFeedbackAddBtn" class="btn-outline-teal mt-3" style="font-size:13px;">
        <i class="fa-solid fa-plus"></i> Add question
    </button>
</div>

@once
    @push('scripts')
    <script nonce="{{ $cspNonce }}">
    (function () {
        var container = document.getElementById('serviceFeedbackQuestions');
        var addBtn = document.getElementById('serviceFeedbackAddBtn');
        if (!container || !addBtn) return;

        function nextIndex() {
            return container.querySelectorAll('.service-feedback-question-row').length;
        }

        function bindRemove(btn) {
            btn.addEventListener('click', function () {
                var rows = container.querySelectorAll('.service-feedback-question-row');
                if (rows.length <= 1) {
                    rows[0].querySelector('input[type="text"]').value = '';
                    var hidden = rows[0].querySelector('input[type="hidden"]');
                    if (hidden) hidden.remove();
                    return;
                }
                btn.closest('.service-feedback-question-row').remove();
            });
        }

        container.querySelectorAll('.service-feedback-remove-btn').forEach(bindRemove);

        addBtn.addEventListener('click', function () {
            var idx = nextIndex();
            var row = document.createElement('div');
            row.className = 'service-feedback-question-row d-flex gap-2 align-items-start';
            row.innerHTML =
                '<input type="text" name="feedback_questions[' + idx + '][text]" class="form-control" placeholder="e.g. How was the child\'s engagement during the session?">' +
                '<button type="button" class="btn-outline-teal service-feedback-remove-btn" style="padding:8px 12px;flex-shrink:0;" title="Remove question" aria-label="Remove question"><i class="fa-solid fa-trash"></i></button>';
            container.appendChild(row);
            bindRemove(row.querySelector('.service-feedback-remove-btn'));
        });
    })();
    </script>
    @endpush
@endonce
