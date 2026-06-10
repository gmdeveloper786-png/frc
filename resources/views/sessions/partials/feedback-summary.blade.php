@if(! empty($sessionFeedback['items']))
    @php
        $overallPercent = $sessionFeedback['overall_percent'] ?? null;
        $overallLabel = $sessionFeedback['overall_label'] ?? null;
    @endphp
    <hr class="my-4" style="opacity:.15;">
    <h6 class="text-uppercase small fw-bold mb-3" style="color:var(--teal);letter-spacing:.04em;">Session feedback</h6>

    @if($overallPercent !== null)
        <div class="session-feedback-overall mb-4" style="display:flex;align-items:center;gap:16px;padding:16px 18px;border-radius:12px;background:linear-gradient(135deg,rgba(22,172,172,.08),rgba(17,81,124,.06));border:1px solid rgba(22,172,172,.18);">
            <div style="min-width:88px;text-align:center;">
                <div style="font-family:'Poppins',sans-serif;font-size:32px;font-weight:700;color:var(--teal);line-height:1;">{{ $overallPercent }}%</div>
                <div class="small text-muted" style="margin-top:4px;">Overall</div>
            </div>
            <div style="flex:1;">
                <div class="fw-semibold" style="color:var(--navy);font-size:15px;">Overall performance</div>
                @if($overallLabel)
                    <div class="small text-muted mt-1">{{ $overallLabel }}</div>
                @endif
                <div class="progress mt-2" style="height:8px;border-radius:999px;background:#e8eef3;">
                    <div class="progress-bar" role="progressbar" style="width:{{ $overallPercent }}%;background:var(--teal);border-radius:999px;" aria-valuenow="{{ $overallPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>
    @endif

    <dl class="row mb-0 small">
        @foreach($sessionFeedback['items'] as $item)
            <dt class="col-sm-8 text-muted mb-2">{{ $item['question'] }}</dt>
            <dd class="col-sm-4 mb-2 text-end fw-semibold" style="color:var(--navy);">
                {{ $item['rating_percent'] ?? 0 }}%
                <span class="d-block text-muted fw-normal" style="font-size:11px;">{{ $item['rating_label'] ?? '' }}</span>
            </dd>
        @endforeach
    </dl>
@endif
