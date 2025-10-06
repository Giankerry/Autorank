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
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    let chart = null;
    let liveWeightsChart = null;

    // --- Session State Management ---
    let sessionScenarios = {};

    function initializeSessionState() {
        if (!rankCategorySelect) return;
        Array.from(rankCategorySelect.options).forEach(opt => {
            sessionScenarios[opt.value] = {
                'kra_1_vs_2': 9, 'kra_1_vs_3': 9, 'kra_1_vs_4': 9,
            };
        });
    }

    function saveCurrentSliderState() {
        const currentCategory = rankCategorySelect.value;
        if (!sessionScenarios[currentCategory]) return;
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
    
    if (rankCategorySelect) {
        rankCategorySelect.addEventListener('change', (e) => {
            loadSliderState(e.target.value);
        });
    }

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
        const chartEl = document.querySelector("#live-weights-chart");
        if (!chartEl) return;
        const options = {
            series: [25, 25, 25, 25],
            chart: { type: 'donut', height: 250 },
            labels: ['Instruction', 'Research', 'Extension', 'Professional Development'],
            stroke: { show: false },
            colors: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6'],
            plotOptions: { pie: { donut: { size: '65%' } } },
            legend: { position: 'bottom', labels: { colors: 'var(--pageTextColorOnBlack)' } },
            dataLabels: { enabled: false }
        };
        liveWeightsChart = new ApexCharts(chartEl, options);
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
        if (!liveWeightsChart) return;
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
    if(rankCategorySelect) {
      loadSliderState(rankCategorySelect.value);
    }
    
    // --- Simulation & Result Rendering Logic ---
    if (runButton) {
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
                _token: csrfToken
            };

            const runSimulationUrl = runButton.dataset.runSimulationUrl;

            try {
                const response = await fetch(runSimulationUrl, {
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
    }

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
            tooltip: {
                enabled: true,
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
                        html += `<span style="font-weight:500;color:${textColor};font-size:13px;margin-left:8px;">${rowValue} faculty</span>`;
                        html += `</div>`;
                    }
                    html += `</div></div>`;
                    return html;
                }
            }
        };
        const chartContainer = document.getElementById('simulation-chart');
        chartContainer.innerHTML = '';
        if(chart) chart.destroy();
        chart = new ApexCharts(chartContainer, options);
        chart.render();
    }
    
    function renderTable(tableData) {
        const container = document.getElementById('simulation-table-container');
        let tableHtml = `<table class="results-table"><thead><tr><th>Applicant</th><th>Baseline Score</th><th>Simulated Score</th><th>Score Change</th><th>Baseline Rank</th><th>Simulated Rank</th><th style="width: 140px;">Change</th></tr></thead><tbody>`;
        
        tableData.forEach(row => {
            let changeClass = 'badge-nochange';
            if (row.change_type === 'promoted') {
                changeClass = 'badge-promoted';
            } else if (row.change_type === 'demoted') {
                changeClass = 'badge-demoted';
            }

            const scoreChange = parseFloat(row.score_change);
            const scoreChangeClass = scoreChange > 0 ? 'text-success' : (scoreChange < 0 ? 'text-danger' : 'text-neutral');
            const scoreChangeSign = scoreChange > 0 ? '+' : '';

            tableHtml += `
                <tr>
                    <td>${row.name}</td>
                    <td>${row.baseline_score}</td>
                    <td>${row.simulated_score}</td>
                    <td><span class="${scoreChangeClass}">${scoreChangeSign}${row.score_change}</span></td>
                    <td>${row.current_rank}</td>
                    <td>${row.simulated_rank}</td>
                    <td><span class="change-badge ${changeClass}">${row.change_text}</span></td>
                </tr>
            `;
        });

        tableHtml += '</tbody></table>';
        container.innerHTML = tableHtml;
    }
});