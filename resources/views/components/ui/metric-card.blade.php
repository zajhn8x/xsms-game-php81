@props([
    'title' => '',
    'value' => '',
    'subtitle' => '',
    'trend' => null, // 'up', 'down', 'neutral'
    'trendValue' => '',
    'color' => 'primary',
    'icon' => null,
    'href' => null
])

@php
$colorClasses = [
    'primary' => 'bg-gradient-to-br from-primary-50 to-primary-100 border-primary-200',
    'success' => 'bg-gradient-to-br from-success-50 to-success-100 border-success-200',
    'warning' => 'bg-gradient-to-br from-warning-50 to-warning-100 border-warning-200',
    'error' => 'bg-gradient-to-br from-error-50 to-error-100 border-error-200',
    'info' => 'bg-gradient-to-br from-info-50 to-info-100 border-info-200',
];

$textColors = [
    'primary' => 'text-primary-900',
    'success' => 'text-success-900',
    'warning' => 'text-warning-900',
    'error' => 'text-error-900',
    'info' => 'text-info-900',
];

$iconColors = [
    'primary' => 'text-primary-600',
    'success' => 'text-success-600',
    'warning' => 'text-warning-600',
    'error' => 'text-error-600',
    'info' => 'text-info-600',
];

$cardClass = $colorClasses[$color] ?? $colorClasses['primary'];
$textClass = $textColors[$color] ?? $textColors['primary'];
$iconClass = $iconColors[$color] ?? $iconColors['primary'];

$trendClasses = [
    'up' => 'text-success-600 bg-success-100',
    'down' => 'text-error-600 bg-error-100',
    'neutral' => 'text-gray-600 bg-gray-100'
];

$trendClass = $trendClasses[$trend] ?? '';
@endphp

<div class="relative">
    @if($href)
        <a href="{{ $href }}" class="block group">
    @endif

    <div class="bg-white rounded-xl border-2 {{ $cardClass }} p-6 transition-all duration-200
        {{ $href ? 'hover:shadow-lg hover:scale-105 cursor-pointer' : '' }}">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-4">
            <div class="flex-1">
                <h3 class="text-sm font-medium text-gray-600 truncate">{{ $title }}</h3>
                @if($subtitle)
                    <p class="text-xs text-gray-500 mt-1">{{ $subtitle }}</p>
                @endif
            </div>

            @if($icon)
                <div class="flex-shrink-0 ml-3">
                    <div class="w-10 h-10 rounded-lg bg-white/50 flex items-center justify-center">
                        {!! $icon !!}
                    </div>
                </div>
            @endif
        </div>

        {{-- Value --}}
        <div class="mb-4">
            <div class="text-3xl font-bold {{ $textClass }} leading-none">
                {{ $value }}
            </div>
        </div>

        {{-- Trend Indicator --}}
        @if($trend && $trendValue)
            <div class="flex items-center">
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $trendClass }}">
                    @if($trend === 'up')
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L10 4.414 4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    @elseif($trend === 'down')
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L10 15.586l5.293-5.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    @else
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                        </svg>
                    @endif
                    {{ $trendValue }}
                </span>
            </div>
        @endif

        {{-- Custom content slot --}}
        @if($slot->isNotEmpty())
            <div class="mt-4 pt-4 border-t border-gray-200">
                {{ $slot }}
            </div>
        @endif
    </div>

    @if($href)
        </a>
    @endif
</div>
