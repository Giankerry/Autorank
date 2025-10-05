@extends('layouts.dashboard-layout')

@section('title', 'Strategic Modeler | Autorank')

@push('styles')
<style>
    /*
    |--------------------------------------------------------------------------
    | Main Page Layout
    |--------------------------------------------------------------------------
    */
    .main-content-container {
        width: 95%;
        min-height: calc(100vh - 80px);
        display: flex;
        justify-self: center;
        justify-content: center;
        align-items: center
    }

    .modeler-container {
        display: grid;
        grid-template-columns: 1fr; /* Default to a single column on mobile */
        grid-template-areas:
            "controls"
            "results";
        gap: 2rem;
        font-family: var(--mainFont);
    }

    /* On large screens, switch to a 1fr 2fr layout */
    @media (min-width: 1024px) {
        .modeler-container {
            grid-template-columns: 1fr 2fr; /* This creates the 1/3 and 2/3 split */
            grid-template-areas: "controls results";
        }
    }

    .control-pod {
        grid-area: controls;
        background-color: var(--tableBackgroundColor);
        border-radius: 0.5rem;
        padding: 1.5rem;
        height: fit-content;
    }

    .results-panel {
        grid-area: results;
        background-color: var(--tableBackgroundColor);
        border-radius: 0.5rem;
        padding: 1.5rem;
    }

    /*
    |--------------------------------------------------------------------------
    | Typography & Section Headers
    |--------------------------------------------------------------------------
    */
    .control-pod h2, .results-panel h2 {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--pageTextColorOnBlack);
        margin-bottom: 0.5rem;
    }
    .control-pod .subtitle, .results-panel .subtitle {
        color: var(--pageTextColorOnBlack);
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--tableDataBorderColor);
    }
    .control-section {
        width: 100%;
        margin-bottom: 10px;
    }
    .control-section h3 {
        font-size: 1.125rem;
        font-weight: 600;
        color: var(--pageTextColorOnBlack);
        margin-bottom: .75rem;
    }
    .control-section .helper-text {
        font-size: 0.75rem;
        color: var(--pageTextColorOnBlack);
        margin-top: -0.5rem;
        margin-bottom: 1rem;
    }

    .control-section {
    position: relative;
    display: inline-block;
    }

    .control-section select {
    width: 100%;
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

    .control-section select:hover {
    background-color: hsl(var(--base-hue-primary), var(--base-saturation-primary), calc(var(--base-lightness-primary) + 25%));;
    }

    #filter-selector::after {
        content: "▼";
        position: absolute;
        right: 15px;
        top: 69%;
        transform: translateY(-50%);
        font-size: 12px;
        color: #fff;
        pointer-events: none;
    }

    /*
    |--------------------------------------------------------------------------
    | Form Controls (Select & Sliders)
    |--------------------------------------------------------------------------
    */
    .control-pod label {
        display: block;
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--pageTextColorOnBlack);
        margin-bottom: 0.5rem;
    }
    .slider-group {
        margin-bottom: 1.5rem;
    }
    .slider-comparison {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--pageTextColorOnBlack);
    }
    .slider-value-badge {
        padding: 0.25rem 0.75rem;
        font-size: 0.75rem;
        font-weight: 600;
        border-radius: 9999px;
        transition: background-color 0.3s, color 0.3s;
    }
    input[type="range"] {
        -webkit-appearance: none;
        width: 100%;
        height: 0.5rem;
        background: #d1d5db;
        border-radius: 0.25rem;
        outline: none;
        opacity: 0.7;
        transition: opacity .2s;
        margin-top: 0.5rem;
    }
    input[type="range"]:hover { opacity: 1; }
    input[type="range"]::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 1.25rem;
        height: 1.25rem;
        background: #3b82f6;
        cursor: pointer;
        border-radius: 50%;
        border: 2px solid white;
        box-shadow: 0 0 2px rgba(0,0,0,0.3);
    }
    input[type="range"].derived {
        background: #e5e7eb;
    }
    input[type="range"].derived::-webkit-slider-thumb {
        background: #9ca3af;
        cursor: not-allowed;
    }

    input[type="range"]::-moz-range-thumb {
        width: 1.25rem;
        height: 1.25rem;
        background: #3b82f6;
        cursor: pointer;
        border-radius: 50%;
        border: 2px solid white;
        box-shadow: 0 0 2px rgba(0,0,0,0.3);
        border: none;
    }
    input[type="range"].derived::-moz-range-thumb {
        background: #9ca3af;
        cursor: not-allowed;
    }

    /*
    |--------------------------------------------------------------------------
    | Buttons & Interactive Elements
    |--------------------------------------------------------------------------
    */
    .btn {
        width: 100%;
        border: none;
        color: white;
        border-radius: 5px;
        height: 50px;
        font-size: 1.1rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .btn-primary { background-color: hsl(var(--base-hue-primary), var(--base-saturation-primary), calc(var(--base-lightness-primary) + 15%)) }
    .btn-primary:hover { background-color: hsl(var(--base-hue-primary), var(--base-saturation-primary), calc(var(--base-lightness-primary) + 25%)); cursor: pointer; }
    .btn svg { width: 1.25rem; height: 1.25rem; margin-right: 0.5rem; }
    .spinner { animation: spin 1s linear infinite; }
    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

    /*
    |-------------------------------------------------------------------------- 
    | Live Donut Charts
    |-------------------------------------------------------------------------- 
    */
    #live-weights-container {
        margin-bottom: 1.5rem;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    #live-weights-container h3 {
        text-align: center;
        margin-bottom: 1rem;
    }

    #active-chart-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
        max-width: 420px;
        height: auto;
        margin-bottom: .5rem;
    }

    #active-chart-wrapper .apexcharts-canvas {
        margin: 0 auto;
        display: block;
    }

    /*
    |--------------------------------------------------------------------------
    | Results Panel (Placeholder, KPIs, Chart, Table)
    |--------------------------------------------------------------------------
    */
    .results-placeholder { text-align: center; padding: 4rem 0; }
    .results-placeholder svg { margin: 0 auto; height: 3rem; width: 3rem; color: var(--pageTextColorOnBlack); }
    .results-placeholder h3 { margin-top: 0.5rem; font-size: 0.875rem; font-weight: 500; color: var(--pageTextColorOnBlack); }
    .results-placeholder p { margin-top: 0.25rem; font-size: 0.875rem; color: var(--pageTextColorOnBlack); }

    .kpi-grid { display: grid; grid-template-columns: repeat(1, 1fr); gap: 1rem; margin-bottom: 2rem; }
    @media (min-width: 768px) { .kpi-grid { grid-template-columns: repeat(3, 1fr); } }
    
    .kpi-card { padding: 1rem; border-radius: 0.5rem; text-align: center; }
    .kpi-card-label { font-size: 0.875rem; font-weight: 500; color: var(--pageTextColorOnBlack); }
    .kpi-card-value { margin-top: 0.25rem; font-size: 1.875rem; font-weight: 600; color: var(--pageTextColorOnBlack); }
    .kpi-card-value.small { font-size: 1.25rem; }

    .results-section h3 { font-size: 1.125rem; font-weight: 600; color: var(--pageTextColorOnBlack); margin-bottom: 0.5rem; }
    .results-table-container { overflow-x: auto; margin-top: 1rem; }
    .results-table { min-width: 100%; border-collapse: collapse; }
    .results-table thead { background-color: #f9fafb; }
    .results-table th, .results-table td { padding: 0.75rem 1.5rem; text-align: left; }
    .results-table th { font-size: 0.75rem; font-weight: 500; color: black; text-transform: uppercase; letter-spacing: 0.05em; }
    .results-table tbody tr { border-bottom: 1px solid var(--tableDataBorderColor); }
    .results-table td { font-size: 0.875rem; color: var(--pageTextColorOnBlack); }
    .results-table td:first-child { font-weight: 500; color: var(--pageTextColorOnBlack); }
    .change-badge { padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 600; border-radius: 9999px; }
    .badge-promoted { background-color: #dcfce7; color: #166534; }
    .badge-demoted { background-color: #fee2e2; color: #991b1b; }
    .badge-nochange { background-color: #f3f4f6; color: #374151; }
    
    /* Utility */
    .hidden { display: none; }
</style>
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
                <button id="run-simulation-btn" class="btn btn-primary">
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- Element Selectors ---
    const rankCategorySelect = document.getElementById('rank_category');
    const primarySliders = document.querySelectorAll('[data-slider-type="primary"]');
    const allSliders = document.querySelectorAll('input[type="range"]');
    const runButton = document.getElementById('run-simulation-btn');
    const buttonText = document.getElementById('run-simulation-text');
    const buttonIcon = document.getElementById('run-simulation-icon');
    const buttonSpinner = document.getElementById('run-simulation-spinner');
    const resultsPlaceholder = document.getElementById('results-placeholder');
    const resultsContent = document.getElementById('results-content');
    
    let chart = null;
    let liveWeightsChart = null;

    // --- Session State Management ---
    let sessionScenarios = {};

    function initializeSessionState() {
        Array.from(rankCategorySelect.options).forEach(opt => {
            sessionScenarios[opt.value] = {
                'kra_1_vs_2': 9, 'kra_1_vs_3': 9, 'kra_1_vs_4': 9,
            };
        });
    }

    function saveCurrentSliderState() {
        const currentCategory = rankCategorySelect.value;
        primarySliders.forEach(slider => {
            sessionScenarios[currentCategory][slider.id] = parseInt(slider.value);
        });
    }

    function loadSliderState(newCategory) {
        if (!sessionScenarios[newCategory]) return;
        
        primarySliders.forEach(slider => {
            slider.value = sessionScenarios[newCategory][slider.id];
        });
        
        enforceConsistency();
    }
    
    rankCategorySelect.addEventListener('change', (e) => {
        loadSliderState(e.target.value);
    });

    // --- Core Calculation & Update Logic ---
    function ahpToSliderValue(ahpValue) {
        if (ahpValue === 1) return 9;
        if (ahpValue > 1) return Math.min(17, Math.round(ahpValue + 8));
        if (ahpValue < 1) return Math.max(1, Math.round(10 - (1 / ahpValue)));
        return 9;
    }
    function getAhpValue(slider) {
        const value = parseInt(slider.value);
        if (value === 9) return 1;
        if (value < 9) return 1 / (10 - value);
        return value - 8;
    }

    function enforceConsistency() {
        const val1v2 = getAhpValue(document.getElementById('kra_1_vs_2'));
        const val1v3 = getAhpValue(document.getElementById('kra_1_vs_3'));
        const val1v4 = getAhpValue(document.getElementById('kra_1_vs_4'));
        const val2v3 = val1v3 / val1v2;
        const val2v4 = val1v4 / val1v2;
        const val3v4 = val1v4 / val1v3;
        document.getElementById('kra_2_vs_3').value = ahpToSliderValue(val2v3);
        document.getElementById('kra_2_vs_4').value = ahpToSliderValue(val2v4);
        document.getElementById('kra_3_vs_4').value = ahpToSliderValue(val3v4);
        allSliders.forEach(s => updateSliderLabel(s));
        saveCurrentSliderState();
        calculateAndDisplayLiveWeights();
    }
    
    // --- Chart & Label Functions ---
    function initializeLiveWeightsChart() {
        const options = {
            series: [25, 25, 25, 25],
            chart: { type: 'donut', height: 250 },
            labels: ['Instruction', 'Research', 'Extension', 'Professional Development'],
            colors: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6'],
            plotOptions: { pie: { donut: { size: '65%' } } },
            legend: { position: 'bottom', labels: { colors: 'var(--pageTextColorOnBlack)' } },
            dataLabels: { enabled: false }
        };
        liveWeightsChart = new ApexCharts(document.querySelector("#live-weights-chart"), options);
        liveWeightsChart.render();
    }
    
    const ahpVerbalAnchors = { 1: "Extremely Less Important", 3: "Strongly Less Important", 5: "Moderately Less Important", 7: "Slightly Less Important", 9: "Equally Important", 11: "Slightly More Important", 13: "Moderately More Important", 15: "Strongly More Important", 17: "Extremely More Important" };
    
    function updateSliderLabel(slider) {
        const valueSpan = document.getElementById(`${slider.dataset.pairId}_value`);
        if (!valueSpan) return;
        const closestKey = Object.keys(ahpVerbalAnchors).reduce((prev, curr) => (Math.abs(curr - slider.value) < Math.abs(prev - slider.value) ? curr : prev));
        valueSpan.textContent = ahpVerbalAnchors[closestKey];
        if (slider.value < 9) { valueSpan.style.backgroundColor = '#fee2e2'; valueSpan.style.color = '#991b1b'; }
        else if (slider.value > 9) { valueSpan.style.backgroundColor = '#dcfce7'; valueSpan.style.color = '#166534'; }
        else { valueSpan.style.backgroundColor = '#dbeafe'; valueSpan.style.color = '#1e40af'; }
    }

    function calculateAndDisplayLiveWeights() {
        const comparisons = {};
        allSliders.forEach(slider => { comparisons[slider.id] = getAhpValue(slider); });
        
        const matrix = [
            [1, comparisons['kra_1_vs_2'], comparisons['kra_1_vs_3'], comparisons['kra_1_vs_4']],
            [1 / comparisons['kra_1_vs_2'], 1, comparisons['kra_2_vs_3'], comparisons['kra_2_vs_4']],
            [1 / comparisons['kra_1_vs_3'], 1 / comparisons['kra_2_vs_3'], 1, comparisons['kra_3_vs_4']],
            [1 / comparisons['kra_1_vs_4'], 1 / comparisons['kra_2_vs_4'], 1 / comparisons['kra_3_vs_4'], 1],
        ];
        const columnSums = [0, 0, 0, 0];
        for (let i = 0; i < 4; i++) { for (let j = 0; j < 4; j++) { columnSums[j] += matrix[i][j]; } }
        const normalizedMatrix = matrix.map(row => row.map((cell, j) => cell / columnSums[j]));
        const weights = normalizedMatrix.map(row => row.reduce((a, b) => a + b, 0) / 4);
        liveWeightsChart.updateSeries(weights.map(w => w * 100));
    }

    // --- Event Listeners & Initial Page Load ---
    primarySliders.forEach(slider => {
        slider.addEventListener('input', enforceConsistency);
    });

    initializeSessionState();
    initializeLiveWeightsChart();
    loadSliderState(rankCategorySelect.value);
    
    // --- Simulation & Result Rendering Logic ---
    runButton.addEventListener('click', async () => {
        buttonText.textContent = 'Calculating...';
        buttonIcon.classList.add('hidden');
        buttonSpinner.classList.remove('hidden');
        runButton.disabled = true;

        const comparisons = {};
        allSliders.forEach(slider => { comparisons[slider.id] = getAhpValue(slider); });

        const payload = {
            rank_category: document.getElementById('rank_category').value,
            comparisons: comparisons,
            _token: '{{ csrf_token() }}'
        };

        try {
            const response = await fetch("{{ route('admin.modeler.run') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            });
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'An unknown error occurred.');
            }
            const results = await response.json();
            displayResults(results);
        } catch (error) {
            displayError(error.message);
        } finally {
            buttonText.textContent = 'Run Simulation';
            buttonIcon.classList.remove('hidden');
            buttonSpinner.classList.add('hidden');
            runButton.disabled = false;
        }
    });

    function displayResults(data) {
        resultsPlaceholder.classList.add('hidden');
        renderKPIs(data.kpis);
        renderChart(data.chart_data);
        renderTable(data.table_data);
        resultsContent.classList.remove('hidden');
    }
    
    function displayError(message) {
        resultsPlaceholder.classList.remove('hidden');
        resultsContent.classList.add('hidden');
        resultsPlaceholder.innerHTML = `
            <div class="results-placeholder">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" style="color: var(--alertRedH);"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <h3>Simulation Failed</h3>
                <p>${message}</p>
            </div>
        `;
    }

    function renderKPIs(kpis) {
        const container = document.getElementById('simulation-kpis');
        container.innerHTML = `
            <div class="kpi-card">
                <p class="kpi-card-label">Faculty Promoted</p>
                <p class="kpi-card-value">${kpis.total_promoted}</p>
            </div>
            <div class="kpi-card">
                <p class="kpi-card-label">New Average Score</p>
                <p class="kpi-card-value">${kpis.new_average_score.toFixed(2)}</p>
            </div>
            <div class="kpi-card">
                <p class="kpi-card-label">Highest Impact</p>
                <p class="kpi-card-value small">${kpis.highest_impact_faculty}</p>
            </div>
        `;
    }

    function renderChart(chartData) {
        const options = {
            series: [{ name: 'Current', data: chartData.current }, { name: 'Simulated', data: chartData.simulated }],
            chart: { type: 'bar', height: 350, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
            colors: ['#9ca3af', '#3b82f6'],
            plotOptions: { bar: { horizontal: false, columnWidth: '55%', borderRadius: 4 } },
            dataLabels: { enabled: false },
            stroke: { show: true, width: 2, colors: ['transparent'] },
            xaxis: { categories: chartData.categories, labels: { style: { colors: 'var(--pageTextColorOnBlack)' } } },
            yaxis: { title: { text: 'Number of Faculty', style: { color: 'var(--pageTextColorOnBlack)' } }, labels: { style: { colors: 'var(--pageTextColorOnBlack)' } } },
            fill: { opacity: 1 },
            grid: { borderColor: 'var(--tableDataBorderColor)' },
            legend: { markers: { radius: 12 }, labels: { colors: 'var(--pageTextColorOnBlack)' }},
            tooltip: { y: { formatter: (val) => `${val} faculty` } }
        };
        const chartContainer = document.getElementById('simulation-chart');
        chartContainer.innerHTML = '';
        if(chart) chart.destroy();
        chart = new ApexCharts(chartContainer, options);
        chart.render();
    }
    
    function renderTable(tableData) {
        const container = document.getElementById('simulation-table-container');
        let tableHtml = `<table class="results-table"><thead><tr><th>Applicant</th><th>Current Rank</th><th>Simulated Rank</th><th>Change</th></tr></thead><tbody>`;
        tableData.forEach(row => {
            let changeClass = 'badge-nochange';
            if (row.change_type === 'promoted') {
                changeClass = 'badge-promoted';
            } else if (row.change_type === 'demoted') {
                changeClass = 'badge-demoted';
            }
            tableHtml += `<tr><td>${row.name}</td><td>${row.current_rank}</td><td>${row.simulated_rank}</td><td><span class="change-badge ${changeClass}">${row.change_text}</span></td></tr>`;
        });
        tableHtml += '</tbody></table>';
        container.innerHTML = tableHtml;
    }
});
</script>
@endpush

