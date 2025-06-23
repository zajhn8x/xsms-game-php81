@extends('layouts.app')

@section('title', 'Campaign Analytics')

@section('content')
{{-- Campaign Analytics Header --}}
<div class="mb-8">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Campaign Analytics</h1>
            <p class="mt-2 text-sm text-gray-600">Phân tích chi tiết hiệu suất chiến dịch</p>
        </div>
        <div class="mt-4 lg:mt-0 flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
            {{-- Campaign Selector --}}
            <select class="form-select rounded-lg border-gray-300 text-sm" id="campaignSelector">
                <option value="all">Tất cả chiến dịch</option>
                <option value="active">Đang hoạt động</option>
                <option value="completed">Đã hoàn thành</option>
                <option value="paused">Tạm dừng</option>
            </select>

            {{-- Time Range Selector --}}
            <select class="form-select rounded-lg border-gray-300 text-sm" id="timeRangeSelector">
                <option value="7">7 ngày qua</option>
                <option value="30" selected>30 ngày qua</option>
                <option value="90">90 ngày qua</option>
                <option value="365">1 năm qua</option>
            </select>

            {{-- Actions --}}
            <x-ui.button type="primary" size="sm" data-action="export-report">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Export
            </x-ui.button>
        </div>
    </div>
</div>

