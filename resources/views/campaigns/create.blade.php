@extends('layouts.app')

@section('title', 'Tạo Chiến Dịch Mới')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="campaignForm()">
    {{-- Page Header --}}
    <div class="compass-fade-in mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Tạo chiến dịch mới</h1>
                <p class="mt-1 text-gray-600">Thiết lập và khởi chạy chiến dịch phân tích của bạn.</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <a href="{{ route('campaigns.index') }}" class="compass-btn-secondary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Quay lại danh sách
                </a>
            </div>
        </div>
    </div>

    {{-- Form --}}
    <div class="compass-card max-w-4xl mx-auto compass-slide-up">
        <form action="{{ route('campaigns.store') }}" method="POST" class="p-0">
            @csrf
            <div class="compass-card-body">
                @if($errors->any())
                    <div class="compass-alert-error mb-6">
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="space-y-6">
                    {{-- Basic Info --}}
                    <div class="border-b border-gray-200 pb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Thông tin cơ bản</h3>
                        <div class="space-y-4">
                            <div>
                                <label for="name" class="compass-label">Tên chiến dịch</label>
                                <input type="text" id="name" name="name" class="compass-input" value="{{ old('name') }}" required placeholder="VD: Chiến dịch săn lô gan tháng 7">
                            </div>
                            <div>
                                <label for="description" class="compass-label">Mô tả</label>
                                <textarea id="description" name="description" class="compass-textarea" rows="3" placeholder="Mô tả ngắn về mục tiêu và phương pháp của chiến dịch">{{ old('description') }}</textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Configuration --}}
                    <div class="border-b border-gray-200 pb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Cấu hình</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="start_date" class="compass-label">Ngày bắt đầu</label>
                                <input type="date" id="start_date" name="start_date" class="compass-input" value="{{ old('start_date', date('Y-m-d')) }}" required x-model="startDate" @change="updateDays">
                            </div>
                            <div>
                                <label for="end_date" class="compass-label">Ngày kết thúc</label>
                                <input type="date" id="end_date" name="end_date" class="compass-input" value="{{ old('end_date') }}" x-model="endDate" @change="updateDays">
                            </div>
                            <div>
                                <label for="bet_type" class="compass-label">Loại chiến dịch</label>
                                <div x-data="customSelect({
                                        options: [
                                            { value: 'manual', text: 'Thủ công' },
                                            { value: 'auto_heatmap', text: 'Tự động Heatmap' },
                                            { value: 'auto_streak', text: 'Tự động Top Streak' },
                                            { value: 'auto_rebound', text: 'Tự động Rebound' }
                                        ],
                                        initialValue: '{{ old('bet_type', 'manual') }}',
                                        name: 'bet_type'
                                    })"
                                     @click.outside="open = false"
                                     class="relative">
                                    <input type="hidden" name="bet_type" x-model="selectedValue">
                                    <button type="button" @click="open = !open" class="compass-select w-full text-left">
                                        <span x-text="selectedText"></span>
                                        <svg class="w-5 h-5 text-gray-400 absolute right-3 top-1/2 -translate-y-1/2 transition-transform" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </button>
                                    <div x-show="open"
                                         x-transition:enter="transition ease-out duration-100"
                                         x-transition:enter-start="opacity-0 scale-95"
                                         x-transition:enter-end="opacity-100 scale-100"
                                         x-transition:leave="transition ease-in duration-75"
                                         x-transition:leave-start="opacity-100 scale-100"
                                         x-transition:leave-end="opacity-0 scale-95"
                                         class="absolute z-10 mt-2 w-full bg-white rounded-md shadow-lg border border-gray-200"
                                         style="display: none;">
                                        <ul class="py-1">
                                            <template x-for="option in options" :key="option.value">
                                                <li @click="selectOption(option)"
                                                    class="px-4 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-700 cursor-pointer flex items-center justify-between">
                                                    <span x-text="option.text"></span>
                                                    <svg x-show="option.value === selectedValue" class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label for="status" class="compass-label">Trạng thái khởi tạo</label>
                                <select id="status" name="status" class="compass-select">
                                    <option value="active">Đang chạy</option>
                                    <option value="paused">Tạm dừng</option>
                                </select>
                            </div>
                             <div class="md:col-span-2">
                                <label class="compass-label">Số ngày chạy (dự kiến)</label>
                                <div class="mt-1 flex items-center">
                                    <input type="text" id="days" class="compass-input bg-gray-100 w-24 text-center" x-model="days" readonly>
                                    <span class="ml-3 text-gray-600">ngày</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Play Type --}}
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Phương thức chơi</h3>
                        <div class="flex items-center space-x-6">
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" name="play_type" value="manual" class="h-4 w-4 text-primary-600 border-gray-300 focus:ring-primary-500" checked @change="playType = 'manual'">
                                <span class="ml-2 text-gray-700">Chơi thủ công</span>
                            </label>
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" name="play_type" value="auto" class="h-4 w-4 text-primary-600 border-gray-300 focus:ring-primary-500" @change="playType = 'auto'">
                                <span class="ml-2 text-gray-700">Chơi tự động</span>
                            </label>
                        </div>

                        <div x-show="playType === 'auto'" x-transition class="mt-6 p-6 bg-primary-50 border border-primary-200 rounded-lg">
                            <h4 class="text-md font-semibold text-primary-800 mb-4">Cấu hình chơi tự động</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="insight_type" class="compass-label">Loại insight</label>
                                    <select id="insight_type" name="insight_type" class="compass-select">
                                        <option value="long_run_stop">Long Run Stop</option>
                                        <option value="rebound_after_long_run">Rebound After Long Run</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="day_stop" class="compass-label">Ngày dừng</label>
                                    <select id="day_stop" name="day_stop" class="compass-select">
                                        <option value="1">Ngày 1</option>
                                        <option value="2" selected>Ngày 2</option>
                                        <option value="3">Ngày 3</option>
                                    </select>
                                </div>
                                <div class="md:col-span-2">
                                    <label for="bet_amount" class="compass-label">Số tiền cược mỗi lần (VND)</label>
                                    <input type="number" id="bet_amount" name="bet_amount" class="compass-input" value="10000" min="1000" step="1000">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="compass-card-footer flex justify-end space-x-3">
                <a href="{{ route('campaigns.index') }}" class="compass-btn-secondary">Hủy</a>
                <button type="submit" class="compass-btn-primary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Lưu và bắt đầu chiến dịch
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function customSelect(config) {
    return {
        open: false,
        options: config.options || [],
        selectedValue: config.initialValue || '',
        selectedText: '',
        name: config.name || 'custom_select',

        init() {
            const initialOption = this.options.find(opt => opt.value === this.selectedValue);
            this.selectedText = initialOption ? initialOption.text : (this.options.length > 0 ? this.options[0].text : 'Select an option');
            if (!this.selectedValue && this.options.length > 0) {
                this.selectedValue = this.options[0].value;
            }
        },

        selectOption(option) {
            this.selectedValue = option.value;
            this.selectedText = option.text;
            this.open = false;
        }
    }
}

function campaignForm() {
    return {
        playType: 'manual',
        startDate: '{{ old('start_date', date('Y-m-d')) }}',
        endDate: '{{ old('end_date') }}',
        days: 30,

        init() {
            this.updateDays();

            // Set initial playType based on old input
            const oldPlayType = '{{ old('play_type') }}';
            if (oldPlayType) {
                this.playType = oldPlayType;
            }
        },

        updateDays() {
            if (this.startDate && this.endDate) {
                const start = new Date(this.startDate);
                const end = new Date(this.endDate);
                if (end < start) {
                    this.days = 'Lỗi';
                    return;
                }
                const diff = Math.floor((end - start) / (1000 * 60 * 60 * 24)) + 1;
                this.days = diff > 0 ? diff : 1;
            } else {
                this.days = 'N/A';
            }
        }
    }
}
</script>
@endpush
@endsection
