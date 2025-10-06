document.addEventListener('DOMContentLoaded', function() {
    const chartContainer = document.querySelector("#rankDistributionChart");
    if (!chartContainer) return;

    let rankData;
    try {
        rankData = JSON.parse(chartContainer.dataset.chartData);
    } catch (error) {
        console.error("Failed to parse chart data. Make sure the data-chart-data attribute is present and contains valid JSON.", error);
        return;
    }

    const ranks = Object.keys(rankData);
    const counts = Object.values(rankData);
    const hasInitialData = counts.some(c => c > 0);

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

    const chart = new ApexCharts(document.querySelector("#rankDistributionChart"), options);
    chart.render();
});