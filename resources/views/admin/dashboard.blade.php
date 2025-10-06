@extends('layouts.dashboard-layout')

@section('title', 'Admin Dashboard | AutoRank')

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
                <div id="rankDistributionChart" data-chart-data='{{ json_encode($rankDistribution) }}'></div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="{{ asset('js/dashboard-scripts.js') }}"></script>
@endpush