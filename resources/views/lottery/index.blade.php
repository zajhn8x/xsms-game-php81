
@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-3xl font-bold mb-6">Hệ thống chọn cầu lô tối ưu</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Kết quả xổ số mới nhất -->
            <div class="border rounded-lg p-4">
                <h2 class="text-xl font-semibold mb-4">Kết quả mới nhất</h2>
                <div id="latest-results">
                    <!-- Results will be loaded here -->
                </div>
            </div>

            <!-- Cầu lô đề xuất -->
            <div class="border rounded-lg p-4">
                <h2 class="text-xl font-semibold mb-4">Cầu lô đề xuất</h2>
                <div id="suggested-numbers">
                    <!-- Suggestions will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Thống kê và phân tích -->
        <div class="mt-8">
            <h2 class="text-xl font-semibold mb-4">Thống kê và phân tích</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="stats-card">
                    <h3>Tổng số cầu</h3>
                    <p id="total-cau">0</p>
                </div>
                <div class="stats-card">
                    <h3>Tỷ lệ trúng</h3>
                    <p id="win-rate">0%</p>
                </div>
                <div class="stats-card">
                    <h3>Số người đang theo dõi</h3>
                    <p id="followers">0</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load latest results
    fetch('/api/lottery-results')
        .then(response => response.json())
        .then(data => {
            // Update UI with results
        });

    // Load suggestions
    fetch('/api/lottery-cau-lo')
        .then(response => response.json())
        .then(data => {
            // Update UI with suggestions
        });
});
</script>
@endpush
