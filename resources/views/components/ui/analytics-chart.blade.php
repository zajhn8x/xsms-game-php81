@props([
    'title' => '',
    'subtitle' => '',
    'chartId' => 'chart-' . uniqid(),
    'chartType' => 'line',
    'height' => '300px',
    'data' => [],
    'color' => 'primary'
])

@php
$colorMap = [
    'primary' => [
        'border' => '#0891b2',
        'background' => 'rgba(8, 145, 178, 0.1)',
        'gradient' => 'from-primary-50 to-primary-100'
    ],
    'success' => [
        'border' => '#059669',
        'background' => 'rgba(5, 150, 105, 0.1)',
        'gradient' => 'from-success-50 to-success-100'
    ],
    'warning' => [
        'border' => '#d97706',
        'background' => 'rgba(217, 119, 6, 0.1)',
        'gradient' => 'from-warning-50 to-warning-100'
    ],
    'error' => [
        'border' => '#dc2626',
        'background' => 'rgba(220, 38, 38, 0.1)',
        'gradient' => 'from-error-50 to-error-100'
    ],
    'info' => [
        'border' => '#2563eb',
        'background' => 'rgba(37, 99, 235, 0.1)',
        'gradient' => 'from-info-50 to-info-100'
    ]
];

$colors = $colorMap[$color] ?? $colorMap['primary'];
@endphp

<x-ui.card
    :title="$title"
    :subtitle="$subtitle"
    class="bg-gradient-to-br {{ $colors['gradient'] }} border-{{ $color }}-200"
>
    <x-slot:icon>
        <svg class="w-6 h-6 text-{{ $color }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
        </svg>
    </x-slot:icon>

    <div class="mt-4" style="height: {{ $height }};">
        <canvas id="{{ $chartId }}" class="w-full h-full"></canvas>
    </div>

    {{ $slot }}
</x-ui.card>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('{{ $chartId }}');
    if (ctx) {
        const chartData = @json($data);

        new Chart(ctx.getContext('2d'), {
            type: '{{ $chartType }}',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#374151',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        borderColor: '#6b7280',
                        borderWidth: 1,
                        cornerRadius: 8,
                        displayColors: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: '#f3f4f6',
                            borderColor: '#e5e7eb'
                        },
                        ticks: {
                            color: '#6b7280',
                            font: {
                                size: 12
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f3f4f6',
                            borderColor: '#e5e7eb'
                        },
                        ticks: {
                            color: '#6b7280',
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
