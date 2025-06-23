@props([
    'type' => 'default',
    'title' => '',
    'subtitle' => '',
    'icon' => null,
    'headerClass' => '',
    'bodyClass' => '',
    'footerClass' => '',
    'hover' => false
])

@php
    $cardClasses = match($type) {
        'primary' => 'rounded-lg border-2 border-primary-500 bg-primary-50 shadow-card',
        'success' => 'rounded-lg border-2 border-success-500 bg-success-50 shadow-card',
        'warning' => 'rounded-lg border-2 border-warning-500 bg-warning-50 shadow-card',
        'error' => 'rounded-lg border-2 border-error-500 bg-error-50 shadow-card',
        'info' => 'rounded-lg border-2 border-info-500 bg-info-50 shadow-card',
        default => 'rounded-lg border border-gray-200 bg-white shadow-card'
    };

    $titleClasses = match($type) {
        'primary' => 'text-primary-900 font-semibold',
        'success' => 'text-success-900 font-semibold',
        'warning' => 'text-warning-900 font-semibold',
        'error' => 'text-error-900 font-semibold',
        'info' => 'text-info-900 font-semibold',
        default => 'text-gray-900 font-semibold'
    };

    $iconClasses = match($type) {
        'primary' => 'text-primary-600',
        'success' => 'text-success-600',
        'warning' => 'text-warning-600',
        'error' => 'text-error-600',
        'info' => 'text-info-600',
        default => 'text-gray-600'
    };

    if ($hover) {
        $cardClasses .= ' hover:shadow-card-hover transition-shadow duration-200 cursor-pointer';
    }
@endphp

<div {{ $attributes->merge(['class' => $cardClasses]) }}>
    @if($title || $icon || isset($header))
        <div class="px-6 py-4 border-b border-current border-opacity-20 {{ $headerClass }}">
            <div class="flex items-center {{ $title ? 'space-x-3' : '' }}">
                @if($icon)
                    <div class="{{ $iconClasses }}">
                        {!! $icon !!}
                    </div>
                @endif

                @if($title)
                    <div class="flex-1">
                        <h3 class="text-lg {{ $titleClasses }}">{{ $title }}</h3>
                        @if($subtitle)
                            <p class="text-sm opacity-75 mt-1">{{ $subtitle }}</p>
                        @endif
                    </div>
                @endif

                @isset($header)
                    {{ $header }}
                @endisset
            </div>
        </div>
    @endif

    <div class="p-6 {{ $bodyClass }}">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="px-6 py-4 border-t border-current border-opacity-20 {{ $footerClass }}">
            {{ $footer }}
        </div>
    @endisset
</div>
