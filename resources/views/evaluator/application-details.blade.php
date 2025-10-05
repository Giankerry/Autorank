@extends('layouts.view-all-layout')

@section('title', 'Application Details | Autorank')

@push('styles')
<style>
    .header-text strong {
        font-weight: 550;
    }

    .final-cce-document-score {
        font-size: 2.5rem;
        font-weight: 550;
        justify-self: center;
        text-align: center;
    }

    .submissions-total-count {
        font-size: 1.2rem;
    }

    .submissions-scored-count {
        font-size: .9rem;
    }
</style>
@endpush

@section('content')

<div class="header">
    <div class="header-text" >
        <h1>Applicant: {{ $application->user->name }}</h1>
        <p class="text-muted">Current Rank: <strong>{{ $application->user->faculty_rank ?? 'Not Set' }}</strong> | Submitted: <strong>{{ $application->created_at->format('F d, Y') }}</strong></p>
    </div>
</div>

{{-- Main container for the KRA summary --}}
<div class="performance-metric-container">
    <table>
        <thead>
            <tr>
                <th>Final CCE Document Score</th>
                <th>Key Result Area</th>
                <th>Submissions</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            {{-- KRA I: Instruction --}}
            @php
                $isInstructionComplete = $application->instructions_count > 0 && $application->instructions_scored_count == $application->instructions_count;
            @endphp
            <tr>
                <td rowspan="4">
                    @if($application->status == 'evaluated' && isset($application->final_score))
                        <div class="final-cce-document-score">
                            <span>{{ number_format($application->final_score, 2) }}</span>
                            <div class="dropdown">
                                <button id="details-button" type="button">i</button>
                                <div class="dropdown-content">
                                    <p>
                                        This score (max 340 pts) is based on CCE documents only.
                                        It must be combined with instructors externally-calculated QCE score
                                        (max 60 pts) and other criteria to determine their final official rank.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @else
                        <span style="font-size: 1rem; font-weight: 400; color: #6c757d;">Not yet calculated</span>
                    @endif
                </td>
                <td>KRA I: Instruction</td>
                <td>
                    ( <strong class="submissions-total-count" title="{{ $application->instructions_scored_count }}/{{ $application->instructions_count }}'s Submissions Scored">{{ $application->instructions_scored_count }} <span class="submissions-scored-count">/{{ $application->instructions_count }}</span></strong> ) Scored
                </td>
                <td>
                    @if($application->instructions_count > 0)
                        <a href="{{ route('evaluator.application.kra', ['application' => $application->id, 'kra_slug' => 'instruction']) }}" class="btn {{ $isInstructionComplete ? 'btn-secondary' : 'btn-primary' }}">
                            <button>{{ $isInstructionComplete ? 'View Submissions' : 'Score Submissions' }}</button>
                        </a>
                    @else
                        <button class="btn btn-secondary" disabled>No Submissions</button>
                    @endif
                </td>
            </tr>

            {{-- KRA II: Research --}}
            @php
                $isResearchComplete = $application->researches_count > 0 && $application->researches_scored_count == $application->researches_count;
            @endphp
            <tr>
                <td>KRA II: Research</td>
                <td>
                   ( <strong class="submissions-total-count" title="{{ $application->researches_scored_count }}/{{ $application->researches_count }}'s Submissions Scored">{{ $application->researches_scored_count }} <span class="submissions-scored-count">/{{ $application->researches_count }}</span></strong> ) Scored
                </td>
                <td>
                    @if($application->researches_count > 0)
                        <a href="{{ route('evaluator.application.kra', ['application' => $application->id, 'kra_slug' => 'research']) }}" class="btn {{ $isResearchComplete ? 'btn-secondary' : 'btn-primary' }}">
                               <button>{{ $isResearchComplete ? 'View Submissions' : 'Score Submissions' }}</button>
                        </a>
                    @else
                        <button class="btn btn-secondary" disabled>No Submissions</button>
                    @endif
                </td>
            </tr>

            {{-- KRA III: Extension --}}
            @php
                $isExtensionComplete = $application->extensions_count > 0 && $application->extensions_scored_count == $application->extensions_count;
            @endphp
            <tr>
                <td>KRA III: Extension</td>
                <td>
                    ( <strong class="submissions-total-count" title="{{ $application->extensions_scored_count }}/{{ $application->extensions_count }}'s Submissions Scored">{{ $application->extensions_scored_count }} <span class="submissions-scored-count">/{{ $application->extensions_count }}</span></strong> ) Scored
                </td>
                <td>
                    @if($application->extensions_count > 0)
                        <a href="{{ route('evaluator.application.kra', ['application' => $application->id, 'kra_slug' => 'extension']) }}" class="btn {{ $isExtensionComplete ? 'btn-secondary' : 'btn-primary' }}">
                            <button>{{ $isExtensionComplete ? 'View Submissions' : 'Score Submissions' }}</button>
                        </a>
                    @else
                        <button class="btn btn-secondary" disabled>No Submissions</button>
                    @endif
                </td>
            </tr>

            {{-- KRA IV: Professional Development --}}
            @php
                $isProfDevComplete = $application->professional_developments_count > 0 && $application->professional_developments_scored_count == $application->professional_developments_count;
            @endphp
            <tr>
                <td>KRA IV: Professional Development</td>
                <td>
                    ( <strong class="submissions-total-count" title="{{ $application->professional_developments_scored_count }}/{{ $application->professional_developments_count }}'s Submissions Scored">{{ $application->professional_developments_scored_count }} <span class="submissions-scored-count">/{{ $application->professional_developments_count }}</span></strong> ) Scored
                </td>
                <td>
                    @if($application->professional_developments_count > 0)
                        <a href="{{ route('evaluator.application.kra', ['application' => $application->id, 'kra_slug' => 'professional-development']) }}" class="btn {{ $isProfDevComplete ? 'btn-secondary' : 'btn-primary' }}">
                            <button>{{ $isProfDevComplete ? 'View Submissions' : 'Score Submissions' }}</button>
                        </a>
                    @else
                        <button class="btn btn-secondary" disabled>No Submissions</button>
                    @endif
                </td>
            </tr>
        </tbody>
    </table>
