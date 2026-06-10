@php
    $chartId = $chartId ?? 'sessionPerformance';
    $chart = $chart ?? null;
    $chartsByKey = $chartsByKey ?? null;
    $filterOptions = $filterOptions ?? [];
    $defaultKey = $defaultKey ?? 'all';
    $showFilter = (bool) ($showFilter ?? false);
    $wrapperClass = $wrapperClass ?? 'session-performance-chart';
    $headingId = $headingId ?? $chartId . '-heading';
    $completedNoFeedbackMessage = $completedNoFeedbackMessage ?? 'Performance will appear once session feedback is submitted for completed sessions.';
    $noSessionsMessage = $noSessionsMessage ?? 'No completed sessions yet.';
    $canvasId = $chartId . 'Chart';
    $centerId = $chartId . 'Center';
    $noteId = $chartId . 'Note';
    $legendId = $chartId . 'Legend';
    $bodyId = $chartId . 'Body';
    $emptyId = $chartId . 'Empty';
    $filterId = $chartId . 'ServiceFilter';
    $initialChart = $chart ?? (($chartsByKey ?? [])[$defaultKey] ?? null);
    $initialHasData = (bool) ($initialChart['has_data'] ?? false);
    $initialCenterValue = $initialHasData ? (($initialChart['overall_percent'] ?? 0) . '%') : '0%';
    $initialCenterLabel = $initialHasData ? ($initialChart['overall_label'] ?? '') : 'No sessions yet';
    $initialWithFeedback = (int) ($initialChart['completed_with_feedback'] ?? 0);
    $initialCompleted = (int) ($initialChart['completed_sessions'] ?? 0);
    $initialNote = 'Based on ' . $initialWithFeedback . ' ' . ($initialWithFeedback === 1 ? 'session' : 'sessions') . ' with feedback';
    if ($initialCompleted > $initialWithFeedback) {
        $initialNote .= ' (' . $initialCompleted . ' completed in total)';
    }
    $initialEmptyMessage = $initialCompleted > 0 ? $completedNoFeedbackMessage : $noSessionsMessage;