{{-- Quick Campaign Metrics --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <x-ui.metric-card
        title="Tổng Chiến Dịch"
        subtitle="Đã tạo"
        :value="0"
        color="primary"
        trend="up"
        trendValue="+5%"
        :icon="'<svg class=\"w-6 h-6 text-primary-600\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
            <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10\"></path>
        </svg>'"
    >
        <span id="totalCampaigns">0</span>
    </x-ui.metric-card>

    <x-ui.metric-card
        title="Tổng Đầu Tư"
        subtitle="Vốn chiến dịch"
        :value="'0 VND'"
        color="info"
        trend="up"
        trendValue="+12%"
        :icon="'<svg class=\"w-6 h-6 text-info-600\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
            <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z\"></path>
        </svg>'"
    >
        <span id="totalInvestment">0 VND</span>
    </x-ui.metric-card>

    <x-ui.metric-card
        title="Lợi Nhuận"
        subtitle="ROI chiến dịch"
        :value="'0 VND'"
        color="success"
        trend="up"
        trendValue="+8%"
        :icon="'<svg class=\"w-6 h-6 text-success-600\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
            <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M13 7h8m0 0v8m0-8l-8 8-4-4-6 6\"></path>
        </svg>'"
    >
        <span id="totalProfit">0 VND</span>
    </x-ui.metric-card>

    <x-ui.metric-card
        title="Tỷ Lệ Thành Công"
        subtitle="Campaign success"
        :value="'0%'"
        color="warning"
        trend="down"
        trendValue="-2%"
        :icon="'<svg class=\"w-6 h-6 text-warning-600\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
            <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z\"></path>
        </svg>'"
    >
        <span id="successRate">0%</span>
    </x-ui.metric-card>
</div>

{{-- Campaign Performance Visualizations --}}
<div class="mb-8">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">Phân Tích Hiệu Suất Chi Tiết</h2>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Campaign ROI Timeline --}}
        <x-ui.analytics-chart
            chartId="campaignROIChart"
            title="ROI Theo Thời Gian"
            subtitle="Biến động lợi nhuận"
            chartType="line"
            color="primary"
            height="400px"
            :data="[]"
        >
            <div class="mt-4 grid grid-cols-3 gap-4">
                <div class="text-center">
                    <div class="text-lg font-bold text-success-600" id="bestROI">+0%</div>
                    <div class="text-xs text-gray-500">ROI cao nhất</div>
                </div>
                <div class="text-center">
                    <div class="text-lg font-bold text-primary-600" id="avgROI">0%</div>
                    <div class="text-xs text-gray-500">ROI trung bình</div>
                </div>
                <div class="text-center">
                    <div class="text-lg font-bold text-error-600" id="worstROI">-0%</div>
                    <div class="text-xs text-gray-500">ROI thấp nhất</div>
                </div>
            </div>
        </x-ui.analytics-chart>

        {{-- Campaign Success Rate --}}
        <x-ui.analytics-chart
            chartId="campaignSuccessChart"
            title="Tỷ Lệ Thành Công"
            subtitle="Win rate các campaign"
            chartType="line"
            color="success"
            height="400px"
            :data="[]"
        >
            <div class="mt-4 grid grid-cols-2 gap-4">
                <div class="text-center">
                    <div class="text-lg font-bold text-success-600" id="activeWinRate">0%</div>
                    <div class="text-xs text-gray-500">Campaign hoạt động</div>
                </div>
                <div class="text-center">
                    <div class="text-lg font-bold text-info-600" id="completedWinRate">0%</div>
                    <div class="text-xs text-gray-500">Campaign hoàn thành</div>
                </div>
            </div>
        </x-ui.analytics-chart>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Campaign Types Distribution --}}
        <x-ui.analytics-chart
            chartId="campaignTypesChart"
            title="Phân Bổ Loại Campaign"
            subtitle="Theo strategy"
            chartType="doughnut"
            color="warning"
            height="350px"
            :data="[]"
        >
            <div class="mt-4 space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Manual:</span>
                    <span class="font-medium" id="manualCampaigns">0%</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Auto:</span>
                    <span class="font-medium" id="autoCampaigns">0%</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Template:</span>
                    <span class="font-medium" id="templateCampaigns">0%</span>
                </div>
            </div>
        </x-ui.analytics-chart>

        {{-- Investment by Campaign --}}
        <x-ui.analytics-chart
            chartId="investmentByCampaignChart"
            title="Đầu Tư Theo Campaign"
            subtitle="Top 10 campaigns"
            chartType="bar"
            color="info"
            height="350px"
            :data="[]"
        >
            <div class="mt-4 text-center">
                <div class="text-lg font-bold text-primary-600" id="topCampaignValue">0 VND</div>
                <div class="text-xs text-gray-500">Campaign lớn nhất</div>
            </div>
        </x-ui.analytics-chart>

        {{-- Risk vs Return Scatter --}}
        <x-ui.analytics-chart
            chartId="riskReturnScatterChart"
            title="Risk vs Return"
            subtitle="Portfolio analysis"
            chartType="scatter"
            color="error"
            height="350px"
            :data="[]"
        >
            <div class="mt-4 space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Low Risk:</span>
                    <span class="font-medium text-success-600" id="lowRiskCount">0</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">High Risk:</span>
                    <span class="font-medium text-error-600" id="highRiskCount">0</span>
                </div>
            </div>
        </x-ui.analytics-chart>
    </div>
</div>

{{-- Campaign Performance Table --}}
<div class="mb-8">
    <x-ui.card
        title="Top Performing Campaigns"
        subtitle="20 chiến dịch hiệu suất cao nhất"
    >
        <x-slot:icon>
            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
            </svg>
        </x-slot:icon>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campaign</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng Thái</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Đầu Tư</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lợi Nhuận</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ROI</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Win Rate</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="campaignPerformanceTable">
                    {{-- Data will be populated by JavaScript --}}
                </tbody>
            </table>
        </div>

        <x-slot:footer>
            <div class="flex justify-between items-center">
                <div class="text-sm text-gray-500">
                    Hiển thị <span id="campaignCount">0</span> campaigns
                </div>
                <x-ui.button size="sm" type="primary" variant="outline" href="{{ route('campaigns.index') }}">
                    Xem tất cả
                </x-ui.button>
            </div>
        </x-slot:footer>
    </x-ui.card>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Campaign Analytics Global Variables
let campaignChartInstances = {};

