@extends('layouts.app')

@section('content')
    <div class="container">
        <h2 class="mb-4">Timeline Cầu Lô #{{ $cauLo->id }}</h2>

        <!-- Meta Information Card -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Thông tin Cầu Lô</h5>
                <div class="btn-group">
                    @foreach([2,3,4,5] as $s)
                        <a href="{{ route('caulo.timeline', ['id' => $cauLo->id, 'streak' => $s]) }}"
                           class="btn btn-sm {{ $currentStreak == $s ? 'btn-light' : 'btn-outline-light' }}">
                            Streak {{ $s }}
                        </a>
                    @endforeach
                </div>
            </div>
            <div class="card-body">
                <p><strong>Tên công thức:</strong> {{ $meta->formula_name }}</p>
                <p><strong>Cấu trúc:</strong>
                <pre class="bg-light p-2">{{ json_encode($metaPosition) }}</pre>
                </p>
            </div>
        </div>

        <!-- Include Streak Chart -->
    @include('caulo._streak_chart')

    <!-- Timeline Data -->
        @livewire('cau-lo-timeline', ['cauLoId' => $cauLo->id])
    </div>

    <style>
        .streak-chart {
            display: flex;
            align-items: flex-end;
            gap: 2px;
            height: 120px;
            padding: 10px;
        }

        .streak-bar {
            flex: 1;
            min-width: 10px;
            transition: height 0.3s ease;
        }
    </style>
@endsection
