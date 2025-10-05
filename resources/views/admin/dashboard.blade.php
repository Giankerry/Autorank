@extends('layouts.dashboard-layout')

@section('title', 'Admin Dashboard | AutoRank')

@push('styles')
<style>
    /*
    |--------------------------------------------------------------------------
    | Main Page Layout & Panels (Adapted from Modeler)
    |--------------------------------------------------------------------------
    */
    .main-content-container {
        width: 95%;
        min-height: calc(100vh - 80px);
        display: flex;
        justify-self: center;
        justify-content: center;
        align-items: flex-start;
        padding-bottom: 2rem;
    }

    .dashboard-container {
        width: 100%;
        display: grid;
        grid-template-columns: 1fr;
        grid-template-areas:
            "left"
            "right";
        gap: 2rem;
        font-family: var(--mainFont);
    }

    @media (min-width: 1024px) {
        .dashboard-container {
            grid-template-columns: 1fr 2fr; 
            grid-template-areas: "left right";
        }
    }

    .dashboard-panel-left {
        grid-area: left;
        background-color: var(--tableBackgroundColor);
        border-radius: 0.5rem;
        padding: 1.5rem;
        height: fit-content;
    }

    .dashboard-panel-right {
        grid-area: right;
        background-color: var(--tableBackgroundColor);
        border-radius: 0.5rem;
        padding: 1.5rem;
    }

    /*
    |--------------------------------------------------------------------------
    | Typography & Section Headers
    |--------------------------------------------------------------------------
    */
    .dashboard-panel-left h2, .dashboard-panel-right h2 {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--pageTextColorOnBlack);
        margin-bottom: 0.5rem;
    }
    .dashboard-panel-left .subtitle, .dashboard-panel-right .subtitle {
        color: var(--pageTextColorOnBlack);
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--tableDataBorderColor);
    }
    .results-section h3 {
        font-size: 1.125rem;
        font-weight: 600;
        color: var(--pageTextColorOnBlack);
        margin-bottom: 1rem;
    }

    /*
    |--------------------------------------------------------------------------
    | KPI Cards Styling
    |--------------------------------------------------------------------------
    */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(1, 1fr);
        gap: 1rem;
        margin-bottom: 2.5rem;
    }
    @media (min-width: 768px) {
        .kpi-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (min-width: 1024px) {
        .kpi-grid { grid-template-columns: repeat(1, 1fr); }
    }
     @media (min-width: 1280px) {
        .kpi-grid { grid-template-columns: repeat(2, 1fr); }
    }

    .kpi-card {
        padding: 1rem;
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .kpi-card-label {
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--pageTextColorOnBlack);
    }
    .kpi-card-value {
        margin-top: 0.25rem;
        font-size: 1.875rem;
        font-weight: 600;
        color: var(--pageTextColorOnBlack);
    }
    .kpi-card-icon {
        padding: 0.75rem;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .kpi-card-icon svg {
        height: 1.5rem;
        width: 1.5rem;
    }

    /*
    |--------------------------------------------------------------------------
    | Recent Activity List Styling
    |--------------------------------------------------------------------------
    */
    .activity-feed {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .activity-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--tableDataBorderColor);
    }
    .activity-feed .activity-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    .activity-avatar img {
        height: 2.5rem;
        width: 2.5rem;
        border-radius: 9999px;
    }
    .activity-content {
        flex: 1;
        min-width: 0;
    }
    .activity-content p {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .activity-name {
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--pageTextColorOnBlack);
    }
    .activity-details, .activity-time {
        font-size: 0.75rem;
        color: var(--pageTextColorOnBlack);
    }
    .activity-time {
        text-align: right;
        white-space: nowrap;
    }

</style>
@endpush

@section('content')
@if(session('success'))
<div class="server-alert-success">
    {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="server-alert-danger">
    {{ session('error') }}
</div>
@endif

<div class="main-content-container">
    <div class="dashboard-container">

        <div class="dashboard-panel-left">
            <h2>Overview</h2>
            <p class="subtitle">At-a-glance metrics and recent activity.</p>

            <div class="kpi-grid">
                <div class="kpi-card">
                    <div>
                        <p class="kpi-card-label">Pending Applications</p>
                        <p class="kpi-card-value">{{ $pendingCount }}</p>
                    </div>
                    <div class="kpi-card-icon" style="background-color: #fff7ed;">
                        <svg class="h-6 w-6" style="color: #f97316;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>

                <div class="kpi-card">
                    <div>
                        <p class="kpi-card-label">Completed Evaluations</p>
                        <p class="kpi-card-value">{{ $evaluatedCount }}</p>
                    </div>
                    <div class="kpi-card-icon" style="background-color: #f0fdf4;">
                        <svg class="h-6 w-6" style="color: #22c55e;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="results-section">
                <h3>Recent Activity</h3>
                <div class="activity-feed">
                    @forelse($recentEvaluations as $evaluation)
                        <div class="activity-item">
                            <div class="activity-avatar">
                                <img src="{{ $evaluation->user->avatar }}" alt="{{ $evaluation->user->name }}'s profile photo">
                            </div>
                            <div class="activity-content">
                                <p class="activity-name">{{ $evaluation->user->name }}</p>
                                <p class="activity-details">
                                    Score: {{ number_format($evaluation->final_score, 2) }} | {{ $evaluation->attainable_rank }}
                                </p>
                            </div>
                            <div class="activity-time">
                                {{ $evaluation->updated_at->diffForHumans() }}
                            </div>
                        </div>
                    @empty
                        <p class="text-center py-4" style="color: var(--pageTextColorOnBlack);">No recent evaluations found.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="dashboard-panel-right">
            <h2>Analytics</h2>
            <p class="subtitle">Visual overview of key institutional data.</p>

            <div class="results-section">
                <h3>Faculty Rank Distribution</h3>
                <div id="rankDistributionChart"></div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const rankData = @json($rankDistribution);
        const ranks = Object.keys(rankData);
        const counts = Object.values(rankData);

        const options = {
            series: [{
                name: 'Faculty Count',
                data: counts
            }],
            chart: {
                type: 'bar',
                height: 450,
                toolbar: { show: false },
                fontFamily: 'Inter, sans-serif'
            },
            colors: ['#3b82f6'],
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    borderRadius: 4
                }
            },
            dataLabels: { enabled: false },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent']
            },
            xaxis: {
                categories: ranks,
                labels: {
                    style: { colors: 'var(--pageTextColorOnBlack)' }
                }
            },
            yaxis: {
                title: {
                    text: 'Number of Faculty',
                    style: { color: 'var(--pageTextColorOnBlack)' }
                },
                labels: {
                    style: { colors: 'var(--pageTextColorOnBlack)' }
                }
            },
            fill: { opacity: 1 },
            grid: {
                borderColor: 'var(--tableDataBorderColor)'
            },
            legend: {
                show: false
            },
            tooltip: {
                y: {
                    formatter: (val) => `${val} faculty`
                }
            }
        };

        const chart = new ApexCharts(document.querySelector("#rankDistributionChart"), options);
        chart.render();
    });
</script>
@endpush