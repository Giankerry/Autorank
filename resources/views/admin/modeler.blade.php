@extends('layouts.dashboard-layout')

@section('title', 'Strategic Modeler | Autorank')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/modeler-styles.css') }}">
@endpush

@section('content')
<div class="main-content-container">
    <div class="modeler-container">

        <!-- Control Pod -->
        <div class="control-pod">
            <h2>Simulation Controls</h2>
            <p class="subtitle">Adjust institutional priorities and run a simulation.</p>

            <div class="control-section" id="filter-selector">
                <label for="rank_category">Select a Rank Category</label>
                <select id="rank_category" name="rank_category">
                    <option value="Instructor">Instructor</option>
                    <option value="Assistant Professor">Assistant Professor</option>
                    <option value="Associate Professor">Associate Professor</option>
                    <option value="Professor">Professor</option>
                </select>
            </div>

            <div id="live-weights-container" class="control-section">
                <h3 id="active-chart-heading">Live KRA Weights</h3>
                <div id="live-weights-chart"></div>
            </div>

            <div class="control-section">
                <h3>Primary Comparisons</h3>
                <p class="helper-text">Set the importance of each KRA relative to Instruction.</p>
                <div class="slider-group">
                    <div class="slider-comparison">
                        <span>Instruction</span>
                        <span class="slider-value-badge" id="kra_1_vs_2_value"></span>
                        <span>Research</span>
                    </div>
                    <input id="kra_1_vs_2" type="range" min="1" max="17" value="9" data-pair-id="kra_1_vs_2" data-slider-type="primary">
                </div>
                <div class="slider-group">
                    <div class="slider-comparison">
                        <span>Instruction</span>
                        <span class="slider-value-badge" id="kra_1_vs_3_value"></span>
                        <span>Extension</span>
                    </div>
                    <input id="kra_1_vs_3" type="range" min="1" max="17" value="9" data-pair-id="kra_1_vs_3" data-slider-type="primary">
                </div>
                <div class="slider-group">
                     <div class="slider-comparison">
                        <span>Instruction</span>
                        <span class="slider-value-badge" id="kra_1_vs_4_value"></span>
                        <span>Prof. Dev.</span>
                    </div>
                    <input id="kra_1_vs_4" type="range" min="1" max="17" value="9" data-pair-id="kra_1_vs_4" data-slider-type="primary">
                </div>
            </div>

            <div class="control-section">
                <h3>Derived Comparisons</h3>
                <p class="helper-text">These values are calculated automatically to ensure consistency.</p>
                <div class="slider-group">
                    <div class="slider-comparison">
                        <span>Research</span>
                        <span class="slider-value-badge" id="kra_2_vs_3_value"></span>
                        <span>Extension</span>
                    </div>
                    <input id="kra_2_vs_3" type="range" min="1" max="17" value="9" class="derived" data-pair-id="kra_2_vs_3" disabled>
                </div>
                <div class="slider-group">
                    <div class="slider-comparison">
                        <span>Research</span>
                        <span class="slider-value-badge" id="kra_2_vs_4_value"></span>
                        <span>Prof. Dev.</span>
                    </div>
                    <input id="kra_2_vs_4" type="range" min="1" max="17" value="9" class="derived" data-pair-id="kra_2_vs_4" disabled>
                </div>
                <div class="slider-group">
                    <div class="slider-comparison">
                        <span>Extension</span>
                        <span class="slider-value-badge" id="kra_3_vs_4_value"></span>
                        <span>Prof. Dev.</span>
                    </div>
                    <input id="kra_3_vs_4" type="range" min="1" max="17" value="9" class="derived" data-pair-id="kra_3_vs_4" disabled>
                </div>
            </div>

            <div>
                <button id="run-simulation-btn" class="btn btn-primary" data-run-simulation-url="{{ route('admin.modeler.run') }}">
                    <svg id="run-simulation-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <svg id="run-simulation-spinner" class="spinner hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span id="run-simulation-text">Run Simulation</span>
                </button>
            </div>
        </div>

        <!-- Results Panel -->
        <div class="results-panel">
            <h2>Simulation Results</h2>
            <p class="subtitle">The potential impact of your adjusted priorities will be displayed here.</p>
            <div id="results-container">
                <div id="results-placeholder" class="results-placeholder">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V7a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    <h3>No Simulation Run</h3>
                    <p>Adjust the controls and click "Run Simulation" to begin.</p>
                </div>
                <div id="results-content" class="hidden">
                    <div id="simulation-kpis" class="kpi-grid"></div>
                    <div class="results-section" style="margin-bottom: 2rem;">
                        <h3>Current vs. Simulated Rank Distribution</h3>
                        <div id="simulation-chart"></div>
                    </div>
                    <div class="results-section">
                        <h3>Detailed Faculty Impact</h3>
                        <div id="simulation-table-container" class="results-table-container"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="{{ asset('js/modeler-scripts.js') }}"></script>
@endpush