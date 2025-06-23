@extends('layouts.app')

@section('title', 'Thống Kê & Analytics')

@section('content')
{{-- Statistics Header --}}
<div class="mb-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Thống Kê & Analytics</h1>
            <p class="mt-2 text-sm text-gray-600">Phân tích hiệu suất và xu hướng đầu tư</p>
        </div>
        <div class="mt-4 sm:mt-0 flex flex-col sm:flex-row sm:items-center space-y-2 sm:space-y-0 sm:space-x-3">
            {{-- Real-time Status Indicator --}}
            <div class="flex items-center space-x-2">
                <div class="flex items-center">
                    <div class="w-2 h-2 bg-success-500 rounded-full animate-pulse" id="liveIndicator"></div>
                    <span class="ml-2 text-xs text-gray-600" id="lastUpdateTime">--</span>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex space-x-2">
                <x-ui.button type="primary" size="sm" data-action="refresh-charts">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Làm mới
                </x-ui.button>

                <x-ui.button type="success" size="sm" variant="outline" data-action="toggle-updates" id="toggleUpdatesBtn">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h1m4 0h1m6-7a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span id="toggleUpdatesText">Live</span>
                </x-ui.button>

                <x-ui.button type="info" size="sm" variant="outline" href="{{ route('dashboard') }}">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Dashboard
                </x-ui.button>
            </div>
        </div>
    </div>
</div>

{{-- Quick Statistics Cards --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <x-ui.metric-card
        title="Tổng Số Lần Đặt"
        subtitle="Số lần đặt cược"
        :value="0"
        color="info"
        trend="up"
        trendValue="+12%"
        :icon="'<svg class=\"w-6 h-6 text-info-600\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
            <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z\"></path>
        </svg>'"
    >
        <span id="total-bets">0</span>
    </x-ui.metric-card>

    <x-ui.metric-card
        title="Tổng Tiền Đã Đặt"
        subtitle="Vốn đầu tư"
        :value="'0 VND'"
        color="primary"
        trend="up"
        trendValue="+8%"
        :icon="'<svg class=\"w-6 h-6 text-primary-600\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
            <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1\"></path>
        </svg>'"
    >
        <span id="total-amount">0đ</span>
    </x-ui.metric-card>

    <x-ui.metric-card
        title="Số Lần Trúng"
        subtitle="Cược thành công"
        :value="0"
        color="success"
        trend="up"
        trendValue="+15%"
        :icon="'<svg class=\"w-6 h-6 text-success-600\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
            <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z\"></path>
        </svg>'"
    >
        <span id="total-wins">0</span>
    </x-ui.metric-card>

    <x-ui.metric-card
        title="Tổng Tiền Thắng"
        subtitle="Lợi nhuận"
        :value="'0 VND'"
        color="warning"
        trend="down"
        trendValue="-3%"
        :icon="'<svg class=\"w-6 h-6 text-warning-600\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
            <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7\"></path>
        </svg>'"
    >
        <span id="total-winnings">0đ</span>
    </x-ui.metric-card>
</div>

{{-- Performance Charts Section --}}
<div class="mb-8">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Phân Tích Hiệu Suất</h2>
        <div class="flex space-x-3">
            <select class="form-select rounded-lg border-gray-300 text-sm" id="chartPeriod">
                <option value="7">7 ngày qua</option>
                <option value="30" selected>30 ngày qua</option>
                <option value="90">90 ngày qua</option>
                <option value="365">1 năm qua</option>
            </select>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Profit/Loss Trend Chart --}}
        <x-ui.analytics-chart
            chartId="profitTrendChart"
            title="Xu Hướng Lợi Nhuận"
            subtitle="Theo thời gian"
            chartType="line"
            color="primary"
            height="350px"
            :data="[]"
        >
            <div class="mt-4 grid grid-cols-2 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-success-600" id="totalProfit">+0 VND</div>
                    <div class="text-sm text-gray-500">Lợi nhuận ròng</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-primary-600" id="profitMargin">0%</div>
                    <div class="text-sm text-gray-500">Tỷ suất lợi nhuận</div>
                </div>
            </div>
        </x-ui.analytics-chart>

        {{-- Win Rate Trend Chart --}}
        <x-ui.analytics-chart
            chartId="winRateChart"
            title="Tỷ Lệ Thắng"
            subtitle="Phần trăm thành công"
            chartType="line"
            color="success"
            height="350px"
            :data="[]"
        >
            <div class="mt-4 grid grid-cols-2 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-success-600" id="currentWinRate">0%</div>
                    <div class="text-sm text-gray-500">Tỷ lệ hiện tại</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-info-600" id="averageWinRate">0%</div>
                    <div class="text-sm text-gray-500">Trung bình</div>
                </div>
            </div>
        </x-ui.analytics-chart>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Investment Distribution Pie Chart --}}
        <x-ui.analytics-chart
            chartId="investmentDistChart"
            title="Phân Bổ Đầu Tư"
            subtitle="Theo loại cược"
            chartType="doughnut"
            color="warning"
            height="300px"
            :data="[]"
        >
            <div class="mt-4 space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Lô thường:</span>
                    <span class="font-medium" id="normalLotto">0%</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Lô xiên:</span>
                    <span class="font-medium" id="xiengLotto">0%</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Đề:</span>
                    <span class="font-medium" id="deLotto">0%</span>
                </div>
            </div>
        </x-ui.analytics-chart>

        {{-- Monthly Performance Bar Chart --}}
        <x-ui.analytics-chart
            chartId="monthlyPerformanceChart"
            title="Hiệu Suất Tháng"
            subtitle="12 tháng gần đây"
            chartType="bar"
            color="info"
            height="300px"
            :data="[]"
        >
            <div class="mt-4 grid grid-cols-2 gap-4">
                <div class="text-center">
                    <div class="text-lg font-bold text-success-600" id="bestMonth">--</div>
                    <div class="text-xs text-gray-500">Tháng tốt nhất</div>
                </div>
                <div class="text-center">
                    <div class="text-lg font-bold text-error-600" id="worstMonth">--</div>
                    <div class="text-xs text-gray-500">Tháng tệ nhất</div>
                </div>
            </div>
        </x-ui.analytics-chart>

        {{-- Risk Analysis Radar Chart --}}
        <x-ui.analytics-chart
            chartId="riskAnalysisChart"
            title="Phân Tích Rủi Ro"
            subtitle="Đánh giá toàn diện"
            chartType="radar"
            color="error"
            height="300px"
            :data="[]"
        >
            <div class="mt-4 space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Volatility:</span>
                    <span class="font-medium text-warning-600" id="volatilityScore">--</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Sharpe Ratio:</span>
                    <span class="font-medium text-info-600" id="sharpeRatio">--</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Max Drawdown:</span>
                    <span class="font-medium text-error-600" id="maxDrawdown">--</span>
                </div>
            </div>
        </x-ui.analytics-chart>
    </div>
