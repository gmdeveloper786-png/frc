@php
    $monthLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    $fillMonths = static function (array $byMonth): array {
        $out = array_fill(0, 12, 0);
        foreach ($byMonth as $month => $value) {
            $out[(int) $month - 1] = is_numeric($value) ? (float) $value : 0;
        }
        return $out;
    };
    $a = $stats['chart_analytics'] ?? [];
    $chartPayload = [
        'feePaid' => (float) ($stats['fee_total_paid'] ?? 0),
        'feePending' => (float) ($stats['fee_pending_overdue'] ?? 0),
        'cash' => (float) ($a['payment_channels']['cash'] ?? 0),
        'online' => (float) ($a['payment_channels']['online'] ?? 0),
        'paidMonthly' => $fillMonths($stats['monthly_revenue'] ?? []),
        'expectedMonthly' => $fillMonths($stats['monthly_expected'] ?? []),
        'enrollmentsMonthly' => $fillMonths($stats['monthly_enrollments'] ?? []),
        'registrationsMonthly' => $fillMonths($a['monthly_child_registrations'] ?? []),
        'childrenStatus' => $a['children_by_status'] ?? [],
        'enrollmentsStatus' => $a['enrollments_by_status'] ?? [],
        'branchCollected' => $a['branch_collected'] ?? [],
        'servicePopularity' => $a['service_popularity'] ?? [],
        'operationalAlerts' => $a['operational_alerts'] ?? [],
    ];
