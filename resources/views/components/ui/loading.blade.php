@props([
    'size' => 'md',
    'text' => 'Đang xử lý dữ liệu...',
    'color' => 'info'
])

@php
    $sizeClasses = match($size) {
        'xs' => 'h-4 w-4',
        'sm' => 'h-5 w-5',
        'md' => 'h-6 w-6',
        'lg' => 'h-8 w-8',
        'xl' => 'h-12 w-12',
        default => 'h-6 w-6'
    };

    $colorClasses = match($color) {
        'primary' => 'text-primary-600',
        'success' => 'text-success-600',
        'warning' => 'text-warning-600',
        'error' => 'text-error-600',
        'info' => 'text-info-600',
        default => 'text-info-600'
    };
@endphp

{{-- Loading Component theo quy chuẩn UI --}}
<div {{ $attributes->merge(['class' => 'flex items-center space-x-2']) }}>
    <svg class="animate-spin {{ $sizeClasses }} {{ $colorClasses }}" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>
    @if($text)
        <span class="{{ $colorClasses }}">{{ $text }}</span>
    @endif
</div>
