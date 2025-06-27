@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8 space-y-6">
    <!-- Page Header -->
    <div class="compass-fade-in">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold compass-text-gradient">Quản lý Chiến dịch</h1>
                <p class="text-gray-600 mt-1">Theo dõi và quản lý tất cả chiến dịch cược của bạn</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <button class="compass-btn-secondary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Xuất báo cáo
                </button>
                <a href="{{ route('campaigns.create') }}" class="compass-btn-primary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Tạo chiến dịch mới
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 compass-slide-up">
        <div class="compass-stat-card">
            <div class="compass-stat-value">{{ $campaigns->count() }}</div>
            <div class="compass-stat-label">Tổng chiến dịch</div>
            <div class="compass-stat-change-positive">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                </svg>
                +{{ $campaigns->where('created_at', '>=', now()->subDays(7))->count() }} tuần này
            </div>
        </div>

        <div class="compass-stat-card">
            <div class="compass-stat-value">{{ $campaigns->where('status', 'running')->count() }}</div>
            <div class="compass-stat-label">Đang hoạt động</div>
            <div class="compass-stat-change-neutral">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                Thời gian thực
            </div>
        </div>

        <div class="compass-stat-card">
            <div class="compass-stat-value">{{ number_format($campaigns->sum('total_profit')) }}đ</div>
            <div class="compass-stat-label">Tổng lợi nhuận</div>
            <div class="{{ $campaigns->sum('total_profit') >= 0 ? 'compass-stat-change-positive' : 'compass-stat-change-negative' }}">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $campaigns->sum('total_profit') >= 0 ? 'M5 10l7-7m0 0l7 7m-7-7v18' : 'M19 14l-7 7m0 0l-7-7m7 7V3' }}"></path>
                </svg>
                {{ $campaigns->sum('total_profit') >= 0 ? '+' : '' }}{{ number_format($campaigns->sum('total_profit')) }}đ
            </div>
        </div>

        <div class="compass-stat-card">
            <div class="compass-stat-value">{{ $campaigns->avg('win_rate') ? number_format($campaigns->avg('win_rate'), 1) : 0 }}%</div>
            <div class="compass-stat-label">Tỷ lệ thắng TB</div>
            <div class="compass-stat-change-positive">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                Trung bình
            </div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="compass-card compass-slide-up">
        <div class="compass-card-body">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4" x-data="campaignFilters()">
                <!-- Search -->
                <div>
                    <label class="compass-label">Tìm kiếm</label>
                    <div class="relative">
                        <input type="text"
                               x-model="filters.search"
                               @input="applyFilters()"
                               class="compass-input pl-10"
                               placeholder="Tên chiến dịch...">
                        <svg class="w-4 h-4 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>

                <!-- Status Filter -->
                <div>
                    <label class="compass-label">Trạng thái</label>
                    <select x-model="filters.status" @change="applyFilters()" class="compass-select">
                        <option value="">Tất cả</option>
                        <option value="running">Đang chạy</option>
                        <option value="paused">Tạm dừng</option>
                        <option value="finished">Đã kết thúc</option>
                        <option value="waiting">Chờ bắt đầu</option>
                    </select>
                </div>

                <!-- Date Range -->
                <div>
                    <label class="compass-label">Thời gian</label>
                    <select x-model="filters.dateRange" @change="applyFilters()" class="compass-select">
                        <option value="">Tất cả</option>
                        <option value="today">Hôm nay</option>
                        <option value="week">Tuần này</option>
                        <option value="month">Tháng này</option>
                    </select>
                </div>

                <!-- Sort -->
                <div>
                    <label class="compass-label">Sắp xếp</label>
                    <select x-model="filters.sort" @change="applyFilters()" class="compass-select">
                        <option value="created_at_desc">Mới nhất</option>
                        <option value="created_at_asc">Cũ nhất</option>
                        <option value="profit_desc">Lợi nhuận cao</option>
                        <option value="profit_asc">Lợi nhuận thấp</option>
                        <option value="name_asc">Tên A-Z</option>
                        <option value="name_desc">Tên Z-A</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Campaigns Table -->
    <div class="compass-card compass-slide-up">
        <div class="compass-card-header">
            <h3 class="text-lg font-semibold">Danh sách chiến dịch</h3>
            <div class="flex items-center space-x-2">
                <button class="compass-btn-secondary compass-btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                    </svg>
                </button>
                <button class="compass-btn-secondary compass-btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h2a2 2 0 002-2z"></path>
                    </svg>
                </button>
            </div>
        </div>

        <div class="compass-table">
            <table class="w-full" id="campaignsTable">
                <thead>
                    <tr>
                        <th class="compass-table th">
                            <input type="checkbox" class="compass-checkbox" id="selectAll">
                        </th>
                        <th class="compass-table th">Chiến dịch</th>
                        <th class="compass-table th">Trạng thái</th>
                        <th class="compass-table th">Thời gian</th>
                        <th class="compass-table th">Hiệu suất</th>
                        <th class="compass-table th">Tiến độ</th>
                        <th class="compass-table th">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($campaigns as $campaign)
                    <tr class="campaign-row" data-campaign-id="{{ $campaign->id }}">
                        <td class="compass-table td">
                            <input type="checkbox" class="compass-checkbox campaign-checkbox" value="{{ $campaign->id }}">
                        </td>
                        <td class="compass-table td">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center text-white font-bold text-sm mr-3">
                                    {{ strtoupper(substr($campaign->name, 0, 2)) }}
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">
                                        <a href="{{ route('campaigns.show', $campaign->id) }}" class="hover:text-primary-600 transition-colors">
                                            {{ $campaign->name }}
                                        </a>
                                    </div>
                                    <div class="text-sm text-gray-500">ID: {{ $campaign->id }}</div>
                                    @if($campaign->description)
                                        <div class="text-xs text-gray-400 mt-1 max-w-xs truncate">{{ $campaign->description }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="compass-table td">
                            @switch($campaign->status)
                                @case('running')
                                    <span class="campaign-status-active">
                                        <span class="status-dot bg-green-500"></span>
                                        Đang chạy
                                    </span>
                                    @break
                                @case('paused')
                                    <span class="campaign-status-paused">
                                        <span class="status-dot bg-yellow-500"></span>
                                        Tạm dừng
                                    </span>
                                    @break
                                @case('finished')
                                    <span class="campaign-status-finished">
                                        <span class="status-dot bg-gray-500"></span>
                                        Đã kết thúc
                                    </span>
                                    @break
                                @case('waiting')
                                    <span class="campaign-status-waiting">
                                        <span class="status-dot bg-blue-500"></span>
                                        Chờ bắt đầu
                                    </span>
                                    @break
                                @default
                                    <span class="campaign-status-unknown">
                                        <span class="status-dot bg-gray-400"></span>
                                        {{ ucfirst($campaign->status) }}
                                    </span>
                            @endswitch
                        </td>
                        <td class="compass-table td">
                            <div class="text-sm">
                                <div class="font-medium">{{ $campaign->start_date->format('d/m/Y') }}</div>
                                <div class="text-gray-500">
                                    đến {{ $campaign->end_date ? $campaign->end_date->format('d/m/Y') : 'Không giới hạn' }}
                                </div>
                                <div class="text-xs text-gray-400 mt-1">
                                    {{ $campaign->created_at->diffForHumans() }}
                                </div>
                            </div>
                        </td>
                        <td class="compass-table td">
                            <div class="space-y-1">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600">Lợi nhuận:</span>
                                    <span class="{{ $campaign->total_profit >= 0 ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold' }}">
                                        {{ $campaign->total_profit >= 0 ? '+' : '' }}{{ number_format($campaign->total_profit) }}đ
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600">Tỷ lệ thắng:</span>
                                    <span class="font-medium">{{ $campaign->win_rate }}%</span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600">Tổng cược:</span>
                                    <span class="text-gray-700">{{ number_format($campaign->total_bet) }}đ</span>
                                </div>
                            </div>
                        </td>
                        <td class="compass-table td">
                            @php
                                $progress = 0;
                                if ($campaign->days > 0) {
                                    $daysElapsed = $campaign->start_date->diffInDays(now());
                                    $progress = min(100, max(0, ($daysElapsed / $campaign->days) * 100));
                                }
                            @endphp
                            <div class="w-full">
                                <div class="flex justify-between text-xs text-gray-600 mb-1">
                                    <span>Tiến độ</span>
                                    <span>{{ number_format($progress, 1) }}%</span>
                                </div>
                                <div class="compass-progress">
                                    <div class="compass-progress-bar" style="width: {{ $progress }}%"></div>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    {{ $campaign->days ? $campaign->days . ' ngày' : 'Không giới hạn' }}
                                </div>
                            </div>
                        </td>
                        <td class="compass-table td">
                            <div class="flex items-center space-x-2">
                                <!-- View -->
                                <a href="{{ route('campaigns.show', $campaign->id) }}"
                                   class="compass-action-btn compass-action-btn-view"
                                   title="Xem chi tiết">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>

                                <!-- Control Buttons -->
                                @if($campaign->status == 'running')
                                    <button type="button"
                                            class="compass-action-btn compass-action-btn-warning"
                                            onclick="pauseCampaign({{ $campaign->id }})"
                                            title="Tạm dừng">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </button>
                                @elseif($campaign->status == 'paused')
                                    <button type="button"
                                            class="compass-action-btn compass-action-btn-success"
                                            onclick="runCampaign({{ $campaign->id }})"
                                            title="Tiếp tục">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h.01M15 14h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </button>
                                @endif

                                @if($campaign->status != 'finished')
                                    <button type="button"
                                            class="compass-action-btn compass-action-btn-danger"
                                            onclick="finishCampaign({{ $campaign->id }})"
                                            title="Kết thúc">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"></path>
                                        </svg>
                                    </button>
                                @endif

                                <!-- More Actions Dropdown -->
                                <div x-data="{ open: false }" class="relative">
                                    <button @click="open = !open"
                                            class="compass-action-btn compass-action-btn-neutral"
                                            title="Thêm hành động">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                                        </svg>
                                    </button>

                                    <div x-show="open"
                                         @click.away="open = false"
                                         x-transition
                                         class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg border border-gray-200 z-10"
                                         style="display: none;">
                                        <div class="py-1">
                                            <a href="{{ route('campaigns.bet.form', $campaign) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                </svg>
                                                Đặt cược
                                            </a>
                                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                </svg>
                                                Sao chép
                                            </a>
                                            <button onclick="deleteCampaign({{ $campaign->id }})" class="w-full text-left block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                                <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                                Xóa
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="compass-table td text-center py-12">
                            <div class="text-gray-400">
                                <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                </svg>
                                <h3 class="text-lg font-medium text-gray-900 mb-1">Chưa có chiến dịch nào</h3>
                                <p class="text-gray-500 mb-4">Bắt đầu tạo chiến dịch đầu tiên của bạn để quản lý các hoạt động cược.</p>
                                <a href="{{ route('campaigns.create') }}" class="compass-btn-primary">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Tạo chiến dịch đầu tiên
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($campaigns, 'hasPages') && $campaigns->hasPages())
        <div class="compass-card-footer">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Hiển thị {{ $campaigns->firstItem() }} đến {{ $campaigns->lastItem() }} trong tổng số {{ $campaigns->total() }} kết quả
                </div>
                {{ $campaigns->links() }}
            </div>
        </div>
        @endif
    </div>

    <!-- Bulk Actions -->
    <div id="bulkActions" class="fixed bottom-6 left-1/2 transform -translate-x-1/2 compass-card-shadow bg-white rounded-lg px-6 py-4 flex items-center space-x-4 transition-all duration-300 opacity-0 translate-y-full pointer-events-none">
        <span class="text-sm font-medium text-gray-700" id="selectedCount">0 chiến dịch được chọn</span>
        <div class="flex space-x-2">
            <button class="compass-btn-secondary compass-btn-sm" onclick="bulkAction('pause')">Tạm dừng</button>
            <button class="compass-btn-secondary compass-btn-sm" onclick="bulkAction('run')">Chạy</button>
            <button class="compass-btn-danger compass-btn-sm" onclick="bulkAction('delete')">Xóa</button>
        </div>
        <button onclick="clearSelection()" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
