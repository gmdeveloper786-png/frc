{{-- Full enrollment breakdown for child portal detail page ($row from ChildPortalService::presentEnrollmentRow). --}}
@php
    $e = $row['enrollment'];
    $completedSessions = $row['completed_sessions'];
    $remainingSessions = $row['remaining_sessions'];
    $upcomingSessions = $row['upcoming_sessions'] ?? 0;
    $daysPerWeek = $row['days_per_week'] ?? 0;
    $nextSession = $row['next_session'];
    $recurring = $row['recurring_summary'];
    $payEff = $e->effectivePaymentStatus();
    $outstanding = $e->outstandingAmount();
    $dp = (float) ($e->discount_percentage ?? 0);
    $pctLabel = null;
    if ($dp > 0) {
        $pctLabel = frc_percent($dp);
    }
@endphp
<article class="card-frc card-frc--child-enrollment-detail child-enrollment-detail">
    <header class="card-header-frc child-enrollment-detail__header">
        <div class="child-enrollment-detail__header-main">
            <h2 class="child-enrollment-detail__programme">{{ $e->service?->name ?? 'Enrollment' }}</h2>
            <ul class="child-enrollment-detail__meta list-unstyled mb-0">
                <li><i class="fa-solid fa-location-dot" aria-hidden="true"></i> {{ $e->branch?->name ?? '—' }}</li>
                <li><i class="fa-solid fa-user-doctor" aria-hidden="true"></i> {{ $e->therapist?->full_name ?? '—' }}</li>
            </ul>
        </div>
        <div class="child-enrollment-detail__badges">
            <span class="badge-status badge-{{ $e->status }}">{{ \Illuminate\Support\Str::title(str_replace('_', ' ', $e->status)) }}</span>
            <span class="badge-status badge-{{ $payEff }}">{{ \App\Models\Payment::labelForEnrollmentPaymentStatus($payEff) }}</span>
        </div>
    </header>

    <div class="child-enrollment-detail__body">
        <section class="child-enrollment-detail__section" aria-label="Session progress">
            <div class="row g-2 g-md-3 child-enrollment-detail__session-metrics">
                <div class="col-6 col-lg-3">
                    <div class="child-enrollment-detail__stat child-enrollment-detail__stat--total">
                        <span class="child-enrollment-detail__stat-label">Total sessions</span>
                        <span class="child-enrollment-detail__stat-value">{{ $e->total_sessions }}</span>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="child-enrollment-detail__stat child-enrollment-detail__stat--done">
                        <span class="child-enrollment-detail__stat-label">Completed</span>
                        <span class="child-enrollment-detail__stat-value">{{ $completedSessions }}</span>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="child-enrollment-detail__stat child-enrollment-detail__stat--upcoming">
                        <span class="child-enrollment-detail__stat-label">Scheduled</span>
                        <span class="child-enrollment-detail__stat-value">{{ $upcomingSessions }}</span>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="child-enrollment-detail__stat child-enrollment-detail__stat--remaining">
                        <span class="child-enrollment-detail__stat-label">Remaining</span>
                        <span class="child-enrollment-detail__stat-value">{{ $remainingSessions }}</span>
                    </div>
                </div>
            </div>
            @if($recurring)
                <p class="child-enrollment-detail__recurring small text-muted mb-0">
                    <i class="fa-solid fa-repeat" aria-hidden="true"></i>
                    <strong>{{ $recurring['selected_days'] }}</strong>
                    @if($recurring['duration_label'])
                        · {{ $recurring['duration_label'] }}
                    @endif
                    @if($daysPerWeek > 0)
                        · {{ $daysPerWeek }} {{ $daysPerWeek === 1 ? 'day' : 'days' }} per week
                    @endif
                </p>
            @endif
        </section>

        @include('child.partials.enrollment-performance-chart', ['row' => $row])

        @if($nextSession)
            <section class="child-enrollment-detail__section child-enrollment-detail__next-session" aria-labelledby="next-session-heading">
                <div class="child-enrollment-detail__next-head">
                    <h3 id="next-session-heading" class="child-enrollment-detail__section-title mb-0">
                        <i class="fa-solid fa-calendar-check" aria-hidden="true"></i> Next session
                    </h3>
                    <span class="badge-status badge-{{ $nextSession['badge'] }}">{{ \Illuminate\Support\Str::title(str_replace('_', ' ', $nextSession['status'])) }}</span>
                </div>
                <div class="child-enrollment-detail__next-body">
                    <div class="child-enrollment-detail__next-datetime">
                        <span class="child-enrollment-detail__next-day">{{ $nextSession['day_label'] }}</span>
                        <span class="child-enrollment-detail__next-date">{{ $nextSession['date_label'] }}</span>
                    </div>
                    <div class="child-enrollment-detail__next-time">
                        <i class="fa-regular fa-clock" aria-hidden="true"></i>
                        {{ $nextSession['time_slot'] }}
                    </div>
                </div>
            </section>
        @endif

        <section class="child-enrollment-detail__section child-enrollment-detail__payments" aria-labelledby="payment-heading">
            <h3 id="payment-heading" class="child-enrollment-detail__section-title">
                <i class="fa-solid fa-receipt" aria-hidden="true"></i> Payment summary
            </h3>
            <div class="row g-2 g-md-3">
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="child-enrollment-detail__payment-tile">
                        <span class="child-enrollment-detail__payment-label">Total fee (before discount)</span>
                        <span class="child-enrollment-detail__payment-amount">PKR {{ frc_money($e->subtotal) }}</span>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="child-enrollment-detail__payment-tile">
                        <span class="child-enrollment-detail__payment-label">
                            Discount @if($pctLabel !== null)<span class="text-muted">({{ $pctLabel }}%)</span>@endif
                        </span>
                        <span class="child-enrollment-detail__payment-amount">PKR {{ frc_money($e->discount_amount) }}</span>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="child-enrollment-detail__payment-tile child-enrollment-detail__payment-tile--highlight">
                        <span class="child-enrollment-detail__payment-label">Final payable amount</span>
                        <span class="child-enrollment-detail__payment-amount child-enrollment-detail__payment-amount--emphasis">PKR {{ frc_money($e->final_total) }}</span>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="child-enrollment-detail__payment-tile child-enrollment-detail__payment-tile--paid">
                        <span class="child-enrollment-detail__payment-label">Paid amount</span>
                        <span class="child-enrollment-detail__payment-amount child-enrollment-detail__payment-amount--paid">PKR {{ frc_money($e->paid_amount) }}</span>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="child-enrollment-detail__payment-tile child-enrollment-detail__payment-tile--due">
                        <span class="child-enrollment-detail__payment-label">Remaining amount</span>
                        <span class="child-enrollment-detail__payment-amount child-enrollment-detail__payment-amount--due">PKR {{ frc_money($outstanding) }}</span>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="child-enrollment-detail__payment-tile">
                        <span class="child-enrollment-detail__payment-label">Payment status</span>
                        <span class="badge-status badge-{{ $payEff }} mt-1">{{ \App\Models\Payment::labelForEnrollmentPaymentStatus($payEff) }}</span>
                    </div>
                </div>
            </div>
        </section>

        @if($row['show_fee_fully_paid_notice'])
            <div class="alert-frc child-enrollment-detail__alert child-enrollment-detail__alert--success" role="status">
                <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                Your fee is fully paid. No payment slip is required.
            </div>
        @endif

        <div class="child-enrollment-detail__actions">
            @if($row['show_upload_slip_button'])
                <a href="{{ route('child.upload-slip', ['enrollment_id' => $e->id]) }}" class="btn-teal child-enrollment-detail__btn">
                    <i class="fa-solid fa-upload" aria-hidden="true"></i> Upload payment slip
                </a>
            @endif
            <a href="{{ route('child.schedule.index') }}" class="btn-outline-teal child-enrollment-detail__btn">
                <i class="fa-solid fa-calendar-week" aria-hidden="true"></i> View full schedule
            </a>
            <a href="{{ route('child.payments') }}" class="btn-outline-teal child-enrollment-detail__btn">
                <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Payment history
            </a>
        </div>
    </div>
</article>
