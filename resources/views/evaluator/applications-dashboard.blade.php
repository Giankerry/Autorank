@extends('layouts.view-all-layout')

@section('title', 'Evaluate Applications | Autorank')

@section('content')

@push('styles')
<style>
.status-badge {
    padding: 0.25em 0.6em;
    border-radius: 1em;
    font-size: 0.8rem;
    font-weight: 500;
    color: #fff;
    text-transform: capitalize;
    word-wrap: unset;
}

.status-pending-evaluation {
    background-color: #FFC107;
    color: #272727;
}

.status-evaluated {
    background-color: #4CAF50;
}
</style>
@endpush

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

<div class="header">
    <h1>Applications for Evaluation</h1>
    <div class="criterion-selector">
        <select id="status-filter" name="status">
            <option value="all" {{ request('status', 'all') == 'all' ? 'selected' : '' }}>All Applications</option>
            <option value="pending evaluation" {{ request('status') == 'pending evaluation' ? 'selected' : '' }}>Pending Evaluation</option>
            <option value="evaluated" {{ request('status') == 'evaluated' ? 'selected' : '' }}>Evaluated</option>
        </select>
    </div>
</div>

<div class="performance-metric-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Applicant Name</th>
                <th>Current Rank</th>
                <th>Date Submitted</th>
                <th>Status</th>
                <th>
                    <div class="search-bar-container">
                        <form id="search-form" action="{{ route('evaluator.applications.dashboard') }}" method="GET">
                            <input type="text" name="search" placeholder="Search by name..." value="{{ request('search') }}">
                            <button type="submit" id="search-btn"><i class="fa-solid fa-magnifying-glass"></i></button>
                            <button type="button" id="clear-search-btn" style="display: none;"><i class="fa-solid fa-times"></i></button>
                        </form>
                    </div>
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse($applications as $application)
                @include('partials._application_table_row', ['application' => $application])
            @empty
            <tr id="no-results-row">
                <td colspan="6" style="text-align: center;">No applications are currently pending evaluation.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="load-more-container">
        <button id="load-more-btn"
                data-current-offset="{{ $perPage }}"
                style="{{ $initialHasMore ? '' : 'display: none;' }}">
            Load More +
        </button>
    </div>
</div>

@endsection

@push('page-scripts')
<script src="{{ asset('js/evaluation-scripts.js') }}"></script>
@endpush