</div>

@push('scripts')
<script>
// Campaign Management JavaScript
document.addEventListener('DOMContentLoaded', function() {
    initializeCampaignManagement();
});

function initializeCampaignManagement() {
    setupBulkSelection();
    setupTableActions();
}

// Bulk Selection
function setupBulkSelection() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.campaign-checkbox');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');

    selectAll?.addEventListener('change', function() {
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBulkActions();
    });

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActions);
    });

    function updateBulkActions() {
        const selected = document.querySelectorAll('.campaign-checkbox:checked');
        selectedCount.textContent = `${selected.length} chiến dịch được chọn`;

        if (selected.length > 0) {
            bulkActions.classList.remove('opacity-0', 'translate-y-full', 'pointer-events-none');
        } else {
            bulkActions.classList.add('opacity-0', 'translate-y-full', 'pointer-events-none');
        }
    }
}

// Campaign Actions
function runCampaign(id) {
    confirmAction('Bạn có chắc muốn chạy chiến dịch này?', () => {
        performAction(`/campaigns/${id}/run`, 'POST', 'Đã bắt đầu chạy chiến dịch');
    });
}

function pauseCampaign(id) {
    confirmAction('Bạn có chắc muốn tạm dừng chiến dịch này?', () => {
        performAction(`/campaigns/${id}/pause`, 'POST', 'Đã tạm dừng chiến dịch');
    });
}