document.addEventListener('DOMContentLoaded', function () {
    // Initialize components
    loadCampaignMetrics();
    loadCampaignPerformance();
    initializeCampaignCharts();

    // Event listeners
    document.getElementById('campaignSelector').addEventListener('change', refreshCampaignData);
    document.getElementById('timeRangeSelector').addEventListener('change', refreshCampaignData);
});

// Load campaign metrics
function loadCampaignMetrics() {
    const campaignType = document.getElementById('campaignSelector').value;
    const timeRange = document.getElementById('timeRangeSelector').value;

    fetch(`/api/campaigns/metrics?type=${campaignType}&period=${timeRange}`)
        .then(response => response.json())
        .then(data => {
            updateCampaignMetrics(data);
        })
        .catch(error => {
            console.error('Error loading campaign metrics:', error);
        });
}

// Update campaign metrics
function updateCampaignMetrics(data) {
    document.getElementById('totalCampaigns').textContent = formatNumber(data.total_campaigns || 0);
    document.getElementById('totalInvestment').textContent = formatMoney(data.total_investment || 0);
    document.getElementById('totalProfit').textContent = formatMoney(data.total_profit || 0);
    document.getElementById('successRate').textContent = (data.success_rate || 0) + '%';

    // Update ROI indicators
    document.getElementById('bestROI').textContent = '+' + (data.best_roi || 0) + '%';
    document.getElementById('avgROI').textContent = (data.avg_roi || 0) + '%';
    document.getElementById('worstROI').textContent = (data.worst_roi || 0) + '%';

    // Update win rates
    document.getElementById('activeWinRate').textContent = (data.active_win_rate || 0) + '%';
    document.getElementById('completedWinRate').textContent = (data.completed_win_rate || 0) + '%';
}

// Load campaign performance table
function loadCampaignPerformance() {
    const campaignType = document.getElementById('campaignSelector').value;
    const timeRange = document.getElementById('timeRangeSelector').value;

    fetch(`/api/campaigns/performance?type=${campaignType}&period=${timeRange}&limit=20`)
        .then(response => response.json())
        .then(data => {
            populateCampaignTable(data);
        })
        .catch(error => {
            console.error('Error loading campaign performance:', error);
        });
}

