@props([
    'percentage' => 0,
    'color' => 'primary',
    'size' => 'md',
    'showText' => true,
    'label' => ''
])

@php
    $heightClasses = match($size) {
        'sm' => 'h-2',
        'md' => 'h-2.5',
        'lg' => 'h-3',
        default => 'h-2.5'
    };

    $bgClasses = match($color) {
        'primary' => 'bg-primary-200',
        'success' => 'bg-success-200',
        'warning' => 'bg-warning-200',
        'error' => 'bg-error-200',
        'info' => 'bg-info-200',
        default => 'bg-primary-200'
    };

    $progressClasses = match($color) {
        'primary' => 'bg-primary-600',
        'success' => 'bg-success-600',
        'warning' => 'bg-warning-600',
        'error' => 'bg-error-600',
        'info' => 'bg-info-600',
        default => 'bg-primary-600'
    };

    $textColor = match($color) {
        'primary' => 'text-primary-700',
        'success' => 'text-success-700',
        'warning' => 'text-warning-700',
        'error' => 'text-error-700',
        'info' => 'text-info-700',
        default => 'text-primary-700'
    };

    $percentage = max(0, min(100, $percentage));
@endphp

<div {{ $attributes }}>
    @if($label || $showText)
        <div class="flex justify-between items-center mb-2">
            @if($label)
                <span class="text-sm font-medium {{ $textColor }}">{{ $label }}</span>
            @endif
            @if($showText)
                <span class="text-sm {{ $textColor }}">{{ $percentage }}%</span>
            @endif
        </div>
    @endif

    <div class="w-full {{ $bgClasses }} rounded-full {{ $heightClasses }} overflow-hidden">
        <div
            class="{{ $progressClasses }} {{ $heightClasses }} rounded-full transition-all duration-300 ease-out"
            style="width: {{ $percentage }}%"
        ></div>
    </div>
</div>
