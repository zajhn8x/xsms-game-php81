@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <!-- Main Feed -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-rss"></i> Social Feed</h5>
                </div>
                <div class="card-body">
                    <div id="social-feed">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Following Campaigns -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5><i class="fas fa-heart"></i> Campaigns từ người theo dõi</h5>
                </div>
                <div class="card-body">
                    <div class="row" id="following-campaigns">
                        @if(count($following_campaigns['campaigns']) > 0)
                            @foreach($following_campaigns['campaigns'] as $campaign)
                            <div class="col-md-6 mb-3">
                                <div class="card border-primary">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-2">
                                            <img src="{{ $campaign->user->avatar ?? '/default-avatar.png' }}"
                                                 class="rounded-circle me-2" width="32" height="32">
                                            <div>
                                                <h6 class="mb-0">{{ $campaign->name }}</h6>
                                                <small class="text-muted">by {{ $campaign->user->name }}</small>
                                            </div>
                                        </div>
                                        <div class="row text-center">
                                            <div class="col-4">
                                                <div class="text-success">
                                                    <i class="fas fa-chart-line"></i><br>
                                                    <small>Lợi nhuận</small><br>
                                                    <strong>{{ number_format(($campaign->current_balance - $campaign->initial_balance) / $campaign->initial_balance * 100, 1) }}%</strong>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="text-info">
                                                    <i class="fas fa-percentage"></i><br>
                                                    <small>Tỷ lệ thắng</small><br>
                                                    <strong>{{ number_format($campaign->win_rate, 1) }}%</strong>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="text-warning">
                                                    <i class="fas fa-calendar"></i><br>
                                                    <small>Ngày</small><br>
                                                    <strong>{{ $campaign->days }}</strong>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <a href="{{ route('campaigns.show', $campaign) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i> Xem chi tiết
                                            </a>
                                            <button class="btn btn-sm btn-outline-success share-campaign"
                                                    data-campaign-id="{{ $campaign->id }}">
                                                <i class="fas fa-share"></i> Share
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="text-center text-muted">
                                <i class="fas fa-heart-broken fa-3x mb-3"></i>
                                <p>Chưa theo dõi ai. Hãy tìm và theo dõi các trader giỏi!</p>
                                <a href="{{ route('social.leaderboard') }}" class="btn btn-primary">
                                    <i class="fas fa-trophy"></i> Xem bảng xếp hạng
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- User Stats -->
            <div class="card">
                <div class="card-header">
                    <h6><i class="fas fa-user"></i> Thống kê của bạn</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="text-primary">
                                <i class="fas fa-user-friends"></i><br>
                                <strong>{{ $user_stats['followers_count'] ?? 0 }}</strong><br>
                                <small>Followers</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-success">
                                <i class="fas fa-heart"></i><br>
                                <strong>{{ $user_stats['following_count'] ?? 0 }}</strong><br>
                                <small>Following</small>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row text-center">
                        <div class="col-12">
                            <div class="text-warning">
                                <i class="fas fa-chart-line"></i><br>
                                <strong>{{ number_format($user_stats['profit_rate'] ?? 0, 1) }}%</strong><br>
                                <small>Tỷ lệ lợi nhuận</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Performers -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6><i class="fas fa-trophy"></i> Top Performers</h6>
                </div>
                <div class="card-body">
                    @foreach(array_slice($top_performers, 0, 5) as $index => $performer)
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge bg-warning me-2">#{{ $index + 1 }}</span>
                        <img src="{{ $performer['avatar'] ?? '/default-avatar.png' }}"
                             class="rounded-circle me-2" width="24" height="24">
                        <div class="flex-grow-1">
                            <div class="fw-bold">{{ $performer['name'] }}</div>
                            <small class="text-success">+{{ number_format($performer['profit_rate'], 1) }}%</small>
                        </div>
                        <button class="btn btn-sm btn-outline-primary follow-user"
                                data-user-id="{{ $performer['id'] }}">
                            <i class="fas fa-user-plus"></i>
                        </button>
                    </div>
                    @endforeach
                    <div class="text-center mt-2">
                        <a href="{{ route('social.leaderboard') }}" class="btn btn-sm btn-outline-primary">
                            Xem tất cả
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Search -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6><i class="fas fa-search"></i> Tìm kiếm User</h6>
                </div>
                <div class="card-body">
                    <div class="input-group">
                        <input type="text" class="form-control" id="user-search" placeholder="Tìm tên hoặc email...">
                        <button class="btn btn-outline-secondary" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <div id="search-results" class="mt-2"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Share Modal -->