// Populate campaign performance table
function populateCampaignTable(campaigns) {
    const tableBody = document.getElementById('campaignPerformanceTable');
    tableBody.innerHTML = '';

    campaigns.forEach(campaign => {
        const row = document.createElement('tr');
        const roi = calculateROI(campaign.investment, campaign.profit);

        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                    <div>
                        <div class="text-sm font-medium text-gray-900">${campaign.name}</div>
                        <div class="text-sm text-gray-500">${campaign.strategy}</div>
                    </div>
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColor(campaign.status)}">
                    ${getStatusText(campaign.status)}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${formatMoney(campaign.investment)}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium ${campaign.profit >= 0 ? 'text-success-600' : 'text-error-600'}">${formatMoney(campaign.profit)}</td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${roi >= 0 ? 'bg-success-100 text-success-800' : 'bg-error-100 text-error-800'}">
                    ${roi >= 0 ? '+' : ''}${roi}%
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                    <div class="w-16 bg-gray-200 rounded-full h-2">
                        <div class="bg-success-600 h-2 rounded-full" style="width: ${campaign.win_rate}%"></div>
                    </div>
                    <span class="ml-2 text-sm text-gray-600">${campaign.win_rate}%</span>
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <a href="/campaigns/${campaign.id}" class="text-primary-600 hover:text-primary-900">View</a>
            </td>
        `;
        tableBody.appendChild(row);
    });

    document.getElementById('campaignCount').textContent = campaigns.length;
}

// Initialize campaign charts
function initializeCampaignCharts() {
    initializeCampaignROIChart();
    initializeCampaignSuccessChart();
    initializeCampaignTypesChart();
    initializeInvestmentByCampaignChart();
    initializeRiskReturnScatterChart();
}

// Campaign ROI Chart
function initializeCampaignROIChart() {
    const ctx = document.getElementById('campaignROIChart');
    if (!ctx) return;

    campaignChartInstances.roi = new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: {
            labels: generateDateLabels(30),
            datasets: [{
                label: 'ROI',
                data: generateSampleData(30, -20, 50),
                borderColor: '#0891b2',
                backgroundColor: 'rgba(8, 145, 178, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: getLineChartOptions('%')
    });
}

// Campaign Success Chart
function initializeCampaignSuccessChart() {
    const ctx = document.getElementById('campaignSuccessChart');
    if (!ctx) return;

    campaignChartInstances.success = new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: {
            labels: generateDateLabels(30),
            datasets: [{
                label: 'Win Rate',
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

// Campaign Types Chart
function initializeCampaignTypesChart() {
    const ctx = document.getElementById('campaignTypesChart');
    if (!ctx) return;

    campaignChartInstances.types = new Chart(ctx.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Manual', 'Auto', 'Template'],
            datasets: [{
                data: [40, 35, 25],
                backgroundColor: ['#d97706', '#059669', '#dc2626'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: getDoughnutChartOptions()
    });
}

// Investment by Campaign Chart
function initializeInvestmentByCampaignChart() {
    const ctx = document.getElementById('investmentByCampaignChart');
    if (!ctx) return;

    campaignChartInstances.investment = new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: ['Campaign A', 'Campaign B', 'Campaign C', 'Campaign D', 'Campaign E'],
            datasets: [{
                label: 'Investment',
                data: generateSampleData(5, 100000, 500000),
                backgroundColor: '#2563eb',
                borderRadius: 4
            }]
        },
        options: getBarChartOptions('VND')
    });
}

// Risk Return Scatter Chart
function initializeRiskReturnScatterChart() {
    const ctx = document.getElementById('riskReturnScatterChart');
    if (!ctx) return;

    campaignChartInstances.riskReturn = new Chart(ctx.getContext('2d'), {
        type: 'scatter',
        data: {
            datasets: [{
                label: 'Campaigns',
                data: Array.from({ length: 20 }, () => ({
                    x: Math.random() * 50,
                    y: Math.random() * 30 - 10
                })),
                backgroundColor: 'rgba(220, 38, 38, 0.6)',
                borderColor: '#dc2626'
            }]
        },
        options: getScatterChartOptions()
    });
}

// Chart Options
function getScatterChartOptions() {
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
                title: { display: true, text: 'Risk (%)' },
                grid: { color: '#f3f4f6' },
                ticks: { color: '#6b7280', font: { size: 12 } }
            },
            y: {
                title: { display: true, text: 'Return (%)' },
                grid: { color: '#f3f4f6' },
                ticks: { color: '#6b7280', font: { size: 12 } }
            }
        }
    };
}

// Utility Functions
function getStatusColor(status) {
    const colors = {
        'active': 'bg-success-100 text-success-800',
        'completed': 'bg-primary-100 text-primary-800',
        'paused': 'bg-warning-100 text-warning-800',
        'failed': 'bg-error-100 text-error-800'
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
}

function getStatusText(status) {
    const texts = {
        'active': 'Hoạt động',
        'completed': 'Hoàn thành',
        'paused': 'Tạm dừng',
        'failed': 'Thất bại'
    };
    return texts[status] || 'Unknown';
}

function refreshCampaignData() {
    loadCampaignMetrics();
    loadCampaignPerformance();
    // Update charts with new data
    // Implementation would refresh all charts
}

// Include utility functions from main statistics page
function formatMoney(amount) {
    return new Intl.NumberFormat('vi-VN').format(amount) + ' VND';
}

function formatNumber(number) {
    return new Intl.NumberFormat('vi-VN').format(number);
}

function calculateROI(invested, returned) {
    if (invested === 0) return 0;
    return Math.round(((returned - invested) / invested) * 100);
}

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

// Chart options helpers (reuse from statistics page)
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
</script>
@endpush
