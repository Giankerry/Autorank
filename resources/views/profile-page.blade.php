@extends('layouts.profile-page-layout')

@section('title')
@if ($isOwnProfile)
Profile | Autorank
@else
{{ $user->name }}'s Profile | Autorank
@endif
@endsection

@section('content')

@push('styles')
<style>
#highest-attainable-rank {
    background-color: hsl(var(--base-hue-primary), var(--base-saturation-primary), calc(var(--base-lightness-primary) + 15%));
    border-radius: 5px;
    width: 100%;
    height: auto;
    font-size: .9rem;
    font-family: var(--mainFont);
    font-weight: 500;
    padding: 10px;
    color: white;
}

#highest-attainable-rank h3 {
    margin-bottom: 7px;
}

.criterion-selector {
  position: relative;
  display: inline-block;
}

.criterion-selector select {
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;

  background-color: hsl(var(--base-hue-primary), var(--base-saturation-primary), calc(var(--base-lightness-primary) + 15%));;
  color: #fff;
  font-size: 16px;
  font-weight: 600;
  padding: 10px 40px 10px 20px;

  border: none;
  border-radius: 6px;
  cursor: pointer;
  text-align-last: center;
}

.criterion-selector::after {
  content: "▼";
  position: absolute;
  right: 15px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 12px;
  color: #fff;
  pointer-events: none;
}

.stats-container {
    width: 100%;
    height: auto;
    display: flex;
    gap: 20px;
}
</style>
@endpush

<div class="content-container">
    <div class="content">
        <div class="content-left-side">
            <div class="profile-img-container">
                <img src="{{ $user->avatar }}" alt="{{ $user->name }}'s profile picture">
            </div>
            <div class="separator-container">
                <h6>Basic Info</h6>
                <hr>
            </div>
            <div class="basic-info-container">
                <div id="copyToast" class="toast-container"><p>Copied to clipboard!</p></div>
                @if($application)
                    <div id="highest-attainable-rank">
                        <h3>Attainable Rank Range</h3>
                        <h5 style="color: #a6a8ad;" id="rank-range-text">{{ $application->highest_attainable_rank ?? 'No evaluation data available yet.'}}</h5>
                    </div>
                @endif
                <div class="basic-info-fields">
                    <div class="basic-info">
                        <h3>Full Name</h3>
                        <h5 id="username">{{ $user->name }}</h5>
                    </div>
                </div>
                <div class="basic-info-fields">
                    <div class="basic-info">
                        <h3>Email</h3>
                        <h5>{{ $user->email }}</h5>
                    </div>
                    @if (!$isOwnProfile)
                    <div class="basic-info-action">
                        <a href="mailto:{{ $user->email }}" title="Email Instructor">
                            <i class="fa-regular fa-envelope"></i>
                        </a>
                    </div>
                    @endif
                </div>
                <div class="basic-info-fields">
                    <div class="basic-info">
                        <h3>Faculty Rank</h3>
                        <h5>{{ $user->faculty_rank ?? 'Unset (Please reach out to an Admin.)' }}</h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="content-right-side">
            <div class="credentials-and-experience-container">
                <div class="title">
                    <h1>Profile</h1>
                    <div class="criterion-selector">
                        <select id="application-filter" name="application">
                            @if($allApplications->isNotEmpty())
                                @foreach($allApplications as $app)
                                    <option value="{{ $app->id }}" {{ $application && $app->id == $application->id ? 'selected' : '' }}>
                                        Evaluation from {{ $app->created_at->format('M d, Y') }}
                                    </option>
                                @endforeach
                            @else
                                <option disabled selected>No applications found</option>
                            @endif
                        </select>
                    </div>
                </div>

                <div class="stats-container" style="display: flex;">
                    <div style="flex: 2;" id="kraBreakdownChart"></div>
                    <div style="flex: 1;" id="kraContributionChart"></div>
                </div>

                <div class="progress-bars-container">
                    <div class="evaluation-progress-container">
                        <div class="subtitle">
                            <h4>Evaluation Progress</h4>
                            <h5 id="evaluation-status-text">{{ $evaluationStatus }}</h5>
                        </div>
                        <div class="evaluation-progress-bar-container">
                            <div id="evaluation-progress-bar" class="evaluation-progress" style="width: {{ $evaluationProgress }}%;"></div>
                            <div class="bar"></div>
                        </div>
                        <div class="evaluation-progress-bottom-note">
                            <h5 id="evaluation-progress-text">{{ $evaluationProgress }}%</h5>
                        </div>
                    </div>
                </div>

                @if ($isOwnProfile)
                <div class="apply-for-reranking-container">
                    @if ($hasSubmittedApplication)
                        <p style="font-size: .9rem; color: #6B7280;">You have already submitted an application for the current evaluation cycle. You cannot submit another until the cycle ends.</p>
                    @else
                        <button id="start-evaluation-btn"
                        data-check-url="{{ route('instructor.evaluation.check') }}">
                            Start CCE Evaluation Process
                        </button>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<form id="submit-evaluation-form" action="{{ route('instructor.evaluation.submit') }}" method="POST" style="display: none;">
    @csrf