@endphp
@if($chart !== null || ($chartsByKey !== null && $chartsByKey !== []))
    <section class="{{ $wrapperClass }}" aria-labelledby="{{ $headingId }}">
        <div class="session-performance-chart__head">
            <h3 id="{{ $headingId }}" class="session-performance-chart__title">
                <i class="fa-solid fa-chart-pie" aria-hidden="true"></i> Overall performance
            </h3>
            @if($showFilter && count($filterOptions) > 1)
                <div class="session-performance-chart__filter">
                    <label class="session-performance-chart__filter-label" for="{{ $filterId }}">Service</label>
                    <select id="{{ $filterId }}" class="form-select form-select-sm session-performance-chart__filter-select">
                        @foreach($filterOptions as $option)
                            <option value="{{ $option['key'] }}" @selected($option['key'] === $defaultKey)>{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        <p id="{{ $noteId }}" class="session-performance-chart__note small text-muted mb-3">{{ $initialNote }}</p>

        <div id="{{ $bodyId }}">
            <div class="row g-3 align-items-center">
                <div class="col-12 col-md-5 col-lg-4">
                    <div class="session-performance-chart__chart-wrap">
                        <canvas id="{{ $canvasId }}" aria-label="Session performance distribution" role="img"></canvas>
                        <div class="session-performance-chart__chart-center" id="{{ $centerId }}" aria-hidden="true">
                            <span class="session-performance-chart__chart-center-value">{{ $initialCenterValue }}</span>
                            <span class="session-performance-chart__chart-center-label">{{ $initialCenterLabel }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-7 col-lg-8">
                    <ul id="{{ $legendId }}" class="session-performance-chart__legend list-unstyled mb-0">
                        @foreach($initialChart['slices'] ?? [] as $slice)
                            <li class="session-performance-chart__legend-item{{ ($slice['count'] ?? 0) === 0 ? ' is-empty' : '' }}">
                                <span class="session-performance-chart__swatch" style="background-color:{{ $slice['color'] }};"></span>
                                <span class="session-performance-chart__legend-label">{{ $slice['label'] }}</span>
                                <span class="session-performance-chart__legend-count">
                                    {{ $slice['count'] }}
                                    {{ ($slice['count'] ?? 0) === 1 ? 'session' : 'sessions' }}
                                    @if(($slice['count'] ?? 0) > 0)
                                        <span class="text-muted">({{ frc_percent($slice['percent']) }}%)</span>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <p id="{{ $emptyId }}" class="session-performance-chart__empty small text-muted mt-3 mb-0" @if($initialHasData) hidden @endif>{{ $initialEmptyMessage }}</p>
    </section>

    @push('scripts')
        <script nonce="{{ $cspNonce }}" src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script nonce="{{ $cspNonce }}">
        (function () {
            const chartsByKey = @json($chartsByKey ?? [$defaultKey => $chart]);
            const defaultKey = @json($defaultKey);
            const canvas = document.getElementById(@json($canvasId));
            const center = document.getElementById(@json($centerId));
            const note = document.getElementById(@json($noteId));
            const body = document.getElementById(@json($bodyId));
            const empty = document.getElementById(@json($emptyId));
            const legend = document.getElementById(@json($legendId));
            const filter = document.getElementById(@json($filterId));
            const completedNoFeedbackMessage = @json($completedNoFeedbackMessage);
            const noSessionsMessage = @json($noSessionsMessage);

            if (!canvas || typeof Chart === 'undefined') return;

            let chartInstance = null;
            let activeKey = defaultKey;

            const escapeHtml = (value) => String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');

            const setCenter = (value, label) => {
                if (!center) return;
                center.querySelector('.session-performance-chart__chart-center-value').textContent = value;
                center.querySelector('.session-performance-chart__chart-center-label').textContent = label;
            };

            const renderLegend = (chartData) => {
                if (!legend) return;
                legend.innerHTML = (chartData.slices || []).map((slice) => {
                    const countLabel = slice.count === 1 ? 'session' : 'sessions';
                    const percentHtml = slice.count > 0
                        ? ` <span class="text-muted">(${escapeHtml(slice.percent)}%)</span>`
                        : '';
                    return `<li class="session-performance-chart__legend-item${slice.count === 0 ? ' is-empty' : ''}">`
                        + `<span class="session-performance-chart__swatch" style="background-color:${escapeHtml(slice.color)};"></span>`
                        + `<span class="session-performance-chart__legend-label">${escapeHtml(slice.label)}</span>`
                        + `<span class="session-performance-chart__legend-count">${slice.count} ${countLabel}${percentHtml}</span>`
                        + `</li>`;
                }).join('');
            };

            const fadeColor = (color) => String(color).replace(/(\d*\.?\d+)\)$/, '0.22)');

            const renderNote = (chartData) => {
                if (!note) return;
                const withFeedback = chartData.completed_with_feedback ?? 0;
                const completed = chartData.completed_sessions ?? 0;
                let text = `Based on ${withFeedback} `
                    + (withFeedback === 1 ? 'session' : 'sessions')
                    + ' with feedback';
                if (completed > withFeedback) {
                    text += ` (${completed} completed in total)`;
                }
                note.textContent = text;
                note.hidden = false;
            };

            const renderEmptyHint = (chartData) => {
                if (!empty) return;
                if (chartData.has_data) {
                    empty.hidden = true;
                    empty.textContent = '';
                    return;
                }
                empty.textContent = (chartData.completed_sessions ?? 0) > 0
                    ? completedNoFeedbackMessage
                    : noSessionsMessage;
                empty.hidden = false;
            };

            const destroyChart = () => {
                if (chartInstance) {
                    chartInstance.destroy();
                    chartInstance = null;
                }
            };

            const renderChart = (chartData) => {
                destroyChart();

                const allSlices = chartData.slices || [];
                const activeSlices = allSlices.filter((slice) => slice.count > 0);
                const isEmpty = !chartData.has_data || !activeSlices.length;
                const chartSlices = isEmpty ? allSlices : activeSlices;
                const defaultCenter = {
                    value: isEmpty ? '0%' : `${chartData.overall_percent ?? 0}%`,
                    label: isEmpty ? 'No sessions yet' : (chartData.overall_label || ''),
                };

                if (body) body.hidden = false;
                renderNote(chartData);
                renderLegend(chartData);
                renderEmptyHint(chartData);
                setCenter(defaultCenter.value, defaultCenter.label);

                if (!chartSlices.length) return;

                chartInstance = new Chart(canvas, {
                    type: 'doughnut',
                    data: {
                        labels: chartSlices.map((slice) => slice.label),
                        datasets: [{
                            data: isEmpty
                                ? chartSlices.map(() => 1)
                                : chartSlices.map((slice) => slice.count),
                            backgroundColor: isEmpty
                                ? chartSlices.map((slice) => fadeColor(slice.color))
                                : chartSlices.map((slice) => slice.color),
                            borderColor: '#fff',
                            borderWidth: 2,
                            hoverOffset: isEmpty ? 0 : 6,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '72%',
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label(context) {
                                        if (isEmpty) {
                                            return `${context.label}: 0 (0%)`;
                                        }
                                        const total = context.dataset.data.reduce((sum, value) => sum + value, 0);
                                        const value = context.parsed;
                                        const pct = total > 0 ? Math.round((value / total) * 100) : 0;
                                        return `${context.label}: ${value} (${pct}%)`;
                                    },
                                },
                            },
                        },
                        onHover(event, elements) {
                            canvas.style.cursor = elements.length ? 'pointer' : 'default';
                            if (!elements.length) {
                                setCenter(defaultCenter.value, defaultCenter.label);
                                return;
                            }
                            const index = elements[0].index;
                            if (isEmpty) {
                                setCenter('0%', '0 sessions');
                                return;
                            }
                            const count = chartInstance.data.datasets[0].data[index];
                            const total = chartInstance.data.datasets[0].data.reduce((sum, value) => sum + value, 0);
                            const pct = total > 0 ? Math.round((count / total) * 100) : 0;
                            const sessionWord = count === 1 ? 'session' : 'sessions';
                            setCenter(`${pct}%`, `${count} ${sessionWord}`);
                        },
                    },
                });

                canvas.onmouseleave = () => setCenter(defaultCenter.value, defaultCenter.label);
            };

            const applyKey = (key) => {
                activeKey = key in chartsByKey ? key : defaultKey;
                renderChart(chartsByKey[activeKey] || {});
            };

            if (filter) {
                filter.addEventListener('change', () => applyKey(filter.value));
            }

            applyKey(activeKey);
        })();
        </script>
    @endpush
@endif
