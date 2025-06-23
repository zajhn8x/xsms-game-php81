/**
 * Real-time Chart Updates Module
 * Handles live data updates for analytics charts
 */

class ChartUpdater {
    constructor() {
        this.updateInterval = 30000; // 30 seconds
        this.isUpdating = false;
        this.intervalId = null;
        this.chartInstances = {};
        this.lastUpdateTime = null;

        // Event listeners
        this.setupEventListeners();
    }

    /**
     * Initialize real-time updates
     */
    start() {
        if (this.isUpdating) return;

        this.isUpdating = true;
        this.intervalId = setInterval(() => {
            this.updateAllCharts();
        }, this.updateInterval);

        console.log('Real-time chart updates started');
        this.showStatus('Live updates enabled', 'success');
    }

    /**
     * Stop real-time updates
     */
    stop() {
        if (!this.isUpdating) return;

        this.isUpdating = false;
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }

        console.log('Real-time chart updates stopped');
        this.showStatus('Live updates disabled', 'info');
    }

    /**
     * Register chart instance for updates
     */
    registerChart(chartId, chartInstance, updateFunction) {
        this.chartInstances[chartId] = {
            instance: chartInstance,
            updateFunction: updateFunction
        };
    }

    /**
     * Update all registered charts
     */
    async updateAllCharts() {
        try {
            this.showStatus('Updating charts...', 'info');

            // Fetch latest data
            const data = await this.fetchLatestData();

            // Update each chart
            for (const [chartId, chartInfo] of Object.entries(this.chartInstances)) {
                if (chartInfo.updateFunction && typeof chartInfo.updateFunction === 'function') {
                    chartInfo.updateFunction(data, chartInfo.instance);
                }
            }

            this.lastUpdateTime = new Date();
            this.updateLastUpdateIndicator();
            this.showStatus('Charts updated', 'success');

        } catch (error) {
            console.error('Error updating charts:', error);
            this.showStatus('Update failed', 'error');
        }
    }

    /**
     * Fetch latest data from API
     */
    async fetchLatestData() {
        const period = document.getElementById('chartPeriod')?.value || 30;

        const [statistics, trends, performance] = await Promise.all([
            fetch('/api/statistics').then(r => r.json()),
            fetch(`/api/trends?period=${period}`).then(r => r.json()),
            fetch(`/api/performance?period=${period}`).then(r => r.json())
        ]);

        return {
            statistics,
            trends,
            performance,
            timestamp: new Date()
        };
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Period change listener
        document.addEventListener('change', (e) => {
            if (e.target.id === 'chartPeriod') {
                this.updateAllCharts();
            }
        });

        // Visibility change listener (pause updates when tab is hidden)
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.stop();
            } else {
                this.start();
            }
        });

        // Manual refresh button
        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-action="refresh-charts"]')) {
                this.updateAllCharts();
            }

            if (e.target.closest('[data-action="toggle-updates"]')) {
                this.isUpdating ? this.stop() : this.start();
            }
        });
    }

    /**
     * Update last update time indicator
     */
    updateLastUpdateIndicator() {
        const indicator = document.getElementById('lastUpdateTime');
        if (indicator && this.lastUpdateTime) {
            indicator.textContent = `Cập nhật lần cuối: ${this.lastUpdateTime.toLocaleTimeString('vi-VN')}`;
        }
    }

    /**
     * Show status message
     */
    showStatus(message, type = 'info') {
        // Create status indicator if it doesn't exist
        let statusIndicator = document.getElementById('chartStatus');
        if (!statusIndicator) {
            statusIndicator = document.createElement('div');
            statusIndicator.id = 'chartStatus';
            statusIndicator.className = 'fixed top-4 right-4 z-50 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-300';
            document.body.appendChild(statusIndicator);
        }

        // Set message and styling
        statusIndicator.textContent = message;
        statusIndicator.className = statusIndicator.className.replace(/bg-\w+-\d+/g, '');
        statusIndicator.className = statusIndicator.className.replace(/text-\w+-\d+/g, '');

        const colors = {
            success: 'bg-success-100 text-success-800 border border-success-200',
            error: 'bg-error-100 text-error-800 border border-error-200',
            info: 'bg-info-100 text-info-800 border border-info-200',
            warning: 'bg-warning-100 text-warning-800 border border-warning-200'
        };

        statusIndicator.className += ` ${colors[type] || colors.info}`;

        // Auto hide after 3 seconds
        setTimeout(() => {
            statusIndicator.style.opacity = '0';
            setTimeout(() => {
                if (statusIndicator.parentNode) {
                    statusIndicator.parentNode.removeChild(statusIndicator);
                }
            }, 300);
        }, 3000);
    }
}

/**
 * Chart Update Functions
 */