</form>

@push('page-scripts')
<script src="{{ asset('js/modal-scripts.js') }}"></script>
<script src="{{ asset('js/profile-page-scripts.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let donutChart = null;
    let barChart = null;

    function adjustColor(hex, percent) {
        let color = hex.startsWith('#') ? hex.slice(1) : hex;
        if (color.length === 3) color = color.split('').map(char => char + char).join('');
        const num = parseInt(color, 16);
        const r = (num >> 16), g = ((num >> 8) & 0x00FF), b = (num & 0x0000FF);
        const newR = Math.min(255, Math.floor(r + (255 - r) * (percent / 100)));
        const newG = Math.min(255, Math.floor(g + (255 - g) * (percent / 100)));
        const newB = Math.min(255, Math.floor(b + (255 - b) * (percent / 100)));
        return `#${(1 << 24 | newR << 16 | newG << 8 | newB).toString(16).slice(1).toUpperCase()}`;
    }

    const userTheme = @json($theme ?? 'light');
    const isDarkMode = userTheme === 'dark';
    const primaryColorFromPHP = @json($primaryColor);
    const baseMonochromeColor = primaryColorFromPHP || '#262626';
    const fontFamily = "Rubik";
    const noDataMessage = { text: 'No evaluation data available yet.', align: 'center', verticalAlign: 'middle', style: { color: '#6B7280', fontSize: '14px', fontFamily: fontFamily } };

    const initialChartData = @json($chartData ?? null);
    const hasInitialData = initialChartData !== null;
    let initialDonutSeries = [], initialBarSeries = [];
    const maxScores = [40, 100, 100, 100];
    const labels = ['KRA I', 'KRA II', 'KRA III', 'KRA IV'];

    if (hasInitialData) {
        const rawScores = [ initialChartData.kra1_capped, initialChartData.kra2_capped, initialChartData.kra3_capped, initialChartData.kra4_capped ];
        const cappedScores = rawScores.map(score => parseFloat(score) || 0);
        initialDonutSeries = cappedScores;
        initialBarSeries = [ { name: 'Score Achieved', data: cappedScores }, { name: 'Max Possible Score', data: maxScores } ];
    }

    const donutColors = [ baseMonochromeColor, adjustColor(baseMonochromeColor, 20), adjustColor(baseMonochromeColor, 40), adjustColor(baseMonochromeColor, 60) ];
    const barColors = [baseMonochromeColor, '#E9ECEF'];

    const donutEl = document.querySelector("#kraContributionChart");
    if (donutEl) {
        const donutOptions = {
            series: initialDonutSeries,
            chart: { type: 'donut', height: 350, fontFamily: fontFamily, toolbar: { show: false } },
            labels: labels,
            colors: donutColors,
            noData: noDataMessage,
            legend: {
                position: 'bottom',
                labels: {
                    colors: isDarkMode ? '#ffffff' : '#000000'
                }
            },
            title: {
                text: 'Score Contribution',
                align: 'center',
                style: {
                    fontSize: '18px',
                    fontWeight: '600',
                    color: isDarkMode ? '#ffffff' : '#000000'
                }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'CCE Score',
                                fontSize: '22px',
                                fontWeight: 'bold',
                                color: isDarkMode ? '#ffffff' : '#000000',
                                formatter: (w) => hasInitialData ? w.globals.seriesTotals.reduce((a, b) => a + b, 0).toFixed(2) : '0.00'
                            },
                            value: {
                                show: true,
                                fontSize: '20px',
                                fontWeight: 600,
                                color: isDarkMode ? '#ffffff' : '#000000'
                            }
                        }
                    }
                }
            },
            stroke: {
                show: true,
                width: 1,
                colors: [isDarkMode ? 'transparent' : '#FFFFFF']
            },
            dataLabels: { enabled: hasInitialData, formatter: (val) => `${val.toFixed(1)}%` },
            tooltip: { y: { formatter: (val) => `${val.toFixed(2)} pts` } },
            responsive: [{ breakpoint: 768, options: { chart: { width: '100%' }, legend: { position: 'bottom' } } }]
        };
        donutChart = new ApexCharts(donutEl, donutOptions);
        donutChart.render();
    }

    const barEl = document.querySelector("#kraBreakdownChart");
    if (barEl) {
        const textColor = isDarkMode ? '#ffffff' : '#000000';
        const barOptions = {
            series: initialBarSeries,
            chart: { type: 'bar', height: 350, fontFamily: fontFamily, toolbar: { show: false } },
            noData: noDataMessage,
            plotOptions: { bar: { horizontal: false, borderRadius: 4, dataLabels: { position: 'top' } } },
            dataLabels: { enabled: hasInitialData, offsetX: 25, style: { fontSize: '12px', colors: [textColor] }, formatter: (val) => val.toFixed(2) },
            stroke: { show: false },
            xaxis: { categories: labels, title: { text: 'Points', style: { color: textColor, fontWeight: 550, } }, labels: { style: { colors: textColor } } },
            yaxis: { labels: { style: { colors: textColor } } },
            grid: { borderColor: isDarkMode ? '#434343' : '#f1f1f1' },
            colors: barColors,
            title: { text: 'Score Breakdown (vs. Max)', align: 'center', style: { fontSize: '16px', fontWeight: '600', color: textColor } },
            legend: { position: 'bottom', labels: { colors: textColor } },
            tooltip: {
                enabled: hasInitialData,
                shared: true,
                intersect: false,
                fillSeriesColor: false,
                custom: function({ series, seriesIndex, dataPointIndex, w }) {
                    const seriesNames = w.globals.seriesNames || [];
                    const categories = w.globals.labels || w.globals.categoryLabels || [];
                    const xLabel = categories[dataPointIndex] ?? '';
                    const bgColor = '#fff';
                    const textColor = '#222';
                    const headerBg = '#f1f3f4';
                    let html = `<div style="border-radius:8px; overflow:hidden; border:1px solid rgba(0,0,0,0.08); box-shadow:0 6px 18px rgba(0,0,0,0.12); width:auto; min-width:180px; background:${bgColor};">`;
                    html += `<div style="background:${headerBg}; padding:8px 12px; color:${textColor}; font-weight:600; font-size:13px;">${xLabel}</div>`;
                    html += `<div style="padding:8px 10px;">`;
                    for (let i = 0; i < series.length; i++) {
                        const rowValue = (series[i] && series[i][dataPointIndex] !== undefined) ? series[i][dataPointIndex] : '';
                        const color = (w.globals.colors && w.globals.colors[i]) ? w.globals.colors[i] : '#000';
                        html += `<div style="display:flex;align-items:center;padding:8px 0;border-top:0;">`;
                        html += `<span style="width:12px;height:12px;border-radius:50%;background:${color};display:inline-block;margin-right:10px;box-shadow:0 0 0 2px rgba(0,0,0,0.03) inset;"></span>`;
                        html += `<span style="flex:1;color:${textColor};font-size:13px;">${seriesNames[i] || 'Series ' + (i+1)}:</span>`;
                        html += `<span style="font-weight:500;color:${textColor};font-size:13px;margin-left:8px;">${rowValue}</span>`;
                        html += `</div>`;
                    }
                    html += `</div></div>`;
                    return html;
                }
            }
        };
        barChart = new ApexCharts(barEl, barOptions);
        barChart.render();
    }

    // --- AJAX Switcher Logic ---
    const applicationFilter = document.getElementById('application-filter');
    if (applicationFilter) {
        applicationFilter.addEventListener('change', function() {
            const selectedApplicationId = this.value;
            if (!selectedApplicationId) return;

            const apiUrl = `/application-data/${selectedApplicationId}`;
            document.body.style.cursor = 'wait';

            fetch(apiUrl)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    const rankRangeContainer = document.getElementById('highest-attainable-rank');
                    if (data.status === 'evaluated') {
                        document.getElementById('rank-range-text').textContent = data.highestAttainableRank;
                        rankRangeContainer.style.display = 'block';
                    } else {
                        rankRangeContainer.style.display = 'none';
                    }
                    document.getElementById('evaluation-status-text').textContent = data.evaluationStatus;
                    document.getElementById('evaluation-progress-bar').style.width = data.evaluationProgress + '%';
                    document.getElementById('evaluation-progress-text').textContent = data.evaluationProgress + '%';

                    const newChartData = data.chartData;
                    const hasNewData = newChartData !== null;
                    const newCappedScores = hasNewData
                        ? [ newChartData.kra1_capped, newChartData.kra2_capped, newChartData.kra3_capped, newChartData.kra4_capped ].map(s => parseFloat(s) || 0)
                        : [];

                    if (donutChart) {
                        donutChart.updateOptions({ dataLabels: { enabled: hasNewData } });
                        donutChart.updateSeries(newCappedScores);
                    }
                    if (barChart) {
                        barChart.updateOptions({ tooltip: { enabled: hasNewData } });
                        const newBarSeries = hasNewData ? [ { data: newCappedScores }, { data: maxScores } ] : [];
                        barChart.updateSeries(newBarSeries);

                    }

                    const currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('application_id', selectedApplicationId);
                    history.pushState({}, '', currentUrl.toString());
                })
                .catch(error => {
                    console.error('Error fetching application data:', error);
                })
                .finally(() => {
                    document.body.style.cursor = 'default';
                });
        });
    }
});
</script>
@endpush
@endsection