</div>

{{-- Recent Betting History --}}
<div class="mb-8">
    <x-ui.card
        title="Lịch Sử Đặt Cược"
        subtitle="50 giao dịch gần nhất"
    >
        <x-slot:icon>
            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
            </svg>
        </x-slot:icon>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số Lô</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số Tiền Đặt</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kết Quả</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tiền Thắng</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ROI</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="bet-history">
                    {{-- Data will be populated by JavaScript --}}
                </tbody>
            </table>
        </div>

        <x-slot:footer>
            <div class="flex justify-between items-center">
                <div class="text-sm text-gray-500">
                    Hiển thị <span id="recordCount">0</span> bản ghi
                </div>
                <x-ui.button size="sm" type="primary" variant="outline" onclick="loadMoreHistory()">
                    Xem thêm
                </x-ui.button>
            </div>
        </x-slot:footer>
    </x-ui.card>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Global Chart Instances
let chartInstances = {};

document.addEventListener('DOMContentLoaded', function () {
    // Initialize all components
    loadStatistics();
    loadBetHistory();
    initializeCharts();

    // Event listeners
    document.getElementById('chartPeriod').addEventListener('change', function() {
        refreshCharts(this.value);
    });
});

// Load main statistics
function loadStatistics() {
    fetch('/api/statistics')
        .then(response => response.json())
        .then(data => {
            updateStatisticsCards(data);
        })
        .catch(error => {
            console.error('Error loading statistics:', error);
            showNotification('Không thể tải dữ liệu thống kê', 'error');
        });
}

// Update statistics cards
function updateStatisticsCards(data) {
    document.getElementById('total-bets').textContent = formatNumber(data.total_bets);
    document.getElementById('total-amount').textContent = formatMoney(data.total_amount_bet);
    document.getElementById('total-wins').textContent = formatNumber(data.total_wins);
    document.getElementById('total-winnings').textContent = formatMoney(data.total_winnings);

    // Update performance metrics
    document.getElementById('totalProfit').textContent = formatMoney(data.total_profit || 0);
    document.getElementById('profitMargin').textContent = (data.profit_margin || 0) + '%';
    document.getElementById('currentWinRate').textContent = (data.current_win_rate || 0) + '%';
    document.getElementById('averageWinRate').textContent = (data.average_win_rate || 0) + '%';
}

