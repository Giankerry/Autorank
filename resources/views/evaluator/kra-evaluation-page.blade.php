@extends('layouts.view-all-layout')

@section('title', 'Evaluate KRA Submissions | Autorank')

@section('content')

@push('styles')
<style>
.header {
    display: flex;;
    flex-direction: column;
}

.header div {
    display: flex;;
    gap: 15px;
}

.header p {
    margin-top: -10px;
}

.header strong {
    font-weight: 550;
}
</style>
@endpush

<div class="header">
    <div>
        <div class="header-text">
            <h1>{{ $kra_title }}</h1>
        </div>
        <div class="criterion-selector">
            <select id="filter-select" name="filter">
                <option value="all" selected>All Statuses</option>
                <option value="unscored">Unscored</option>
                <option value="scored">Scored</option>
            </select>
        </div>
    </div>
    <p>Evaluating submissions for: <strong>{{ $application->user->name }}</strong></p>
</div>

<div class="performance-metric-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Category</th>
                <th>Date Uploaded</th>
                <th>Score</th>
                <th>
                    <div class="search-bar-container">
                        <form id="search-form" action="" method="GET">
                            <input type="text" name="search" placeholder="Search by title...">
                            <button type="submit" id="search-btn"><i class="fa-solid fa-magnifying-glass"></i></button>
                            <button type="button" id="clear-search-btn" style="display: none;"><i class="fa-solid fa-times"></i></button>
                        </form>
                    </div>
                </th>
            </tr>
        </thead>
        <tbody id="submissions-table-body">
            @forelse($submissions as $item)
                @include('partials._submission_table_row', ['item' => $item, 'kra_slug' => $kra_slug])
            @empty
                <tr id="no-results-row">
                    <td colspan="6" style="text-align: center;">No submissions found for this KRA.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="load-more-container">
         <a href="{{ route('evaluator.application.details', ['application' => $application->id]) }}" class="btn btn-secondary">
            <button>Back</button>
        </a>
        <button id="load-more-btn"
                data-current-offset="{{ $perPage }}"
                style="{{ $initialHasMore ? '' : 'display: none;' }}">
            Load More +
        </button>
    </div>
</div>

<div class="role-modal-container" id="scoring-modal" style="display: none;">
    <div class="role-modal">
        <div class="role-modal-navigation">
            <i class="fa-solid fa-xmark close-modal-btn" style="color: #ffffff;"></i>
        </div>
        <form id="score-modal-form">
            @csrf
            <input type="hidden" name="submission_id">
            <input type="hidden" name="kra_slug">

            {{-- Step 1: Enter Score --}}
            <div class="initial-step">
                <div class="role-modal-content">
                    <div class="role-modal-content-header">
                        <h1 id="scoring-modal-title">Set Score</h1>
                        <p>Enter the score for this submission below.</p>
                    </div>
                    <div class="role-modal-content-body">
                        <div class="form-group">
                            <label class="form-group-title" data-label="Score">Score *</label>
                            <input type="number" name="score" step="0.01" min="0" placeholder="0.00" required>
                        </div>
                        <div class="modal-messages mt-2"></div>
                    </div>
                </div>
                <div class="role-modal-actions">
                    <button type="button" class="proceed-btn">Proceed</button>
                </div>
            </div>

            {{-- Step 2: Confirmation --}}
            <div class="confirmation-step" style="display: none;">
                <div class="role-modal-content">
                    <div class="role-modal-content-header">
                        <h1>Confirm Score</h1>
                        <p class="confirmation-message-area"></p>
                    </div>
                    <div class="role-modal-content-body">
                        <div class="final-status-message-area mt-2"></div>
                    </div>
                </div>
                <div class="role-modal-actions">
                    <button type="button" class="back-btn">Back</button>
                    <button type="button" class="confirm-btn">Confirm & Save</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('page-scripts')
<script src="{{ asset('js/modal-scripts.js') }}"></script>
<script src="{{ asset('js/evaluation-scripts.js') }}"></script>
@endpush