</div>

<div class="load-more-container">
    <a href="{{ route('evaluator.applications.dashboard') }}" class="btn btn-secondary"><button>Back</button></a>

    <div class="final-score-container">
        @if($application->status == 'evaluated')
            <div class="score-display">
                <h2><span>[&nbsp;&nbsp;&nbsp;{{ $application->highest_attainable_rank }}&nbsp;&nbsp;&nbsp;]</span></h2>
                <p>This is the <i>lowest</i> to <i>highest</i> rank attainable.</p>
            </div>
        @else
            <form id="calculate-score-form" method="POST" action="{{ route('evaluator.application.calculate-score', $application->id) }}">
                @csrf
            </form>
            <button id="calculate-score-btn" type="button" class="upload-new-button"
                    data-validate-url="{{ route('evaluator.application.validate-scores', $application->id) }}">
                Calculate CCE Score
            </button>   
        @endif
    </div>
</div>

@include('partials._action_modals')

@endsection

@push('page-scripts')
<script src="{{ asset('js/modal-scripts.js') }}"></script>
<script src="{{ asset('js/evaluation-scripts.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const calculateScoreBtn = document.getElementById('calculate-score-btn');

    if (calculateScoreBtn) {
        calculateScoreBtn.addEventListener('click', async function() {
            const validationUrl = this.dataset.validateUrl;
            const originalButtonText = this.textContent;
            
            this.textContent = 'Loading...';
            this.disabled = true;

            try {
                const response = await fetch(validationUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                });

                if (response.ok) {
                    showConfirmationModal({
                        title: 'Confirm Final Score Calculation',
                        body: 'Are you sure you want to finalize and calculate the score? This action cannot be undone.',
                        confirmText: 'Calculate Score',
                        onConfirm: () => {
                            document.getElementById('calculate-score-form').submit();
                        }
                    });
                } else {
                    const errorData = await response.json();
                    showConfirmationModal({
                        title: 'Incomplete Evaluation',
                        body: `<p>${errorData.message}</p>`,
                        confirmText: 'OK',
                        onConfirm: () => {
                            hideConfirmationModal();
                        }
                    });
                }
            } catch (error) {
                console.error('An error occurred during validation:', error);
                showConfirmationModal({
                    title: 'Error',
                    body: '<p>An unexpected error occurred while communicating with the server. Please try again.</p>',
                    confirmText: 'OK',
                    onConfirm: hideConfirmationModal
                });
            } finally {
                this.textContent = originalButtonText;
                this.disabled = false;
            }
        });
    }
});
</script>
@endpush