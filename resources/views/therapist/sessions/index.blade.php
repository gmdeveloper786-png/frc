@extends('layouts.app')
@section('title', 'Sessions')
@section('page-title', 'Sessions & Schedule')

@section('content')
@php
    $statuses = [
        'all' => 'All statuses',
        'scheduled' => 'Scheduled',
        'in_progress' => 'In progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];
@endphp

<div class="card-frc mb-4">
    <div class="card-header-frc">
        <h6 class="card-title-frc mb-0"><i class="fa-solid fa-filter me-2" style="color:var(--teal);"></i>Filters</h6>
    </div>
    <div class="p-3">
        <form method="get" action="{{ route('therapist.sessions.index') }}" id="therapistSessionsFilterForm" class="frc-sessions-filters">
            <div class="frc-sessions-filters-row">
                <div class="frc-sessions-filter-field">
                    <label class="form-label small text-muted mb-1" for="filter_start_date">Start date</label>
                    <input type="date" id="filter_start_date" name="start_date" class="form-control form-control-sm w-100"
                        value="{{ $startDate ?? '' }}" data-auto-submit>
                </div>
                <div class="frc-sessions-filter-field">
                    <label class="form-label small text-muted mb-1" for="filter_end_date">End date</label>
                    <input type="date" id="filter_end_date" name="end_date" class="form-control form-control-sm w-100"
                        value="{{ $endDate ?? '' }}" data-auto-submit>
                </div>
                <div class="frc-sessions-filter-field">
                    <label class="form-label small text-muted mb-1" for="filter_status">Status</label>
                    <select id="filter_status" name="status" class="form-select form-select-sm w-100" data-auto-submit>
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}" @selected(($status ?? 'all') === $key)>{{ $label }}</option>
                    @endforeach
                    </select>
                </div>
                <div class="frc-sessions-filter-field">
                    <label class="form-label small text-muted mb-1" for="filter_child_id">Child</label>
                    <select id="filter_child_id" name="child_id" class="form-select form-select-sm w-100" data-auto-submit>
                    <option value="">All children</option>
                    @foreach($filterChildren ?? [] as $c)
                        <option value="{{ $c->id }}" @selected(($filterChildId ?? null) === (int) $c->id)>{{ $c->full_name }}</option>
                    @endforeach
                    </select>
                </div>
                <div class="frc-sessions-filter-field">
                    <label class="form-label small text-muted mb-1" for="filter_service_id">Service</label>
                    <select id="filter_service_id" name="service_id" class="form-select form-select-sm w-100" data-auto-submit>
                    <option value="">All services</option>
                    @foreach($filterServices ?? [] as $svc)
                        <option value="{{ $svc->id }}" @selected(($filterServiceId ?? null) === (int) $svc->id)>{{ $svc->name }}</option>
                    @endforeach
                    </select>
                </div>
                @if($hasDateFilter ?? false)
                    <div class="frc-sessions-filter-field frc-sessions-filter-actions">
                        <label class="form-label small text-muted mb-1 frc-sessions-filter-actions-label" aria-hidden="true">&nbsp;</label>
                        <div class="filter-actions">
                            <a href="{{ route('therapist.sessions.index', array_filter([
                                'status' => ($status ?? 'all') !== 'all' ? $status : null,
                                'child_id' => $filterChildId ?? null,
                                'service_id' => $filterServiceId ?? null,
                            ])) }}" class="btn-outline-teal">
                                <i class="fa-solid fa-calendar-xmark me-1"></i>Clear dates
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </form>
        @if(!empty($defaultRangeHint))
            <p class="small text-muted mb-0 mt-2">{{ $defaultRangeHint }}</p>
        @endif
    </div>
</div>