// Load betting history
function loadBetHistory() {
    fetch('/api/bet-history?limit=50')
        .then(response => response.json())
        .then(data => {
            populateBetHistory(data);
        })
        .catch(error => {
            console.error('Error loading bet history:', error);
        });
}

// Populate betting history table
function populateBetHistory(data) {
    const historyTable = document.getElementById('bet-history');
    historyTable.innerHTML = '';

    data.forEach(bet => {
        const row = document.createElement('tr');
        const roi = calculateROI(bet.amount, bet.win_amount);

        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${formatDate(bet.bet_date)}</td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                    ${bet.lo_number}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${formatMoney(bet.amount)}</td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${bet.is_win ? 'bg-success-100 text-success-800' : 'bg-error-100 text-error-800'}">
                    ${bet.is_win ? '✓ Trúng' : '✗ Trượt'}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium ${bet.win_amount > 0 ? 'text-success-600' : 'text-gray-900'}">${formatMoney(bet.win_amount)}</td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${roi > 0 ? 'bg-success-100 text-success-800' : roi < 0 ? 'bg-error-100 text-error-800' : 'bg-gray-100 text-gray-800'}">
                    ${roi > 0 ? '+' : ''}${roi}%
                </span>
            </td>
        `;
        historyTable.appendChild(row);
    });

    document.getElementById('recordCount').textContent = data.length;
}

// Initialize all charts
function initializeCharts() {
    initializeProfitTrendChart();
    initializeWinRateChart();
    initializeInvestmentDistChart();
    initializeMonthlyPerformanceChart();
    initializeRiskAnalysisChart();

    // Register charts for real-time updates
    setTimeout(() => {
        if (window.registerChartUpdates) {
            window.registerChartUpdates();
        }
    }, 500);
}

// Profit Trend Chart
function initializeProfitTrendChart() {
    const ctx = document.getElementById('profitTrendChart');
    if (!ctx) return;

    chartInstances.profitTrend = new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: {
            labels: generateDateLabels(30),
            datasets: [{
                label: 'Lợi Nhuận',
                data: generateSampleData(30, -10000, 50000),
                borderColor: '#0891b2',
                backgroundColor: 'rgba(8, 145, 178, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: getLineChartOptions('VND')
    });
}

// Win Rate Chart
function initializeWinRateChart() {
    const ctx = document.getElementById('winRateChart');
    if (!ctx) return;

    chartInstances.winRate = new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: {
            labels: generateDateLabels(30),
            datasets: [{
                label: 'Tỷ Lệ Thắng',
                data: generateSampleData(30, 0, 100),
                borderColor: '#059669',
                backgroundColor: 'rgba(5, 150, 105, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: getLineChartOptions('%', 0, 100)
    });
}

// Investment Distribution Chart
function initializeInvestmentDistChart() {
    const ctx = document.getElementById('investmentDistChart');
    if (!ctx) return;

    chartInstances.investmentDist = new Chart(ctx.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Lô Thường', 'Lô Xiên', 'Đề'],
            datasets: [{
                data: [45, 30, 25],
                backgroundColor: ['#d97706', '#059669', '#dc2626'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: getDoughnutChartOptions()
    });
}

// Monthly Performance Chart
function initializeMonthlyPerformanceChart() {
    const ctx = document.getElementById('monthlyPerformanceChart');
    if (!ctx) return;

    chartInstances.monthlyPerformance = new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12'],
            datasets: [{
                label: 'Lợi Nhuận',
                data: generateSampleData(12, -20000, 80000),
                backgroundColor: '#2563eb',
                borderRadius: 4
            }]
        },
        options: getBarChartOptions('VND')
    });
}

// Risk Analysis Radar Chart
function initializeRiskAnalysisChart() {
    const ctx = document.getElementById('riskAnalysisChart');
    if (!ctx) return;

    chartInstances.riskAnalysis = new Chart(ctx.getContext('2d'), {
        type: 'radar',
        data: {
            labels: ['Volatility', 'Sharpe Ratio', 'ROI', 'Consistency', 'Risk Score'],
            datasets: [{
                label: 'Current Portfolio',
                data: [65, 78, 82, 45, 70],
                borderColor: '#dc2626',
                backgroundColor: 'rgba(220, 38, 38, 0.1)',
                pointBackgroundColor: '#dc2626'
            }]
        },
        options: getRadarChartOptions()
    });
}

// Chart Options Templates
function getLineChartOptions(suffix = '', min = null, max = null) {
    return {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { intersect: false, mode: 'index' },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#374151',
                titleColor: '#ffffff',
                bodyColor: '#ffffff',
                borderColor: '#6b7280',
                borderWidth: 1,
                cornerRadius: 8
            }
        },
        scales: {
            x: {
                grid: { color: '#f3f4f6' },
                ticks: { color: '#6b7280', font: { size: 12 } }
            },
            y: {
                beginAtZero: min === null,
                min: min,
                max: max,
                grid: { color: '#f3f4f6' },
                ticks: {
                    color: '#6b7280',
                    font: { size: 12 },
                    callback: function(value) {
                        return formatNumber(value) + suffix;
                    }
                }
            }
        }
    };
}

function getDoughnutChartOptions() {
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#374151',
                titleColor: '#ffffff',
                bodyColor: '#ffffff',
                callbacks: {
                    label: function(context) {
                        return context.label + ': ' + context.parsed + '%';
                    }
                }
            }
        }
    };
}

function getBarChartOptions(suffix = '') {
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#374151',
                titleColor: '#ffffff',
                bodyColor: '#ffffff'
            }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { color: '#6b7280', font: { size: 12 } }
            },
            y: {
                grid: { color: '#f3f4f6' },
                ticks: {
                    color: '#6b7280',
                    font: { size: 12 },
                    callback: function(value) {
                        return formatNumber(value) + suffix;
                    }
                }
            }
        }
    };
}

function getRadarChartOptions() {
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            r: {
                beginAtZero: true,
                max: 100,
                ticks: { display: false },
                grid: { color: '#f3f4f6' },
                angleLines: { color: '#e5e7eb' }
            }
        }
    };
}

// Utility Functions
function generateDateLabels(days) {
    const labels = [];
    for (let i = days - 1; i >= 0; i--) {
        const date = new Date();
        date.setDate(date.getDate() - i);
        labels.push(date.toLocaleDateString('vi-VN', { month: 'short', day: 'numeric' }));
    }
    return labels;
}

function generateSampleData(count, min, max) {
    return Array.from({ length: count }, () =>
        Math.floor(Math.random() * (max - min + 1)) + min
    );
}

function formatMoney(amount) {
    return new Intl.NumberFormat('vi-VN').format(amount) + ' VND';
}

function formatNumber(number) {
    return new Intl.NumberFormat('vi-VN').format(number);
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('vi-VN');
}

function calculateROI(invested, returned) {
    if (invested === 0) return 0;
    return Math.round(((returned - invested) / invested) * 100);
}

// Interactive Functions
function refreshData() {
    showNotification('Đang làm mới dữ liệu...', 'info');
    loadStatistics();
    loadBetHistory();
    refreshCharts(document.getElementById('chartPeriod').value);

    // Trigger real-time update if available
    if (window.chartUpdater) {
        window.chartUpdater.updateAllCharts();
    }
}

function refreshCharts(period) {
    // Update chart data based on period
    console.log('Refreshing charts for period:', period);
    // Implementation would fetch new data and update charts
}

function loadMoreHistory() {
    showNotification('Đang tải thêm dữ liệu...', 'info');
    // Implementation would load more history records
}

function showNotification(message, type = 'info') {
    // Implementation would show toast notification
    console.log(`${type.toUpperCase()}: ${message}`);
}

// Real-time UI Updates
function updateLiveIndicator(isActive) {
    const indicator = document.getElementById('liveIndicator');
    const toggleBtn = document.getElementById('toggleUpdatesBtn');
    const toggleText = document.getElementById('toggleUpdatesText');

    if (indicator) {
        indicator.className = isActive
            ? 'w-2 h-2 bg-success-500 rounded-full animate-pulse'
            : 'w-2 h-2 bg-gray-400 rounded-full';
    }

    if (toggleBtn && toggleText) {
        toggleBtn.className = toggleBtn.className.replace(/bg-\w+-\d+/g, '').replace(/text-\w+-\d+/g, '');
        if (isActive) {
            toggleBtn.className += ' bg-success-100 text-success-800 border-success-200';
            toggleText.textContent = 'Live';
        } else {
            toggleBtn.className += ' bg-gray-100 text-gray-800 border-gray-200';
            toggleText.textContent = 'Paused';
        }
    }
}

// Listen to real-time updater events
document.addEventListener('DOMContentLoaded', function() {
    // Monitor chart updater status
    const checkUpdaterStatus = () => {
        if (window.chartUpdater) {
            updateLiveIndicator(window.chartUpdater.isUpdating);
        }
    };

    // Check status every second
    setInterval(checkUpdaterStatus, 1000);

    // Initial check
    setTimeout(checkUpdaterStatus, 2000);
});
</script>
@endpush
