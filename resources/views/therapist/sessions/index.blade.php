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

@if($errors->has('session_date'))
    <div class="alert alert-danger border-0 mb-4" style="border-radius:12px;">
        <i class="fa-solid fa-circle-exclamation me-2"></i>{{ $errors->first('session_date') }}
    </div>
@endif

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
                        value="{{ $startDate ?? '' }}" onchange="this.form.submit()">
                </div>
                <div class="frc-sessions-filter-field">
                    <label class="form-label small text-muted mb-1" for="filter_end_date">End date</label>
                    <input type="date" id="filter_end_date" name="end_date" class="form-control form-control-sm w-100"
                        value="{{ $endDate ?? '' }}" onchange="this.form.submit()">
                </div>
                <div class="frc-sessions-filter-field">
                    <label class="form-label small text-muted mb-1" for="filter_status">Status</label>
                    <select id="filter_status" name="status" class="form-select form-select-sm w-100" onchange="this.form.submit()">
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}" @selected(($status ?? 'all') === $key)>{{ $label }}</option>
                    @endforeach
                    </select>
                </div>
                <div class="frc-sessions-filter-field">
                    <label class="form-label small text-muted mb-1" for="filter_child_id">Child</label>
                    <select id="filter_child_id" name="child_id" class="form-select form-select-sm w-100" onchange="this.form.submit()">
                    <option value="">All children</option>
                    @foreach($filterChildren ?? [] as $c)
                        <option value="{{ $c->id }}" @selected(($filterChildId ?? null) === (int) $c->id)>{{ $c->full_name }}</option>
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
            $emptyMessage = match (true) {
                ($hasStatusFilter ?? false) && ($hasDateFilter ?? false) => 'No sessions with status “' . ($statuses[$status] ?? $status) . '” in the selected date range.',
                ($hasStatusFilter ?? false) => 'No sessions with status “' . ($statuses[$status] ?? $status) . '”. Try another status or set a wider date range.',
                ($hasChildFilter ?? false) && ($hasDateFilter ?? false) => 'No sessions for this child in the selected date range.',
                ($hasChildFilter ?? false) => 'No sessions for this child. Try another child or adjust the date range.',
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
                            $pnCreate = $cid
                                ? route('therapist.progress-notes.create', array_filter([
                                    'child_id' => $cid,
                                    'session_date' => $dateIso,
                                    'service_id' => $sch->enrollment?->service_id,
                                    'enrollment_id' => $sch->enrollment_id,
                                    'enrollment_schedule_id' => $sch->id,
                                ], fn ($v) => $v !== null && $v !== ''))
                                : null;
                            $meta = $row['progress_meta'] ?? [];
                            $pnDraftId = $meta['draft_id'] ?? null;
                            $pnCompletedId = $meta['completed_id'] ?? null;
                        @endphp
                        <tr>
                            <td style="font-weight:600;color:var(--navy); white-space:nowrap;">{{ $row['effective_date']->format('d M Y') }}</td>
                            <td class="small text-muted white-space:nowrap;">{{ $row['effective_date']->format('l') }}</td>
                            <td style="white-space:nowrap;">{{ $row['time_slot'] }}</td>
                            <td style="white-space:nowrap;">{{ $row['child_name'] }}</td>
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
                                        @if($cid)
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('therapist.children.show', $cid) }}">
                                                    <i class="fa-solid fa-child"></i><span>View child</span>
                                                </a>
                                            </li>
                                        @endif
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('therapist.sessions.show', ['schedule' => $sch->id, 'session_date' => $dateIso]) }}">
                                                <i class="fa-solid fa-eye"></i><span>View session details</span>
                                            </a>
                                        </li>

                                        @if($occStatus === 'scheduled')
                                            @if($cid)<li><hr class="dropdown-divider"></li>@endif
                                            @if($row['can_start_session_now'] ?? false)
                                                <li>
                                                    <button type="button" class="dropdown-item d-flex align-items-center gap-2"
                                                        data-bs-toggle="modal" data-bs-target="#frcTherapistStartModal"
                                                        data-start-url="{{ route('therapist.sessions.start', $sch) }}"
                                                        data-session-date="{{ $dateIso }}">
                                                        <i class="fa-solid fa-play"></i><span>Start session</span>
                                                    </button>
                                                </li>
                                            @elseif(! ($row['session_start_window_passed'] ?? false))
                                                <li>
                                                    <span class="dropdown-item disabled d-flex flex-column align-items-start gap-1 py-2">
                                                        <span class="d-flex align-items-center gap-2"><i class="fa-solid fa-clock"></i><span>Start session</span></span>
                                                        @if(! empty($row['occurrence_starts_at'] ?? null) && ! empty($row['occurrence_ends_at'] ?? null))
                                                            <span class="small text-muted">Available {{ $row['occurrence_starts_at']->timezone(config('app.timezone'))->format('g:i A') }} – {{ $row['occurrence_ends_at']->timezone(config('app.timezone'))->format('g:i A') }} on {{ $row['occurrence_starts_at']->format('d M Y') }}</span>
                                                        @elseif(! empty($row['occurrence_starts_at'] ?? null))
                                                            <span class="small text-muted">Available from {{ $row['occurrence_starts_at']->timezone(config('app.timezone'))->format('d M Y, g:i A') }}</span>
                                                        @else
                                                            <span class="small text-muted">Not available yet</span>
                                                        @endif
                                                    </span>
                                                </li>
                                            @endif
                                            <li>
                                                <button type="button" class="dropdown-item d-flex align-items-center gap-2 text-danger"
                                                    data-bs-toggle="modal" data-bs-target="#frcTherapistCancelModal"
                                                    data-cancel-url="{{ route('therapist.sessions.cancel', $sch) }}">
                                                    <i class="fa-solid fa-ban"></i><span>Cancel</span>
                                                </button>
                                            </li>
                                        @endif

                                        @if($occStatus === 'in_progress')
                                            @if($cid)<li><hr class="dropdown-divider"></li>@endif
                                            <li>
                                                <button type="button" class="dropdown-item d-flex align-items-center gap-2"
                                                    data-bs-toggle="modal" data-bs-target="#frcTherapistCompleteModal"
                                                    data-complete-url="{{ route('therapist.sessions.complete', $sch) }}"
                                                    data-session-date="{{ $dateIso }}">
                                                    <i class="fa-solid fa-check"></i><span>Complete</span>
                                                </button>
                                            </li>
                                            <li>
                                                <button type="button" class="dropdown-item d-flex align-items-center gap-2 text-danger"
                                                    data-bs-toggle="modal" data-bs-target="#frcTherapistCancelModal"
                                                    data-cancel-url="{{ route('therapist.sessions.cancel', $sch) }}">
                                                    <i class="fa-solid fa-ban"></i><span>Cancel</span>
                                                </button>
                                            </li>
                                        @endif

                                        @if($occStatus === 'completed' && ($pnDraftId || $pnCompletedId || $pnCreate))
                                            @if($cid)
                                                <li><hr class="dropdown-divider"></li>
                                            @endif
                                            <li>
                                                @if($pnDraftId)
                                                    <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('therapist.progress-notes.edit', $pnDraftId) }}">
                                                        <i class="fa-solid fa-file-lines"></i><span>Continue draft note</span>
                                                    </a>
                                                @elseif(!$pnCompletedId && $pnCreate)
                                                    <a class="dropdown-item d-flex align-items-center gap-2" href="{{ $pnCreate }}">
                                                        <i class="fa-solid fa-file-circle-plus"></i><span>Add progress note</span>
                                                    </a>
                                                @elseif($pnCompletedId)
                                                    <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('therapist.progress-notes.show', $pnCompletedId) }}">
                                                        <i class="fa-solid fa-eye"></i><span>View progress note</span>
                                                    </a>
                                                @endif
                                            </li>
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
                    <p class="small text-muted mb-0">Mark this session as <strong>in progress</strong>? You can complete or cancel it afterwards.</p>
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
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px;border:1px solid var(--border-soft);overflow:hidden;">
            <div class="modal-header" style="background:var(--bg-light);border-bottom:1px solid var(--border-soft);">
                <h5 class="modal-title" id="frcTherapistCompleteLabel" style="font-family:'Poppins',sans-serif;font-weight:600;color:var(--navy);font-size:17px;">Complete session</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                @csrf
                <input type="hidden" name="session_date" value="" autocomplete="off">
                <div class="modal-body">
                    <p class="small text-muted mb-2">Session moves to <strong>completed</strong>. Add an optional <strong>completion note</strong> (short wrap-up). Detailed clinical documentation belongs in a <strong>progress note</strong>.</p>
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
                <div class="modal-body">
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
<script>
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
            var form = startModal.querySelector('form');
            if (form && url) form.setAttribute('action', url);
            var hid = startModal.querySelector('input[name="session_date"]');
            if (hid) hid.value = sessionDate || '';
        });
    }

    var completeModal = document.getElementById('frcTherapistCompleteModal');
    if (completeModal) {
        completeModal.addEventListener('show.bs.modal', function (event) {
            var btn = event.relatedTarget;
            var url = btn && btn.getAttribute('data-complete-url');
            var sessionDate = btn && btn.getAttribute('data-session-date');
            var form = completeModal.querySelector('form');
            if (form && url) form.setAttribute('action', url);
            var hid = completeModal.querySelector('input[name="session_date"]');
            if (hid) hid.value = sessionDate || '';
            var ta = completeModal.querySelector('textarea[name="completion_note"]');
            if (ta) ta.value = '';
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
            var form = cancelModal.querySelector('form');
            if (form && url) form.setAttribute('action', url);
            var ta = cancelModal.querySelector('textarea[name="cancellation_reason"]');
            if (ta) {
                ta.value = '';
                ta.classList.remove('is-invalid');
            }
        });
    }
})();
</script>
@endpush
@endsection
