
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Danh sách chiến dịch</h5>
                    <a href="{{ route('campaigns.create') }}" class="btn btn-primary">Tạo chiến dịch mới</a>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Ngày bắt đầu</th>
                                    <th>Số ngày</th>
                                    <th>Vốn ban đầu</th>
                                    <th>Vốn hiện tại</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($campaigns as $campaign)
                                <tr>
                                    <td>{{ $campaign->start_date->format('d/m/Y') }}</td>
                                    <td>{{ $campaign->days }}</td>
                                    <td>{{ number_format($campaign->initial_balance) }}</td>
                                    <td>{{ number_format($campaign->current_balance) }}</td>
                                    <td>{{ $campaign->status }}</td>
                                    <td>
                                        <a href="{{ route('campaigns.show', $campaign) }}" class="btn btn-sm btn-info">Chi tiết</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{ $campaigns->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
