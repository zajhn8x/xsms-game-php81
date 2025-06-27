<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $campaign->name }} - Chiến Dịch Chia Sẻ</title>

    <!-- Meta for Social Sharing -->
    <meta property="og:title" content="{{ $campaign->name }} - Chiến Dịch Xổ Số">
    <meta property="og:description" content="Xem chiến dịch {{ $campaign->strategy_type }} với tỷ lệ thắng {{ number_format(($campaign->bets->where('is_win', true)->count() / max($campaign->bets->count(), 1)) * 100, 1) }}%">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ request()->url() }}">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Compass Theme CSS -->
    <link href="{{ asset('css/compass-theme.css') }}" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        .compass-gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
        }

        .compass-gradient-bg::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Cg fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Cpath d='m25 0 25 25-25 25v-50zm50 0v50l-25-25 25-25z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
            opacity: 0.1;
        }
    </style>
</head>

<body class="bg-gray-50">
    <!-- Header -->
    <header class="compass-gradient-bg text-white py-8 relative z-10">
        <div class="container mx-auto px-4">
            <div class="text-center">
                <div class="inline-flex items-center mb-4">
                    <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-chart-line text-2xl"></i>
                    </div>
                    <div class="text-left">
                        <h1 class="text-2xl md:text-3xl font-bold">{{ $campaign->name }}</h1>
                        <p class="text-white/80">Được chia sẻ bởi {{ $campaign->user->name }}</p>
                    </div>
                </div>

                <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4 inline-block">
                    <div class="flex items-center space-x-6 text-sm">
                        <div>
                            <i class="fas fa-calendar-alt mr-2"></i>
                            {{ \Carbon\Carbon::parse($campaign->created_at)->format('d/m/Y') }}
                        </div>
                        <div>
                            <i class="fas fa-tag mr-2"></i>
                            {{ ucfirst($campaign->strategy_type) }}
                        </div>
                        <div>
                            <i class="fas fa-eye mr-2"></i>
                            {{ $share_stats['views'] ?? 0 }} lượt xem
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8 -mt-6 relative z-20">
        <!-- Campaign Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Số Dư -->
            <div class="compass-card bg-gradient-to-br from-blue-50 to-blue-100">
                <div class="compass-card-body text-center">
                    <div class="text-3xl font-bold text-blue-900 mb-2">
                        {{ number_format($campaign->current_balance) }}đ
                    </div>
                    <div class="text-sm text-blue-700">Số dư hiện tại</div>
                    <div class="mt-2">
                        <i class="fas fa-wallet text-blue-600 text-lg"></i>
                    </div>
                </div>
            </div>

            <!-- Tổng Cược -->
            <div class="compass-card bg-gradient-to-br from-green-50 to-green-100">
                <div class="compass-card-body text-center">
                    <div class="text-3xl font-bold text-green-900 mb-2">
                        {{ $campaign->bets->count() }}
                    </div>
                    <div class="text-sm text-green-700">Tổng số cược</div>
                    <div class="mt-2">
                        <i class="fas fa-coins text-green-600 text-lg"></i>
                    </div>
                </div>
            </div>

            <!-- Tỷ Lệ Thắng -->
            @php
                $totalBets = $campaign->bets->count();
                $winningBets = $campaign->bets->where('is_win', true)->count();
                $winRate = $totalBets > 0 ? ($winningBets / $totalBets) * 100 : 0;
            @endphp
            <div class="compass-card bg-gradient-to-br from-yellow-50 to-yellow-100">
                <div class="compass-card-body text-center">
                    <div class="text-3xl font-bold text-yellow-900 mb-2">
                        {{ number_format($winRate, 1) }}%
                    </div>
                    <div class="text-sm text-yellow-700">Tỷ lệ thắng</div>
                    <div class="mt-2">
                        <i class="fas fa-percentage text-yellow-600 text-lg"></i>
                    </div>
                </div>
            </div>

            <!-- Lợi Nhuận -->
            @php
                $profit = $campaign->current_balance - $campaign->initial_balance;
            @endphp
            <div class="compass-card bg-gradient-to-br from-purple-50 to-purple-100">
                <div class="compass-card-body text-center">
                    <div class="text-3xl font-bold text-purple-900 mb-2">
                        {{ $profit >= 0 ? '+' : '' }}{{ number_format($profit) }}đ
                    </div>
                    <div class="text-sm text-purple-700">
                        {{ $profit >= 0 ? '📈 Lãi' : '📉 Lỗ' }}
                    </div>
                    <div class="mt-2">
                        <i class="fas {{ $profit >= 0 ? 'fa-arrow-trend-up text-green-600' : 'fa-arrow-trend-down text-red-600' }} text-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Campaign Info & Recent Bets -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Campaign Information -->
            <div class="compass-card">
                <div class="compass-card-header">
                    <h3 class="text-lg font-semibold">📋 Thông Tin Chiến Dịch</h3>
                </div>
                <div class="compass-card-body space-y-4">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Tên chiến dịch:</span>
                        <span class="font-semibold">{{ $campaign->name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Chiến lược:</span>
                        <span class="compass-badge compass-badge-primary">{{ ucfirst($campaign->strategy_type) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Ngân sách ban đầu:</span>
                        <span class="font-semibold">{{ number_format($campaign->initial_balance) }}đ</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Trạng thái:</span>
                        <span class="compass-badge {{ $campaign->status === 'active' ? 'compass-badge-success' : ($campaign->status === 'paused' ? 'compass-badge-warning' : 'compass-badge-secondary') }}">
                            {{ ucfirst($campaign->status) }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Người tạo:</span>
                        <span class="font-semibold">{{ $campaign->user->name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Ngày tạo:</span>
                        <span class="font-semibold">{{ \Carbon\Carbon::parse($campaign->created_at)->format('d/m/Y H:i') }}</span>
                    </div>

                    @if($campaign->description)
                        <hr>
                        <div>
                            <span class="text-gray-600">Mô tả:</span>
                            <p class="mt-2 text-gray-800">{{ $campaign->description }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Recent Betting History -->
            <div class="compass-card">
                <div class="compass-card-header">
                    <h3 class="text-lg font-semibold">🎯 Lịch Sử Cược Gần Đây</h3>
                </div>
                <div class="compass-card-body">
                    @if($campaign->bets && $campaign->bets->count() > 0)
                        <div class="space-y-3">
                            @foreach($campaign->bets->take(10) as $bet)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-bold">
                                                {{ $bet->lo_number }}
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium">
                                                    {{ \Carbon\Carbon::parse($bet->bet_date)->format('d/m/Y') }}
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    {{ number_format($bet->amount) }}đ
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        @if($bet->is_win)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                ✅ +{{ number_format($bet->win_amount) }}đ
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                ❌ Thua
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <div class="text-gray-400 text-4xl mb-4">🎯</div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Chưa có cược nào</h3>
                            <p class="text-gray-600">Chiến dịch này chưa có lịch sử đặt cược.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Share Statistics -->
        <div class="mt-8">
            <div class="compass-card">
                <div class="compass-card-header">
                    <h3 class="text-lg font-semibold">📊 Thống Kê Chia Sẻ</h3>
                </div>
                <div class="compass-card-body">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600">{{ $share_stats['views'] ?? 0 }}</div>
                            <div class="text-sm text-gray-600">Lượt xem</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600">{{ $share_stats['clicks'] ?? 0 }}</div>
                            <div class="text-sm text-gray-600">Lượt click</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-purple-600">{{ $share_stats['shares'] ?? 0 }}</div>
                            <div class="text-sm text-gray-600">Lượt chia sẻ</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-orange-600">{{ $share_stats['unique_visitors'] ?? 0 }}</div>
                            <div class="text-sm text-gray-600">Người dùng duy nhất</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="mt-8 text-center">
            <div class="compass-card bg-gradient-to-r from-blue-500 to-purple-600 text-white">
                <div class="compass-card-body py-8">
                    <h3 class="text-2xl font-bold mb-4">🚀 Bạn cũng muốn tạo chiến dịch tương tự?</h3>
                    <p class="text-white/90 mb-6 max-w-2xl mx-auto">
                        Tham gia hệ thống phân tích xổ số thông minh để tạo chiến dịch của riêng bạn và chia sẻ với cộng đồng.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                        <a href="{{ route('register') }}" class="compass-btn bg-white text-blue-600 hover:bg-gray-100 border-0 px-8 py-3">
                            <i class="fas fa-user-plus mr-2"></i>Đăng Ký Ngay
                        </a>
                        <a href="{{ route('home') }}" class="compass-btn compass-btn-outline border-white text-white hover:bg-white hover:text-blue-600 px-8 py-3">
                            <i class="fas fa-home mr-2"></i>Về Trang Chủ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-16">
        <div class="container mx-auto px-4 text-center">
            <div class="flex items-center justify-center mb-4">
                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center mr-3">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3 class="text-lg font-semibold">Hệ Thống lựa trọn cầu lô</h3>
            </div>
            <p class="text-gray-400 mb-4">Hệ thống phân tích xổ số thông minh</p>
            <div class="flex justify-center space-x-6">
                <a href="#" class="text-gray-400 hover:text-white">
                    <i class="fab fa-facebook"></i>
                </a>
                <a href="#" class="text-gray-400 hover:text-white">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="#" class="text-gray-400 hover:text-white">
                    <i class="fab fa-youtube"></i>
                </a>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        // Track page view
        fetch('/api/social/track-view', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                campaign_id: {{ $campaign->id }},
                page_url: window.location.href
            })
        }).catch(() => {}); // Silent fail

        // Social sharing functions
        function shareOnFacebook() {
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(window.location.href)}`,
                '_blank', 'width=600,height=400');
        }

        function shareOnTwitter() {
            const text = `Xem chiến dịch ${escape('{{ $campaign->name }}')} với tỷ lệ thắng {{ number_format($winRate, 1) }}%`;
            window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(window.location.href)}`,
                '_blank', 'width=600,height=400');
        }

        function copyToClipboard() {
            navigator.clipboard.writeText(window.location.href).then(() => {
                alert('Đã sao chép link chia sẻ!');
            });
        }
    </script>
</body>
</html>
