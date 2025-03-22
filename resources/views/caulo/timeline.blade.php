@extends('layouts.app')

@section('content')
    <div class="container">
        <h2 class="mb-4">Timeline Cầu Lô #{{ $cauLo->id }}</h2>

        <!-- Hiển thị thông tin meta của cầu lô -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Thông tin Cầu Lô</h5>
            </div>
            <div class="card-body">
                <p><strong>ID:</strong> {{ $cauLo->id }}</p>
                <p><strong>Name:</strong> {{ $meta['formula_name'] }}</p>
                <p><strong>Ghi chú:</strong> {{ $meta['formula_note'] }}</p>
                <p><strong>Tỷ lệ trúng:</strong> {{ $meta['hit_rate'] }}</p>
                <p><strong>Tổng số lần trúng:</strong> {{ $meta['total_hits'] }}</p>
                <p><strong>Ngày tạo:</strong> {{ $meta['created_at'] }}</p>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <ul class="list-group">
                    @foreach($dateRange as $date)
                        @php
                            $hit = $hitsByDate->get($date);
                            $result = $results[$date] ?? [];
                            $loArray = Arr::get($result,'lo_array',[]);
                            $prizes = Arr::get($result,'prizes',[]);
                            $loHtml = implode(', ', array_map(fn($lo) => $hit && $lo == $hit['so_trung']
                                ? "<strong class='text-danger'>$lo</strong>"
                                : $lo, $loArray));
                            // Lấy cặp cầu lô từ LotteryResultIndex
                            $cauLoData = $cauLoIndex->get($date, collect())->map(fn($index) => $index->value)->implode(', ');
                        @endphp
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">📅 {{ $date }}</h5>
                                <small>Dãy lô: {!! $loHtml !!}</small>
                            </div>
                            <!-- Nút "Xem Chi Tiết" -->
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-{{ $date }}">
                                Xem Chi Tiết
                            </button>
                            <span class="alert alert-info">Cặp cầu lô hôm sau: {{ $cauLoData }}</span>
                            <span class="badge {{ $hit ? 'bg-success' : 'bg-secondary' }}">
                                {{ $hit ? '🎯 Số trúng: ' . $hit['so_trung'] : '❌ Không trúng' }}
                            </span>
                        </li>

                        <!-- Modal Bootstrap -->
                        <div class="modal fade" id="modal-{{ $date }}" tabindex="-1" aria-labelledby="modalLabel-{{ $date }}" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="modalLabel-{{ $date }}">Chi Tiết Kết Quả Ngày {{ $date }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <x-lottery-results :prizes="$prizes" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- End Modal -->
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endsection
