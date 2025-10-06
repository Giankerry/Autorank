document.addEventListener('DOMContentLoaded', function() {
    const statsContainer = document.getElementById('stats-container-data');
    if (!statsContainer) {
        return;
    }

    let donutChart = null;
    let barChart = null;
    let initialChartData = null;

    // --- Safely read and parse data from the DOM ---
    const userTheme = statsContainer.dataset.theme || 'light';
    const primaryColorFromPHP = statsContainer.dataset.primaryColor || '#262626';
    try {
        initialChartData = JSON.parse(statsContainer.dataset.chartData);
    } catch (e) {
        console.error("Could not parse chart data from the DOM:", e);
        initialChartData = null;
    }

    // --- Chart Configuration ---
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

    const isDarkMode = userTheme === 'dark';
    const baseMonochromeColor = primaryColorFromPHP;
    const fontFamily = "Rubik";
    const noDataMessage = { text: 'No evaluation data available yet.', align: 'center', verticalAlign: 'middle', style: { color: '#6B7280', fontSize: '14px', fontFamily: fontFamily } };

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

    // --- Donut Chart Initialization ---
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
                labels: { colors: isDarkMode ? '#ffffff' : '#000000' }
            },
            title: {
                text: 'Score Contribution',
                align: 'center',
                style: { fontSize: '18px', fontWeight: '600', color: isDarkMode ? '#ffffff' : '#000000' }
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

    // --- Bar Chart Initialization ---
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