<div class="card-frc frc-sessions-schedule-card">
    <div class="card-header-frc">
        <h6 class="card-title-frc mb-0"><i class="fa-solid fa-hand-holding-medical me-2" style="color:var(--teal);"></i>Your sessions ({{ $sessions->total() }})</h6>
    </div>
    @if($sessions->isEmpty())
        @php
            $filterServiceName = ($hasServiceFilter ?? false)
                ? (($filterServices ?? collect())->firstWhere('id', $filterServiceId)?->name ?? 'this service')
                : null;
            $emptyMessage = match (true) {
                ($hasStatusFilter ?? false) && ($hasDateFilter ?? false) => 'No sessions with status “' . ($statuses[$status] ?? $status) . '” in the selected date range.',
                ($hasStatusFilter ?? false) => 'No sessions with status “' . ($statuses[$status] ?? $status) . '”. Try another status or set a wider date range.',
                ($hasChildFilter ?? false) && ($hasDateFilter ?? false) => 'No sessions for this child in the selected date range.',
                ($hasChildFilter ?? false) => 'No sessions for this child. Try another child or adjust the date range.',
                ($hasServiceFilter ?? false) && ($hasDateFilter ?? false) => 'No sessions for ' . $filterServiceName . ' in the selected date range.',
                ($hasServiceFilter ?? false) => 'No sessions for ' . $filterServiceName . '. Try another service or adjust the date range.',
                ($hasDateFilter ?? false) => 'No sessions between the selected start and end dates.',
                default => 'No upcoming sessions scheduled.',
            };
        @endphp
        <div class="empty-state py-5">
            <i class="fa-solid fa-calendar-xmark empty-icon" style="font-size:28px;"></i>
            <p class="mb-0">{{ $emptyMessage }}</p>
        </div>
    @else
        <div class="table-responsive frc-sessions-table-wrap">
            <table class="table-frc mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Day</th>
                        <th>Time</th>
                        <th>Child</th>
                        <th>Service</th>
                        <th>Branch</th>
                        <th>Status</th>
                        <th class="text-end" style="min-width:84px;width:1%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sessions as $row)
                        @php
                            $sch = $row['schedule'];
                            $groupMembers = $row['group_members'] ?? null;
                            $isGroupRow = ! empty($groupMembers);
                            $occStatus = (string) ($row['status'] ?? $sch->status);
                            $badge = match ($occStatus) {
                                'scheduled' => 'badge-session-scheduled',
                                'in_progress' => 'badge-session-in-progress',
                                'completed' => 'badge-session-completed',
                                'cancelled' => 'badge-session-cancelled',
                                'no_show' => 'badge-session-no-show',
                                default => 'badge-draft',
                            };
                            $cid = $sch->enrollment?->child_id;
                            $dateIso = $row['effective_date']->toDateString();
                            $groupShowUrl = $isGroupRow
                                ? route('therapist.sessions.group-show', ['schedule' => $sch->id]).'?session_date='.urlencode($dateIso)
                                : null;
                        @endphp
                        <tr>
                            <td style="font-weight:600;color:var(--navy); white-space:nowrap;">{{ $row['effective_date']->format('d M Y') }}</td>
                            <td class="small text-muted white-space:nowrap;">{{ $row['effective_date']->format('l') }}</td>
                            <td style="white-space:nowrap;">{{ $row['time_slot'] }}</td>
                            <td style="white-space:normal;max-width:360px;line-height:1.45;">
                                @if($isGroupRow)
                                    @php
                                        $gmList = is_array($groupMembers) ? $groupMembers : collect($groupMembers)->all();
                                        $gmShown = array_slice($gmList, 0, 1);
                                        $gmExtra = count($gmList) - count($gmShown);
                                    @endphp
                                    @foreach($gmShown as $idx => $member)
                                        @if($idx > 0)<span class="text-muted">, </span>@endif
                                        @if((int) ($member['child_id'] ?? 0) > 0)
                                            <a href="{{ route('therapist.children.show', $member['child_id']) }}" style="color:var(--navy);text-decoration:underline;">{{ $member['child_name'] }}</a>
                                        @else
                                            {{ $member['child_name'] }}
                                        @endif
                                    @endforeach
                                    @if($gmExtra > 0)
                                        <span class="text-muted">, +{{ $gmExtra }} more</span>
                                    @endif
                                    <span class="badge-status badge-draft" style="font-size:10px;margin-left:6px;vertical-align:middle;">Group</span>
                                @elseif($cid)
                                    <a href="{{ route('therapist.children.show', $cid) }}" style="color:var(--navy);text-decoration:underline;">{{ $row['child_name'] }}</a>
                                @else
                                    {{ $row['child_name'] }}
                                @endif
                            </td>
                            <td class="small" style="white-space:nowrap;">{{ $row['service_name'] }}</td>
                            <td class="small" style="white-space:nowrap;">{{ $row['branch_name'] }}</td>
                            <td style="white-space:nowrap;"><span class="badge-status {{ $badge }}" style="font-size:11px;">{{ ucfirst(str_replace('_', ' ', $occStatus)) }}</span></td>
                            <td class="text-end align-middle pe-3">
                                <div class="dropdown d-inline-block">
                                    <button
                                        class="btn btn-sm btn-outline-teal dropdown-toggle frc-session-actions-btn"
                                        type="button"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false"
                                        aria-haspopup="true"
                                        title="Session actions"
                                    >
                                        <i class="fa-solid fa-ellipsis-vertical" aria-hidden="true"></i>
                                        <span class="visually-hidden">Open session actions</span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end frc-session-actions-menu">
                                        @if($isGroupRow)
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-2" href="{{ $groupShowUrl }}">
                                                    <i class="fa-solid fa-eye"></i><span>Session details</span>
                                                </a>
                                            </li>
                                            @if($occStatus === 'scheduled')
                                                <li><hr class="dropdown-divider"></li>
                                                @if($row['can_start_session_now'] ?? false)
                                                    <li>
                                                        <button type="button" class="dropdown-item d-flex align-items-center gap-2"
                                                            data-bs-toggle="modal" data-bs-target="#frcTherapistStartModal"
                                                            data-start-url="{{ route('therapist.sessions.group-start', $sch) }}"
                                                            data-session-date="{{ $dateIso }}"
                                                            data-is-group="1">
                                                            <i class="fa-solid fa-play"></i><span>Start session</span>
                                                        </button>
                                                    </li>
                                                @elseif(! ($row['session_start_window_passed'] ?? false))
                                                    <li>
                                                        <span class="dropdown-item disabled d-flex flex-column align-items-start gap-1 py-2">
                                                            <span class="d-flex align-items-center gap-2"><i class="fa-solid fa-clock"></i><span>Start session</span></span>
                                                            @if(! empty($row['occurrence_starts_at'] ?? null))
                                                                <span class="small text-muted">Available on {{ $row['occurrence_starts_at']->timezone(config('app.timezone'))->format('d M Y') }}</span>
                                                            @else
                                                                <span class="small text-muted">Not available yet</span>
                                                            @endif
                                                        </span>
                                                    </li>
                                                @endif
                                                <li>
                                                    <button type="button" class="dropdown-item d-flex align-items-center gap-2 text-danger"
                                                        data-bs-toggle="modal" data-bs-target="#frcTherapistCancelModal"
                                                        data-cancel-url="{{ route('therapist.sessions.group-cancel', $sch) }}"
                                                        data-session-date="{{ $dateIso }}"
                                                        data-is-group="1">
                                                        <i class="fa-solid fa-ban"></i><span>Cancel</span>
                                                    </button>
                                                </li>
                                            @endif
                                            @if($occStatus === 'in_progress')
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button type="button" class="dropdown-item d-flex align-items-center gap-2"
                                                        data-bs-toggle="modal" data-bs-target="#frcTherapistCompleteModal"
                                                        data-complete-url="{{ route('therapist.sessions.group-complete', $sch) }}"
                                                        data-feedback-url="{{ route('therapist.sessions.feedback-questions', $sch) }}"
                                                        data-session-date="{{ $dateIso }}"
                                                        data-is-group="1">
                                                        <i class="fa-solid fa-check"></i><span>Complete</span>
                                                    </button>
                                                </li>
                                                <li>
                                                    <button type="button" class="dropdown-item d-flex align-items-center gap-2 text-danger"
                                                        data-bs-toggle="modal" data-bs-target="#frcTherapistCancelModal"
                                                        data-cancel-url="{{ route('therapist.sessions.group-cancel', $sch) }}"
                                                        data-session-date="{{ $dateIso }}"
                                                        data-is-group="1">
                                                        <i class="fa-solid fa-ban"></i><span>Cancel</span>
                                                    </button>
                                                </li>
                                            @endif
                                        @else
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('therapist.sessions.show', ['schedule' => $sch->id, 'session_date' => $dateIso]) }}">
                                                <i class="fa-solid fa-eye"></i><span>View session details</span>
                                            </a>
                                        </li>

                                        @if($occStatus === 'scheduled')
                                            <li><hr class="dropdown-divider"></li>
                                            @if($row['can_start_session_now'] ?? false)
                                                <li>
                                                    <button type="button" class="dropdown-item d-flex align-items-center gap-2"
                                                        data-bs-toggle="modal" data-bs-target="#frcTherapistStartModal"
                                                        data-start-url="{{ route('therapist.sessions.start', $sch) }}"
                                                        data-session-date="{{ $dateIso }}"
                                                        data-is-group="0">
                                                        <i class="fa-solid fa-play"></i><span>Start session</span>
                                                    </button>
                                                </li>
                                            @elseif(! ($row['session_start_window_passed'] ?? false))
                                                <li>
                                                    <span class="dropdown-item disabled d-flex flex-column align-items-start gap-1 py-2">
                                                        <span class="d-flex align-items-center gap-2"><i class="fa-solid fa-clock"></i><span>Start session</span></span>
                                                        @if(! empty($row['occurrence_starts_at'] ?? null))
                                                            <span class="small text-muted">Available on {{ $row['occurrence_starts_at']->timezone(config('app.timezone'))->format('d M Y') }}</span>
                                                        @else
                                                            <span class="small text-muted">Not available yet</span>
                                                        @endif
                                                    </span>
                                                </li>
                                            @endif
                                            <li>
                                                <button type="button" class="dropdown-item d-flex align-items-center gap-2 text-danger"
                                                    data-bs-toggle="modal" data-bs-target="#frcTherapistCancelModal"
                                                    data-cancel-url="{{ route('therapist.sessions.cancel', $sch) }}"
                                                    data-session-date="{{ $dateIso }}"
                                                    data-is-group="0">
                                                    <i class="fa-solid fa-ban"></i><span>Cancel</span>
                                                </button>
                                            </li>
                                        @endif

                                        @if($occStatus === 'in_progress')
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <button type="button" class="dropdown-item d-flex align-items-center gap-2"
                                                    data-bs-toggle="modal" data-bs-target="#frcTherapistCompleteModal"
                                                    data-complete-url="{{ route('therapist.sessions.complete', $sch) }}"
                                                    data-feedback-url="{{ route('therapist.sessions.feedback-questions', $sch) }}"
                                                    data-session-date="{{ $dateIso }}"
                                                    data-is-group="0">
                                                    <i class="fa-solid fa-check"></i><span>Complete</span>
                                                </button>
                                            </li>
                                            <li>
                                                <button type="button" class="dropdown-item d-flex align-items-center gap-2 text-danger"
                                                    data-bs-toggle="modal" data-bs-target="#frcTherapistCancelModal"
                                                    data-cancel-url="{{ route('therapist.sessions.cancel', $sch) }}"
                                                    data-session-date="{{ $dateIso }}"
                                                    data-is-group="0">
                                                    <i class="fa-solid fa-ban"></i><span>Cancel</span>
                                                </button>
                                            </li>
                                        @endif

                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($sessions->hasPages())
            <div class="frc-list-pagination" aria-label="Sessions list pages">
                {{ $sessions->onEachSide(1)->links() }}
            </div>
        @endif
    @endif
