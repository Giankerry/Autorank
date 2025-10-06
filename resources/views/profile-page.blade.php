@extends('layouts.profile-page-layout')

@section('title')
@if ($isOwnProfile)
Profile | Autorank
@else
{{ $user->name }}'s Profile | Autorank
@endif
@endsection

@section('content')

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

                <div id="stats-container-data" class="stats-container" style="display: flex;"
                    data-theme="{{ $theme ?? 'light' }}"
                    data-primary-color="{{ $primaryColor }}"
                    data-chart-data='{{ json_encode($chartData ?? null) }}'>
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
<script src="{{ asset('js/profile-page-chart-scripts.js') }}"></script>
@endpush
@endsection
