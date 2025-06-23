@props([
    'type' => 'primary',
    'size' => 'md',
    'variant' => 'solid',
    'href' => null,
    'icon' => null,
    'iconPosition' => 'left',
    'loading' => false,
    'disabled' => false
])

@php
    $baseClasses = 'inline-flex items-center justify-center border font-semibold uppercase tracking-widest transition ease-in-out duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';

    // Size classes
    $sizeClasses = match($size) {
        'xs' => 'px-2.5 py-1.5 text-xs rounded',
        'sm' => 'px-3 py-2 text-xs rounded-md',
        'md' => 'px-4 py-2 text-sm rounded-md',
        'lg' => 'px-6 py-3 text-base rounded-md',
        'xl' => 'px-8 py-4 text-lg rounded-lg',
        default => 'px-4 py-2 text-sm rounded-md'
    };

    // Type and variant classes
    $colorClasses = match($type) {
        'primary' => match($variant) {
            'solid' => 'bg-primary-600 border-transparent text-white hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:ring-primary-500',
            'outline' => 'bg-transparent border-primary-600 text-primary-600 hover:bg-primary-50 focus:bg-primary-50 active:bg-primary-100 focus:ring-primary-500',
            'ghost' => 'bg-transparent border-transparent text-primary-600 hover:bg-primary-50 focus:bg-primary-50 active:bg-primary-100 focus:ring-primary-500',
            default => 'bg-primary-600 border-transparent text-white hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:ring-primary-500'
        },
        'success' => match($variant) {
            'solid' => 'bg-success-600 border-transparent text-white hover:bg-success-700 focus:bg-success-700 active:bg-success-900 focus:ring-success-500',
            'outline' => 'bg-transparent border-success-600 text-success-600 hover:bg-success-50 focus:bg-success-50 active:bg-success-100 focus:ring-success-500',
            'ghost' => 'bg-transparent border-transparent text-success-600 hover:bg-success-50 focus:bg-success-50 active:bg-success-100 focus:ring-success-500',
            default => 'bg-success-600 border-transparent text-white hover:bg-success-700 focus:bg-success-700 active:bg-success-900 focus:ring-success-500'
        },
        'warning' => match($variant) {
            'solid' => 'bg-warning-600 border-transparent text-white hover:bg-warning-700 focus:bg-warning-700 active:bg-warning-900 focus:ring-warning-500',
            'outline' => 'bg-transparent border-warning-600 text-warning-600 hover:bg-warning-50 focus:bg-warning-50 active:bg-warning-100 focus:ring-warning-500',
            'ghost' => 'bg-transparent border-transparent text-warning-600 hover:bg-warning-50 focus:bg-warning-50 active:bg-warning-100 focus:ring-warning-500',
            default => 'bg-warning-600 border-transparent text-white hover:bg-warning-700 focus:bg-warning-700 active:bg-warning-900 focus:ring-warning-500'
        },
        'error' => match($variant) {
            'solid' => 'bg-error-600 border-transparent text-white hover:bg-error-700 focus:bg-error-700 active:bg-error-900 focus:ring-error-500',
            'outline' => 'bg-transparent border-error-600 text-error-600 hover:bg-error-50 focus:bg-error-50 active:bg-error-100 focus:ring-error-500',
            'ghost' => 'bg-transparent border-transparent text-error-600 hover:bg-error-50 focus:bg-error-50 active:bg-error-100 focus:ring-error-500',
            default => 'bg-error-600 border-transparent text-white hover:bg-error-700 focus:bg-error-700 active:bg-error-900 focus:ring-error-500'
        },
        'gray' => match($variant) {
            'solid' => 'bg-gray-600 border-transparent text-white hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:ring-gray-500',
            'outline' => 'bg-transparent border-gray-300 text-gray-700 hover:bg-gray-50 focus:bg-gray-50 active:bg-gray-100 focus:ring-gray-500',
            'ghost' => 'bg-transparent border-transparent text-gray-700 hover:bg-gray-50 focus:bg-gray-50 active:bg-gray-100 focus:ring-gray-500',
            default => 'bg-gray-600 border-transparent text-white hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:ring-gray-500'
        },
        default => 'bg-primary-600 border-transparent text-white hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:ring-primary-500'
    };

    $classes = $baseClasses . ' ' . $sizeClasses . ' ' . $colorClasses;

    $iconSizeClass = match($size) {
        'xs' => 'h-3 w-3',
        'sm' => 'h-4 w-4',
        'md' => 'h-4 w-4',
        'lg' => 'h-5 w-5',
        'xl' => 'h-6 w-6',
        default => 'h-4 w-4'
    };

    $isDisabled = $disabled || $loading;
@endphp

@if($href && !$isDisabled)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($loading)
            <svg class="animate-spin {{ $iconSizeClass }} {{ $iconPosition === 'right' ? 'ml-2' : 'mr-2' }}" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        @elseif($icon && $iconPosition === 'left')
            <span class="{{ $iconSizeClass }} mr-2">
                {!! $icon !!}
            </span>
        @endif

        {{ $slot }}

        @if($icon && $iconPosition === 'right' && !$loading)
            <span class="{{ $iconSizeClass }} ml-2">
                {!! $icon !!}
            </span>
        @endif
    </a>
@else
    <button
        {{ $attributes->merge([
            'type' => 'button',
            'class' => $classes,
            'disabled' => $isDisabled
        ]) }}
    >
        @if($loading)
            <svg class="animate-spin {{ $iconSizeClass }} {{ $iconPosition === 'right' ? 'ml-2' : 'mr-2' }}" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="ml-2">{{ $loading === true ? 'Đang xử lý...' : $loading }}</span>
        @else
            @if($icon && $iconPosition === 'left')
                <span class="{{ $iconSizeClass }} mr-2">
                    {!! $icon !!}
                </span>
            @endif

            {{ $slot }}

            @if($icon && $iconPosition === 'right')
                <span class="{{ $iconSizeClass }} ml-2">
                    {!! $icon !!}
                </span>
            @endif
        @endif
    </button>
@endif