</div>

<div class="modal fade" id="frcTherapistStartModal" tabindex="-1" aria-labelledby="frcTherapistStartLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px;border:1px solid var(--border-soft);overflow:hidden;">
            <div class="modal-header" style="background:var(--bg-light);border-bottom:1px solid var(--border-soft);">
                <h5 class="modal-title" id="frcTherapistStartLabel" style="font-family:'Poppins',sans-serif;font-weight:600;color:var(--navy);font-size:17px;">Start session</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                @csrf
                <input type="hidden" name="session_date" value="" autocomplete="off">
                <div class="modal-body">
                    <p class="small text-muted mb-0" id="frcTherapistStartModalBody">Mark this session as <strong>in progress</strong>? You can complete or cancel it afterwards.</p>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--border-soft);gap:8px;">
                    <button type="button" class="btn-outline-teal" data-bs-dismiss="modal" style="border-radius:10px;">Close</button>
                    <button type="submit" class="btn-teal" style="border-radius:10px;">Confirm start</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="frcTherapistCompleteModal" tabindex="-1" aria-labelledby="frcTherapistCompleteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:14px;border:1px solid var(--border-soft);overflow:hidden;">
            <div class="modal-header" style="background:var(--bg-light);border-bottom:1px solid var(--border-soft);">
                <h5 class="modal-title" id="frcTherapistCompleteLabel" style="font-family:'Poppins',sans-serif;font-weight:600;color:var(--navy);font-size:17px;">Complete session</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                @csrf
                <input type="hidden" name="session_date" value="" autocomplete="off">
                <div class="modal-body">
                    <p class="small text-muted mb-2" id="frcTherapistCompleteModalBody">Session moves to <strong>completed</strong>. You can add an optional <strong>completion note</strong> (short wrap-up).</p>
                    <div id="frcSessionFeedbackQuestions" class="mb-3"></div>
                    <label class="form-label small fw-semibold" style="color:var(--navy);">Completion note <span class="text-muted fw-normal">(optional)</span></label>
                    <textarea name="completion_note" class="form-control" rows="4" maxlength="5000" placeholder="Brief summary of how the session ended…"></textarea>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--border-soft);gap:8px;">
                    <button type="button" class="btn-outline-teal" data-bs-dismiss="modal" style="border-radius:10px;">Close</button>
                    <button type="submit" class="btn-teal" style="border-radius:10px;">Mark completed</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="frcTherapistCancelModal" tabindex="-1" aria-labelledby="frcTherapistCancelLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px;border:1px solid var(--border-soft);overflow:hidden;">
            <div class="modal-header" style="background:#fde8e8;border-bottom:1px solid var(--border-soft);">
                <h5 class="modal-title" id="frcTherapistCancelLabel" style="font-family:'Poppins',sans-serif;font-weight:600;color:var(--danger);font-size:17px;">Cancel session</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                @csrf
                <input type="hidden" name="session_date" value="" autocomplete="off">
                <div class="modal-body">
                    <p class="small text-muted mb-2" id="frcTherapistCancelModalIntro">A cancellation reason is required.</p>
                    <label class="form-label small fw-semibold" style="color:var(--navy);">Cancellation reason <span class="text-danger">*</span></label>
                    <textarea name="cancellation_reason" class="form-control" rows="4" required minlength="1" maxlength="5000" placeholder="Explain why this session is being cancelled…"></textarea>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--border-soft);gap:8px;">
                    <button type="button" class="btn-outline-teal" data-bs-dismiss="modal" style="border-radius:10px;">Close</button>
                    <button type="submit" class="btn-teal" style="border-radius:10px;background:var(--danger);border-color:var(--danger);">Confirm cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce }}">