@endphp
<script nonce="{{ $cspNonce }}" src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script nonce="{{ $cspNonce }}">
(function () {
    const months = @json($monthLabels);
    const data = @json($chartPayload);
    const pkr = (v) => 'PKR ' + Number(v).toLocaleString('en-PK', { maximumFractionDigits: 0 });
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = '#6b7c93';

    const doughnutCenterPlugin = {
        id: 'frcDoughnutCenter',
        afterDraw(chart) {
            const text = chart.$centerText;
            if (!text || !chart.chartArea) return;
            const { ctx, chartArea } = chart;
            const x = (chartArea.left + chartArea.right) / 2;
            const y = (chartArea.top + chartArea.bottom) / 2;
            const holeR = Math.min(chartArea.width, chartArea.height) * 0.24;

            ctx.save();
            ctx.beginPath();
            ctx.arc(x, y, holeR, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(255, 255, 255, 0.97)';
            ctx.fill();

            let label = String(text.label || '');
            if (label.length > 14) label = label.slice(0, 13) + '…';

            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillStyle = '#6b7c93';
            ctx.font = '500 10px Inter, sans-serif';
            ctx.fillText(label, x, y - 10);
            ctx.fillStyle = '#11517c';
            ctx.font = '700 17px Poppins, sans-serif';
            ctx.fillText(String(text.value), x, y + 9);
            ctx.restore();
        },
    };
    if (!Chart.registry.plugins.get('frcDoughnutCenter')) {
        Chart.register(doughnutCenterPlugin);
    }

    const donutOpts = (cutout = '68%') => ({
        responsive: true,
        maintainAspectRatio: false,
        cutout,
        plugins: { legend: { display: false }, tooltip: { enabled: false } },
    });

    const el = (id) => document.getElementById(id);

    if (el('feeOverviewChart')) new Chart(el('feeOverviewChart'), {
        type: 'doughnut',
        data: {
            labels: ['Total Paid', 'Pending'],
            datasets: [{
                data: [data.feePaid, data.feePending],
                backgroundColor: ['#28a745', '#dc3545'],
                borderColor: ['#fff', '#fff'],
                borderWidth: 3,
                hoverOffset: 6,
            }],
        },
        options: donutOpts('72%'),
    });

    if (el('monthlyFinanceChart')) new Chart(el('monthlyFinanceChart'), {
        type: 'bar',
        data: {
            labels: months,
            datasets: [
                {
                    label: 'Collected',
                    data: data.paidMonthly,
                    backgroundColor: 'rgba(40, 167, 69, 0.88)',
                    borderRadius: 6,
                    maxBarThickness: 26,
                },
                {
                    label: 'Enrolled fees',
                    data: data.expectedMonthly,
                    backgroundColor: 'rgba(17, 81, 124, 0.78)',
                    borderRadius: 6,
                    maxBarThickness: 26,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: (c) => ' ' + c.dataset.label + ': ' + pkr(c.parsed.y) } },
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                y: {
                    beginAtZero: true,
                    grid: { color: '#eef2f7' },
                    ticks: { callback: (v) => v >= 1000 ? (v / 1000) + 'k' : v },
                },
            },
        },
    });

    if (el('growthTrendsChart')) {
        new Chart(el('growthTrendsChart'), {
            type: 'line',
            data: {
                labels: months,
                datasets: [
                    {
                        label: 'Enrollments',
                        data: data.enrollmentsMonthly,
                        borderColor: '#16acac',
                        backgroundColor: 'rgba(22, 172, 172, 0.12)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                    },
                    {
                        label: 'New children',
                        data: data.registrationsMonthly,
                        borderColor: '#11517c',
                        backgroundColor: 'rgba(17, 81, 124, 0.08)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, ticks: { stepSize: 1 } },
                },
            },
        });
    }

    if (el('paymentMethodsChart')) {
        const payTotal = (data.cash || 0) + (data.online || 0);
        const payChart = new Chart(el('paymentMethodsChart'), {
            type: 'doughnut',
            data: {
                labels: ['Cash', 'Online / Bank'],
                datasets: [{
                    data: [data.cash, data.online],
                    backgroundColor: ['#16acac', '#11517c'],
                    borderColor: ['#fff', '#fff'],
                    borderWidth: 2,
                    hoverOffset: 4,
                }],
            },
            options: {
                ...donutOpts('68%'),
                layout: { padding: 2 },
            },
        });
        payChart.$centerText = {
            label: 'Received',
            value: payTotal >= 1000 ? Math.round(payTotal / 1000) + 'k' : String(payTotal),
        };
        payChart.update('none');
    }

    const updateChartCenter = (chart, label, value) => {
        chart.$centerText = { label, value: String(value) };
        chart.update('none');
    };

    const sliceChart = (id, slices, centerId = null, totalLabel = 'Total', compact = false) => {
        if (!el(id) || !slices.length) return;
        const total = slices.reduce((sum, s) => sum + s.value, 0);
        const chart = new Chart(el(id), {
            type: 'doughnut',
            data: {
                labels: slices.map((s) => s.label),
                datasets: [{
                    data: slices.map((s) => s.value),
                    backgroundColor: slices.map((s) => s.color),
                    borderColor: '#fff',
                    borderWidth: 2,
                    hoverOffset: 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: centerId ? '72%' : '58%',
                layout: compact ? { padding: 2 } : {},
                onHover: (evt, elements) => {
                    el(id).style.cursor = elements.length ? 'pointer' : 'default';
                    if (!centerId) return;
                    if (!elements.length) {
                        updateChartCenter(chart, totalLabel, total);
                        return;
                    }
                    const i = elements[0].index;
                    updateChartCenter(chart, chart.data.labels[i], chart.data.datasets[0].data[i]);
                },
                onClick: (evt, elements) => {
                    if (!centerId || !elements.length) {
                        if (centerId) updateChartCenter(chart, totalLabel, total);
                        return;
                    }
                    const i = elements[0].index;
                    updateChartCenter(chart, chart.data.labels[i], chart.data.datasets[0].data[i]);
                },
                plugins: {
                    legend: {
                        display: !compact,
                        position: 'bottom',
                        labels: {
                            boxWidth: 10,
                            padding: 12,
                            font: { size: 10 },
                            generateLabels(chart) {
                                const ds = chart.data.datasets[0];
                                return chart.data.labels.map((label, i) => ({
                                    text: `${label}: ${ds.data[i]}`,
                                    fillStyle: ds.backgroundColor[i],
                                    strokeStyle: '#fff',
                                    lineWidth: 1,
                                    hidden: false,
                                    index: i,
                                }));
                            },
                        },
                        onClick: (evt, legendItem, legend) => {
                            const c = legend.chart;
                            const i = legendItem.index;
                            if (centerId) {
                                updateChartCenter(c, c.data.labels[i], c.data.datasets[0].data[i]);
                            }
                        },
                    },
                    tooltip: { enabled: false },
                },
            },
        });
        if (centerId) {
            chart.$centerText = { label: totalLabel, value: String(total) };
            chart.update('none');
            el(id).addEventListener('mouseleave', () => updateChartCenter(chart, totalLabel, total));
        }
        return chart;
    };

    sliceChart('childrenStatusChart', data.childrenStatus, 'childrenStatusCenter', 'Total children', true);
    sliceChart('enrollmentsStatusChart', data.enrollmentsStatus, 'enrollmentsStatusCenter', 'Total enrollments');

    const verticalBar = (id, items, valueLabel, isMoney = false) => {
        if (!el(id) || !items.length) return;
        const colors = items.map((_, i) => (i % 2 === 0 ? 'rgba(22, 172, 172, 0.9)' : 'rgba(17, 81, 124, 0.82)'));
        new Chart(el(id), {
            type: 'bar',
            data: {
                labels: items.map((i) => i.label),
                datasets: [{
                    label: valueLabel,
                    data: items.map((i) => i.value),
                    backgroundColor: colors,
                    borderRadius: { topLeft: 8, topRight: 8, bottomLeft: 0, bottomRight: 0 },
                    borderSkipped: false,
                    maxBarThickness: items.length <= 3 ? 56 : 40,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: { top: 8, bottom: 4 } },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (c) => isMoney ? ' ' + pkr(c.parsed.y) : ' Count: ' + c.parsed.y,
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { size: 11 },
                            maxRotation: 45,
                            minRotation: 0,
                            autoSkip: false,
                            callback(value, index) {
                                const label = items[index]?.label ?? value;
                                return label.length > 14 ? label.slice(0, 13) + '…' : label;
                            },
                        },
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#eef2f7' },
                        ticks: {
                            font: { size: 11 },
                            callback: isMoney
                                ? (v) => (v >= 1000 ? (v / 1000) + 'k' : v)
                                : (v) => Number.isInteger(v) ? v : v,
                        },
                    },
                },
            },
        });
    };

    verticalBar('branchCollectedChart', data.branchCollected, 'Collected', true);
    verticalBar('servicePopularityChart', data.servicePopularity, 'Enrollments', false);

    if (el('collectionRateChart')) {
        const rateData = data.paidMonthly.map((paid, i) => {
            const exp = data.expectedMonthly[i];
            return exp > 0 ? Math.round((paid / exp) * 100) : 0;
        });
        new Chart(el('collectionRateChart'), {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'Collection %',
                    data: rateData,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.15)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 3,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: { callback: (v) => v + '%' },
                    },
                },
            },
        });
    }

    if (el('operationalAlertsChart') && data.operationalAlerts.length) {
        new Chart(el('operationalAlertsChart'), {
            type: 'bar',
            data: {
                labels: data.operationalAlerts.map((a) => a.label),
                datasets: [{
                    data: data.operationalAlerts.map((a) => a.value),
                    backgroundColor: data.operationalAlerts.map((a) => a.color),
                    borderRadius: 8,
                    maxBarThickness: 40,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                    y: { beginAtZero: true, ticks: { stepSize: 1 } },
                },
            },
        });
    }
})();
</script>