const ChartUpdateFunctions = {
    /**
     * Update profit trend chart
     */
    updateProfitTrend(data, chartInstance) {
        if (!chartInstance || !data.trends) return;

        const newData = {
            labels: data.trends.dates || [],
            datasets: [{
                label: 'Lợi Nhuận',
                data: data.trends.profits || [],
                borderColor: '#0891b2',
                backgroundColor: 'rgba(8, 145, 178, 0.1)',
                fill: true,
                tension: 0.4
            }]
        };

        chartInstance.data = newData;
        chartInstance.update('none'); // No animation for real-time updates
    },

    /**
     * Update win rate chart
     */
    updateWinRate(data, chartInstance) {
        if (!chartInstance || !data.trends) return;

        const newData = {
            labels: data.trends.dates || [],
            datasets: [{
                label: 'Tỷ Lệ Thắng',
                data: data.trends.winRates || [],
                borderColor: '#059669',
                backgroundColor: 'rgba(5, 150, 105, 0.1)',
                fill: true,
                tension: 0.4
            }]
        };

        chartInstance.data = newData;
        chartInstance.update('none');
    },

    /**
     * Update investment distribution chart
     */
    updateInvestmentDist(data, chartInstance) {
        if (!chartInstance || !data.performance) return;

        const distribution = data.performance.distribution || { normal: 45, xieng: 30, de: 25 };

        chartInstance.data.datasets[0].data = [
            distribution.normal,
            distribution.xieng,
            distribution.de
        ];

        chartInstance.update('none');

        // Update percentage displays
        document.getElementById('normalLotto').textContent = `${distribution.normal}%`;
        document.getElementById('xiengLotto').textContent = `${distribution.xieng}%`;
        document.getElementById('deLotto').textContent = `${distribution.de}%`;
    },

    /**
     * Update monthly performance chart
     */
    updateMonthlyPerformance(data, chartInstance) {
        if (!chartInstance || !data.performance) return;

        const monthlyData = data.performance.monthly || [];

        chartInstance.data.datasets[0].data = monthlyData;
        chartInstance.update('none');

        // Update best/worst month indicators
        if (monthlyData.length > 0) {
            const maxValue = Math.max(...monthlyData);
            const minValue = Math.min(...monthlyData);
            const maxIndex = monthlyData.indexOf(maxValue);
            const minIndex = monthlyData.indexOf(minValue);

            const months = ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12'];

            document.getElementById('bestMonth').textContent = months[maxIndex] || '--';
            document.getElementById('worstMonth').textContent = months[minIndex] || '--';
        }
    },

    /**
     * Update risk analysis radar chart
     */
    updateRiskAnalysis(data, chartInstance) {
        if (!chartInstance || !data.performance) return;

        const riskData = data.performance.risk || {};

        chartInstance.data.datasets[0].data = [
            riskData.volatility || 65,
            riskData.sharpeRatio || 78,
            riskData.roi || 82,
            riskData.consistency || 45,
            riskData.riskScore || 70
        ];

        chartInstance.update('none');

        // Update risk indicators
        document.getElementById('volatilityScore').textContent = `${riskData.volatility || '--'}%`;
        document.getElementById('sharpeRatio').textContent = riskData.sharpeRatio || '--';
        document.getElementById('maxDrawdown').textContent = `${riskData.maxDrawdown || '--'}%`;
    }
};

/**
 * Initialize real-time chart updates
 */
function initializeRealTimeUpdates() {
    // Create chart updater instance
    window.chartUpdater = new ChartUpdater();

    // Register chart update functions
    window.registerChartUpdates = function() {
        if (window.chartInstances) {
            window.chartUpdater.registerChart('profitTrendChart', window.chartInstances.profitTrend, ChartUpdateFunctions.updateProfitTrend);
            window.chartUpdater.registerChart('winRateChart', window.chartInstances.winRate, ChartUpdateFunctions.updateWinRate);
            window.chartUpdater.registerChart('investmentDistChart', window.chartInstances.investmentDist, ChartUpdateFunctions.updateInvestmentDist);
            window.chartUpdater.registerChart('monthlyPerformanceChart', window.chartInstances.monthlyPerformance, ChartUpdateFunctions.updateMonthlyPerformance);
            window.chartUpdater.registerChart('riskAnalysisChart', window.chartInstances.riskAnalysis, ChartUpdateFunctions.updateRiskAnalysis);
        }
    };

    // Auto-start real-time updates
    setTimeout(() => {
        if (window.registerChartUpdates) {
            window.registerChartUpdates();
            window.chartUpdater.start();
        }
    }, 1000);
}

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeRealTimeUpdates);
} else {
    initializeRealTimeUpdates();
}

// Export for use in other modules
window.ChartUpdater = ChartUpdater;
window.ChartUpdateFunctions = ChartUpdateFunctions;