window.FRC_SESSION_FEEDBACK_RATINGS = @json(\App\Support\SessionFeedbackRating::options());
</script>
<script nonce="{{ $cspNonce }}">
(function () {
    document.querySelectorAll('.frc-sessions-schedule-card .frc-session-actions-btn').forEach(function (toggleEl) {
        var existing = bootstrap.Dropdown.getInstance(toggleEl);
        if (existing) {
            existing.dispose();
        }
        new bootstrap.Dropdown(toggleEl, {
            popperConfig: function (defaultBsPopperConfig) {
                return Object.assign({}, defaultBsPopperConfig || {}, { strategy: 'fixed' });
            }
        });
    });

    var startModal = document.getElementById('frcTherapistStartModal');
    if (startModal) {
        startModal.addEventListener('show.bs.modal', function (event) {
            var btn = event.relatedTarget;
            var url = btn && btn.getAttribute('data-start-url');
            var sessionDate = btn && btn.getAttribute('data-session-date');
            var isGroup = btn && btn.getAttribute('data-is-group') === '1';
            var form = startModal.querySelector('form');
            if (form && url) form.setAttribute('action', url);
            var hid = startModal.querySelector('input[name="session_date"]');
            if (hid) hid.value = sessionDate || '';
            var p = document.getElementById('frcTherapistStartModalBody');
            if (p) {
                p.innerHTML = isGroup
                    ? 'Mark this <strong>group session</strong> as <strong>in progress</strong> for <strong>every child</strong> in the slot? You can complete or cancel afterwards.'
                    : 'Mark this session as <strong>in progress</strong>? You can complete or cancel it afterwards.';
            }
        });
    }

    var completeModal = document.getElementById('frcTherapistCompleteModal');
    if (completeModal) {
        completeModal.addEventListener('show.bs.modal', function (event) {
            var btn = event.relatedTarget;
            var url = btn && btn.getAttribute('data-complete-url');
            var feedbackUrl = btn && btn.getAttribute('data-feedback-url');
            var sessionDate = btn && btn.getAttribute('data-session-date');
            var isGroup = btn && btn.getAttribute('data-is-group') === '1';
            var form = completeModal.querySelector('form');
            if (form && url) form.setAttribute('action', url);
            var hid = completeModal.querySelector('input[name="session_date"]');
            if (hid) hid.value = sessionDate || '';
            var ta = completeModal.querySelector('textarea[name="completion_note"]');
            if (ta) ta.value = '';
            var p = document.getElementById('frcTherapistCompleteModalBody');
            if (p) {
                p.innerHTML = isGroup
                    ? 'This marks <strong>every child</strong> in the group slot as <strong>completed</strong>. Rate each feedback question below (required when configured for this service).'
                    : 'Session moves to <strong>completed</strong>. Rate each feedback question below (required when configured for this service).';
            }

            var feedbackBox = document.getElementById('frcSessionFeedbackQuestions');
            if (!feedbackBox) return;
            feedbackBox.innerHTML = '<p class="small text-muted mb-0">Loading feedback questions…</p>';

            if (!feedbackUrl) {
                feedbackBox.innerHTML = '';
                return;
            }

            fetch(feedbackUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (res) { return res.json(); })
                .then(function (payload) {
                    var questions = (payload && payload.data) ? payload.data : [];
                    if (!questions.length) {
                        feedbackBox.innerHTML = '';
                        return;
                    }

                    var html = '<div class="border rounded-3 p-3 mb-2" style="background:var(--bg-light);border-color:var(--border-soft)!important;">'
                        + '<h6 class="small fw-bold mb-3" style="color:var(--navy);">Session feedback <span class="text-danger">*</span></h6>';
                    var ratingOptions = window.FRC_SESSION_FEEDBACK_RATINGS || {};
                    questions.forEach(function (q) {
                        html += '<div class="mb-3">'
                            + '<label class="form-label small fw-semibold mb-1" style="color:var(--navy);">' + q.text + '</label>'
                            + '<select name="ratings[' + q.id + ']" class="form-control form-select" required>'
                            + '<option value="">Select progress level</option>';
                        Object.keys(ratingOptions).forEach(function (key) {
                            html += '<option value="' + key + '">' + key + '. ' + ratingOptions[key] + '</option>';
                        });
                        html += '</select></div>';
                    });
                    html += '</div>';
                    feedbackBox.innerHTML = html;
                })
                .catch(function () {
                    feedbackBox.innerHTML = '<p class="small text-danger mb-0">Could not load feedback questions. Please try again.</p>';
                });
        });
    }

    var filterForm = document.getElementById('therapistSessionsFilterForm');
    if (filterForm) {
        filterForm.addEventListener('submit', function () {
            filterForm.querySelectorAll('input[type="date"]').forEach(function (input) {
                if (!input.value) {
                    input.removeAttribute('name');
                }
            });
        });
    }

    var cancelModal = document.getElementById('frcTherapistCancelModal');
    if (cancelModal) {
        cancelModal.addEventListener('show.bs.modal', function (event) {
            var btn = event.relatedTarget;
            var url = btn && btn.getAttribute('data-cancel-url');
            var sessionDate = btn && btn.getAttribute('data-session-date');
            var isGroup = btn && btn.getAttribute('data-is-group') === '1';
            var form = cancelModal.querySelector('form');
            if (form && url) form.setAttribute('action', url);
            var hid = cancelModal.querySelector('input[name="session_date"]');
            if (hid) hid.value = sessionDate || '';
            var ta = cancelModal.querySelector('textarea[name="cancellation_reason"]');
            if (ta) {
                ta.value = '';
                ta.classList.remove('is-invalid');
            }
            var intro = document.getElementById('frcTherapistCancelModalIntro');
            if (intro) {
                intro.textContent = isGroup
                    ? 'This cancels the session for every child in this group slot. A cancellation reason is required.'
                    : 'A cancellation reason is required.';
            }
        });
    }
})();
</script>
@endpush
@endsection
