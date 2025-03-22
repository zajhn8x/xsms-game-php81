
@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4">Timeline Cầu Lô #{{ $cauLo->id }}</h2>

    <!-- Meta Information Card -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Thông tin Cầu Lô</h5>
        </div>
        <div class="card-body">
            <p><strong>Tên công thức:</strong> {{ $meta['formula_name'] }}</p>
            <p><strong>Cấu trúc:</strong> <pre class="bg-light p-2">{{ json_encode($meta['formula_structure'] ?? [], JSON_PRETTY_PRINT) }}</pre></p>
            <p><strong>Tỷ lệ trúng:</strong> {{ number_format($meta['hit_rate'], 2) }}%</p>
            <p><strong>Tổng số lần trúng:</strong> {{ $meta['total_hits'] }}</p>
        </div>
    </div>

    <!-- Timeline Card -->
    <div class="card">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">Lịch sử 30 ngày gần nhất</h5>
        </div>
        <div class="card-body">
            <div class="list-group">
                @foreach($dateRange as $date)
                    @php
                        $hit = $hits[$date] ?? null;
                        $result = $results[$date] ?? null;
                    @endphp
                    <div class="list-group-item {{ $hit ? 'list-group-item-success' : '' }}">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">📅 {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</h5>
                                @if($result)
                                    <small class="text-muted">Kết quả: {{ $result->result_string ?? 'N/A' }}</small>
                                @endif
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <!-- Cặp cầu lô hôm sau -->
                                @if(isset($cauLoIndex[$date]))
                                    <span class="badge bg-info">
                                        Cặp cầu lô hôm sau: {{ $cauLoIndex[$date]->pluck('value')->implode(', ') }}
                                    </span>
                                @endif

                                <!-- Trạng thái trúng -->
                                <span class="badge {{ $hit ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $hit ? '🎯 Số trúng: ' . $hit->so_trung : '❌ Không trúng' }}
                                </span>

                                <!-- Modal Button -->
                                @if($result)
                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-{{ $date }}">
                                        Chi tiết
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Modal -->
                    @if($result)
                        <div class="modal fade" id="modal-{{ $date }}" tabindex="-1" aria-labelledby="modalLabel-{{ $date }}" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="modalLabel-{{ $date }}">
                                            Chi tiết kết quả ngày {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        @if($result->prizes)
                                            <x-lottery-results :prizes="$result->prizes" />
                                        @else
                                            <p class="text-center">Không có dữ liệu chi tiết</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