<div class="modal fade" id="shareModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chia sẻ Campaign</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-primary share-platform" data-platform="facebook">
                        <i class="fab fa-facebook"></i> Chia sẻ lên Facebook
                    </button>
                    <button class="btn btn-info share-platform" data-platform="twitter">
                        <i class="fab fa-twitter"></i> Chia sẻ lên Twitter
                    </button>
                    <button class="btn btn-success share-platform" data-platform="telegram">
                        <i class="fab fa-telegram"></i> Chia sẻ lên Telegram
                    </button>
                    <button class="btn btn-secondary share-platform" data-platform="copy_link">
                        <i class="fas fa-link"></i> Copy Link
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    let currentCampaignId = null;

    // Load social feed
    function loadSocialFeed() {
        $.get('/social/api/feed', function(data) {
            let html = '';
            if (data.activities && data.activities.length > 0) {
                data.activities.forEach(function(activity) {
                    html += renderActivity(activity);
                });
            } else {
                html = '<div class="text-center text-muted"><i class="fas fa-inbox fa-3x mb-3"></i><p>Chưa có hoạt động nào</p></div>';
            }
            $('#social-feed').html(html);
        });
    }

    function renderActivity(activity) {
        const time = new Date(activity.timestamp).toLocaleString('vi-VN');

        if (activity.type === 'campaign_created') {
            return `
                <div class="border-bottom pb-3 mb-3">
                    <div class="d-flex align-items-center mb-2">
                        <img src="${activity.user.avatar || '/default-avatar.png'}" class="rounded-circle me-2" width="32" height="32">
                        <div>
                            <strong>${activity.user.name}</strong> đã tạo campaign mới
                            <br><small class="text-muted">${time}</small>
                        </div>
                    </div>
                    <div class="ms-4">
                        <h6>${activity.data.name}</h6>
                        <p class="text-muted mb-0">${activity.data.description || 'Không có mô tả'}</p>
                    </div>
                </div>
            `;
        } else if (activity.type === 'big_win') {
            return `
                <div class="border-bottom pb-3 mb-3">
                    <div class="d-flex align-items-center mb-2">
                        <img src="${activity.user.avatar || '/default-avatar.png'}" class="rounded-circle me-2" width="32" height="32">
                        <div>
                            <strong>${activity.user.name}</strong> vừa thắng lớn!
                            <br><small class="text-muted">${time}</small>
                        </div>
                    </div>
                    <div class="ms-4 bg-success bg-opacity-10 p-2 rounded">
                        <strong class="text-success">🎉 ${new Intl.NumberFormat('vi-VN').format(activity.data.win_amount)} VND</strong>
                        <br><small>Số ${activity.data.lo_number} - Campaign: ${activity.data.campaign_name}</small>
                    </div>
                </div>
            `;
        }
        return '';
    }

    // Follow user
    $(document).on('click', '.follow-user', function() {
        const userId = $(this).data('user-id');
        const button = $(this);

        $.post(`/social/follow/${userId}`, {
            _token: $('meta[name="csrf-token"]').attr('content')
        })
        .done(function(response) {
            if (response.success) {
                button.removeClass('btn-outline-primary').addClass('btn-success');
                button.html('<i class="fas fa-check"></i>');
                toastr.success(response.message);
            } else {
                toastr.error(response.message);
            }
        });
    });

    // Share campaign
    $(document).on('click', '.share-campaign', function() {
        currentCampaignId = $(this).data('campaign-id');
        $('#shareModal').modal('show');
    });

    $(document).on('click', '.share-platform', function() {
        const platform = $(this).data('platform');

        $.post(`/campaigns/${currentCampaignId}/share`, {
            platform: platform,
            _token: $('meta[name="csrf-token"]').attr('content')
        })
        .done(function(response) {
            if (response.success) {
                if (platform === 'copy_link') {
                    navigator.clipboard.writeText(response.share_url);
                    toastr.success('Link đã được copy!');
                } else {
                    window.open(response.share_url, '_blank');
                    toastr.success('Đã tạo link share!');
                }
                $('#shareModal').modal('hide');
            } else {
                toastr.error(response.message);
            }
        });
    });

    // User search
    let searchTimeout;
    $('#user-search').on('input', function() {
        const query = $(this).val();

        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            if (query.length >= 2) {
                $.get('/api/social/search-users', { q: query })
                .done(function(response) {
                    let html = '';
                    response.users.forEach(function(user) {
                        html += `
                            <div class="d-flex align-items-center justify-content-between p-2 border-bottom">
                                <div class="d-flex align-items-center">
                                    <img src="${user.avatar || '/default-avatar.png'}" class="rounded-circle me-2" width="24" height="24">
                                    <div>
                                        <div class="fw-bold">${user.name}</div>
                                        <small class="text-muted">${user.email}</small>
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-outline-primary follow-user" data-user-id="${user.id}">
                                    <i class="fas fa-user-plus"></i>
                                </button>
                            </div>
                        `;
                    });
                    $('#search-results').html(html);
                });
            } else {
                $('#search-results').empty();
            }
        }, 300);
    });

    // Initial load
    loadSocialFeed();

    // Auto refresh every 30 seconds
    setInterval(loadSocialFeed, 30000);
});
</script>
@endpush
@endsection