function finishCampaign(id) {
    confirmAction('Bạn có chắc muốn kết thúc chiến dịch này?', () => {
        performAction(`/campaigns/${id}/finish`, 'POST', 'Đã kết thúc chiến dịch');
    });
}

function deleteCampaign(id) {
    confirmAction('Bạn có chắc muốn xóa chiến dịch này? Hành động này không thể hoàn tác.', () => {
        performAction(`/campaigns/${id}`, 'DELETE', 'Đã xóa chiến dịch');
    });
}

// Bulk Actions
function bulkAction(action) {
    const selected = Array.from(document.querySelectorAll('.campaign-checkbox:checked')).map(cb => cb.value);
    if (selected.length === 0) return;

    const actionText = {
        pause: 'tạm dừng',
        run: 'chạy',
        delete: 'xóa'
    };

    confirmAction(`Bạn có chắc muốn ${actionText[action]} ${selected.length} chiến dịch đã chọn?`, () => {
        // Implement bulk action
        console.log(`Bulk ${action} for campaigns:`, selected);
    });
}

function clearSelection() {
    document.querySelectorAll('.campaign-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('selectAll').checked = false;
    document.getElementById('bulkActions').classList.add('opacity-0', 'translate-y-full', 'pointer-events-none');
}

// Utility Functions
function performAction(url, method, successMessage) {
    const options = {
        method: method,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    };

    fetch(url, options)
        .then(response => response.json())
        .then(data => {
            showNotification(successMessage, 'success');
            setTimeout(() => window.location.reload(), 1000);
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Có lỗi xảy ra!', 'error');
        });
}

function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

function showNotification(message, type = 'info') {
    // Implement notification system
    console.log(`${type.toUpperCase()}: ${message}`);
}

// Filters
function campaignFilters() {
    return {
        filters: {
            search: '',
            status: '',
            dateRange: '',
            sort: 'created_at_desc'
        },

        applyFilters() {
            // Implement filtering logic
            console.log('Applying filters:', this.filters);
        }
    };
}

// Table Setup
function setupTableActions() {
    // Add any additional table interactions here
}
</script>

<style>
/* Campaign Status Styles */
.campaign-status-active,
.campaign-status-paused,
.campaign-status-finished,
.campaign-status-waiting,
.campaign-status-unknown {
    @apply inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium;
}

.campaign-status-active {
    @apply bg-green-100 text-green-800;
}

.campaign-status-paused {
    @apply bg-yellow-100 text-yellow-800;
}

.campaign-status-finished {
    @apply bg-gray-100 text-gray-800;
}

.campaign-status-waiting {
    @apply bg-blue-100 text-blue-800;
}

.campaign-status-unknown {
    @apply bg-gray-100 text-gray-600;
}

.status-dot {
    @apply w-2 h-2 rounded-full mr-2;
    animation: pulse 2s infinite;
}

/* Action Buttons */
.compass-action-btn {
    @apply inline-flex items-center justify-center w-8 h-8 rounded-lg transition-all duration-200 hover:scale-105;
}

.compass-action-btn-view {
    @apply bg-blue-50 text-blue-600 hover:bg-blue-100;
}

.compass-action-btn-success {
    @apply bg-green-50 text-green-600 hover:bg-green-100;
}

.compass-action-btn-warning {
    @apply bg-yellow-50 text-yellow-600 hover:bg-yellow-100;
}

.compass-action-btn-danger {
    @apply bg-red-50 text-red-600 hover:bg-red-100;
}

.compass-action-btn-neutral {
    @apply bg-gray-50 text-gray-600 hover:bg-gray-100;
}

/* Table Enhancements */
.campaign-row:hover {
    @apply bg-gray-50;
}

/* Animation */
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: .5;
    }
}
</style>
@endpush
@endsection
