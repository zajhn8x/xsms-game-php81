<?php
return [
    'positions' => [
        'special' => ['GDB-1-1', 'GDB-1-2', 'GDB-1-3', 'GDB-1-4', 'GDB-1-5'],
        'prize1' => ['G1-1-1', 'G1-1-2', 'G1-1-3', 'G1-1-4', 'G1-1-5'],
        'prize2' => ['G2-1-1', 'G2-1-2', 'G2-1-3', 'G2-1-4', 'G2-1-5',
            'G2-2-1', 'G2-2-2', 'G2-2-3', 'G2-2-4', 'G2-2-5'],
        'prize3' => ['G3-1-1', 'G3-1-2', 'G3-1-3', 'G3-1-4', 'G3-1-5',
            'G3-2-1', 'G3-2-2', 'G3-2-3', 'G3-2-4', 'G3-2-5',
            'G3-3-1', 'G3-3-2', 'G3-3-3', 'G3-3-4', 'G3-3-5',
            'G3-4-1', 'G3-4-2', 'G3-4-3', 'G3-4-4', 'G3-4-5',
            'G3-5-1', 'G3-5-2', 'G3-5-3', 'G3-5-4', 'G3-5-5',
            'G3-6-1', 'G3-6-2', 'G3-6-3', 'G3-6-4', 'G3-6-5'],
        'prize4' => ['G4-1-1', 'G4-1-2', 'G4-1-3', 'G4-1-4',
            'G4-2-1', 'G4-2-2', 'G4-2-3', 'G4-2-4',
            'G4-3-1', 'G4-3-2', 'G4-3-3', 'G4-3-4',
            'G4-4-1', 'G4-4-2', 'G4-4-3', 'G4-4-4'], // Mỗi số riêng biệt
        'prize5' => ['G5-1-1', 'G5-1-2', 'G5-1-3', 'G5-1-4',
            'G5-2-1', 'G5-2-2', 'G5-2-3', 'G5-2-4',
            'G5-3-1', 'G5-3-2', 'G5-3-3', 'G5-3-4',
            'G5-4-1', 'G5-4-2', 'G5-4-3', 'G5-4-4',
            'G5-5-1', 'G5-5-2', 'G5-5-3', 'G5-5-4',
            'G5-6-1', 'G5-6-2', 'G5-6-3', 'G5-6-4'], // Chỉnh theo từng chữ số
        'prize6' => ['G6-1-1', 'G6-1-2', 'G6-1-3',
            'G6-2-1', 'G6-2-2', 'G6-2-3',
            'G6-3-1', 'G6-3-2', 'G6-3-3'],
        'prize7' => ['G7-1-1', 'G7-1-2',
            'G7-2-1', 'G7-2-2',
            'G7-3-1', 'G7-3-2',
            'G7-4-1', 'G7-4-2'] // Chia từng chữ số cho prize7
    ],
    // Cấu hình các dạng ghép cầu
    'combination_types' => [
        'single' => [
            'description' => 'Cầu lô từ một vị trí duy nhất',
            'example' => 'Lấy số tại vị trí G7-3-2'
        ],
        'pair' => [
            'description' => 'Ghép từ hai vị trí',
            'example' => 'G7-3-2 & G7-4-2 = XY [0-99]'
        ],
        'multi' => [
            'description' => 'Ghép từ nhiều vị trí',
            'example' => 'G7-3-2 & G7-4-2 & G6-1-3 = XYZ'
        ],
        'dynamic' => [
            'description' => 'Máy học tự động xác định vị trí tối ưu',
            'example' => 'Tự động xác định vị trí dựa trên phân tích thống kê'
        ]
    ],

    // Các công thức ghép cầu mẫu
    'predefined_formulas' => [
        [
            'name' => 'Cầu Lô Song Thủ G7',
            'type' => 'pair',
            'positions' => ['G7-3-2', 'G7-4-2'],
            'description' => 'Ghép song thủ từ Giải 7'
        ],
        [
            'name' => 'Cầu Lô Tam Thủ G6-G7',
            'type' => 'multi',
            'positions' => ['G6-3-3', 'G7-3-2', 'G7-4-2'],
            'description' => 'Ghép tam thủ từ Giải 6 và Giải 7'
        ],
        [
            'name' => 'Cầu Lô Đầu DB',
            'type' => 'single',
            'positions' => ['GDB-1-1'],
            'description' => 'Lấy số đầu của giải đặc biệt'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Alerts Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for campaign performance monitoring and alert thresholds
    |
    */
    'performance_alerts' => [
        'enabled' => env('PERFORMANCE_ALERTS_ENABLED', true),

        'win_rate_threshold' => [
            'critical' => env('ALERT_WIN_RATE_CRITICAL', 15),  // 15%
            'warning' => env('ALERT_WIN_RATE_WARNING', 30)     // 30%
        ],

        'roi_threshold' => [
            'critical' => env('ALERT_ROI_CRITICAL', -50),  // -50%
            'warning' => env('ALERT_ROI_WARNING', -20)     // -20%
        ],

        'balance_threshold' => [
            'critical' => env('ALERT_BALANCE_CRITICAL', 10),  // 10% of initial
            'warning' => env('ALERT_BALANCE_WARNING', 25)     // 25% of initial
        ],

        'consecutive_losses_threshold' => [
            'critical' => env('ALERT_CONSECUTIVE_LOSSES_CRITICAL', 15),  // 15 losses
            'warning' => env('ALERT_CONSECUTIVE_LOSSES_WARNING', 10)     // 10 losses
        ],

        'balance_depletion_rate' => [
            'critical' => env('ALERT_BALANCE_DEPLETION_CRITICAL', 100000), // 100k VND per day
            'warning' => env('ALERT_BALANCE_DEPLETION_WARNING', 50000)     // 50k VND per day
        ],

        'betting_frequency_threshold' => [
            'critical' => env('ALERT_BETTING_FREQUENCY_CRITICAL', 20),  // 20 bets per hour
            'warning' => env('ALERT_BETTING_FREQUENCY_WARNING', 15)     // 15 bets per hour
        ],

        'large_bet_threshold' => [
            'critical' => env('ALERT_LARGE_BET_CRITICAL', 500000), // 500k VND
            'warning' => env('ALERT_LARGE_BET_WARNING', 200000)    // 200k VND
        ],

        'notification_settings' => [
            'critical_immediate' => env('ALERT_CRITICAL_IMMEDIATE', true),
            'warning_throttle_minutes' => env('ALERT_WARNING_THROTTLE', 30),
            'alert_history_days' => env('ALERT_HISTORY_DAYS', 7),
            'max_history_entries' => env('ALERT_MAX_HISTORY', 100)
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Resource Monitoring Configuration
    |--------------------------------------------------------------------------
    | Micro-task 2.3.1.4: Resource usage monitoring (3h)
    |
    | Configuration for monitoring system and campaign resource usage
    |
    */
    'resource_monitoring' => [
        'enabled' => env('RESOURCE_MONITORING_ENABLED', true),
        'monitoring_interval' => env('RESOURCE_MONITORING_INTERVAL', 300), // 5 minutes
        'cache_duration' => env('RESOURCE_MONITORING_CACHE_DURATION', 300),

        // Alert thresholds
        'alert_thresholds' => [
            'memory_usage_percent' => env('RESOURCE_ALERT_MEMORY_PERCENT', 85),
            'disk_usage_percent' => env('RESOURCE_ALERT_DISK_PERCENT', 90),
            'database_connections_percent' => env('RESOURCE_ALERT_DB_CONNECTIONS_PERCENT', 80),
            'queue_size' => env('RESOURCE_ALERT_QUEUE_SIZE', 1000),
            'cpu_load' => env('RESOURCE_ALERT_CPU_LOAD', 3.0),
            'cache_keys' => env('RESOURCE_ALERT_CACHE_KEYS', 50000),
            'failed_jobs' => env('RESOURCE_ALERT_FAILED_JOBS', 100),
            'slow_queries' => env('RESOURCE_ALERT_SLOW_QUERIES', 100)
        ],

        // Heavy usage thresholds
        'heavy_thresholds' => [
            'user_campaigns' => env('RESOURCE_HEAVY_USER_CAMPAIGNS', 5),
            'campaign_bets_per_day' => env('RESOURCE_HEAVY_CAMPAIGN_BETS', 100),
            'network_requests_per_hour' => env('RESOURCE_HEAVY_NETWORK_REQUESTS', 10000)
        ],

        // Health scoring weights
        'health_weights' => [
            'database' => 0.25,
            'memory' => 0.20,
            'storage' => 0.15,
            'queue' => 0.20,
            'cpu' => 0.20
        ],

        // Optimization settings
        'optimization' => [
            'auto_cleanup_logs' => env('RESOURCE_AUTO_CLEANUP_LOGS', true),
            'auto_cleanup_cache' => env('RESOURCE_AUTO_CLEANUP_CACHE', true),
            'auto_optimize_database' => env('RESOURCE_AUTO_OPTIMIZE_DATABASE', false),
            'cleanup_interval_days' => env('RESOURCE_CLEANUP_INTERVAL_DAYS', 7),
            'max_log_size_mb' => env('RESOURCE_MAX_LOG_SIZE_MB', 100)
        ]
    ]
];

