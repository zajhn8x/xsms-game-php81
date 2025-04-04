@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-7xl mx-auto">
            <!-- Tổng quan -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900">Tổng số lần đặt</h3>
                    <p class="text-3xl font-bold text-blue-600" id="total-bets">0</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900">Tổng tiền đã đặt</h3>
                    <p class="text-3xl font-bold text-green-600" id="total-amount">0đ</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900">Số lần trúng</h3>
                    <p class="text-3xl font-bold text-purple-600" id="total-wins">0</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900">Tổng tiền thắng</h3>
                    <p class="text-3xl font-bold text-red-600" id="total-winnings">0đ</p>
                </div>
            </div>

            <!-- Lịch sử đặt cược -->
            <div class="bg-white rounded-lg shadow mb-8">
                <div class="p-6">
                    <h2 class="text-xl font-bold mb-4">Lịch sử đặt cược</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ngày
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Số lô
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Số tiền đặt
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Kết quả
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tiền thắng
                                </th>
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="bet-history">
                            <!-- Dữ liệu sẽ được thêm vào đây bằng JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Biểu đồ thống kê -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6">
                    <h2 class="text-xl font-bold mb-4">Phân tích xu hướng</h2>
                    <canvas id="trendsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Load thống kê
            fetch('/api/statistics')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('total-bets').textContent = data.total_bets;
                    document.getElementById('total-amount').textContent = formatMoney(data.total_amount_bet);
                    document.getElementById('total-wins').textContent = data.total_wins;
                    document.getElementById('total-winnings').textContent = formatMoney(data.total_winnings);
                });

            // Load lịch sử đặt cược
            fetch('/api/bet-history')
                .then(response => response.json())
                .then(data => {
                    const historyTable = document.getElementById('bet-history');
                    data.forEach(bet => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap">${formatDate(bet.bet_date)}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${bet.lo_number}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${formatMoney(bet.amount)}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${bet.is_win ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                            ${bet.is_win ? 'Trúng' : 'Trượt'}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">${formatMoney(bet.win_amount)}</td>
                `;
                        historyTable.appendChild(row);
                    });
                });

            // Hàm format tiền
            function formatMoney(amount) {
                return new Intl.NumberFormat('vi-VN', {style: 'currency', currency: 'VND'}).format(amount);
            }

            // Hàm format ngày
            function formatDate(date) {
                return new Date(date).toLocaleDateString('vi-VN');
            }

            // Vẽ biểu đồ xu hướng
            const ctx = document.getElementById('trendsChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'],
                    datasets: [{
                        label: 'Tỷ lệ thắng',
                        data: [65, 59, 80, 81, 56, 55, 40],
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        });
    </script>
@endpush
