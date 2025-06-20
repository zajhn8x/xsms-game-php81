<?php

return [
    'default_currency' => 'VND',
    'default_gateway' => 'vnpay',

    'gateways' => [
        'vnpay' => [
            'enabled' => env('VNPAY_ENABLED', true),
            'sandbox' => env('VNPAY_SANDBOX', true),
            'merchant_id' => env('VNPAY_MERCHANT_ID'),
            'hash_secret' => env('VNPAY_HASH_SECRET'),
            'secure_secret' => env('VNPAY_SECURE_SECRET'),
            'url' => env('VNPAY_SANDBOX', true)
                ? 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'
                : 'https://vnpayment.vn/paymentv2/vpcpay.html',
            'return_url' => env('APP_URL') . '/wallet/vnpay/return',
            'notify_url' => env('APP_URL') . '/webhook/vnpay/notify',
            'min_amount' => 10000, // 10k VND
            'max_amount' => 100000000, // 100M VND
            'supported_currencies' => ['VND'],
            'fee_percent' => 2.2, // 2.2% transaction fee
            'fee_min' => 2000, // Min 2k VND fee
        ],

        'momo' => [
            'enabled' => env('MOMO_ENABLED', true),
            'sandbox' => env('MOMO_SANDBOX', true),
            'partner_code' => env('MOMO_PARTNER_CODE'),
            'access_key' => env('MOMO_ACCESS_KEY'),
            'secret_key' => env('MOMO_SECRET_KEY'),
            'url' => env('MOMO_SANDBOX', true)
                ? 'https://test-payment.momo.vn/v2/gateway/api/create'
                : 'https://payment.momo.vn/v2/gateway/api/create',
            'return_url' => env('APP_URL') . '/wallet/momo/return',
            'notify_url' => env('APP_URL') . '/webhook/momo/notify',
            'min_amount' => 10000, // 10k VND
            'max_amount' => 50000000, // 50M VND
            'supported_currencies' => ['VND'],
            'fee_percent' => 2.0, // 2.0% transaction fee
            'fee_min' => 1500, // Min 1.5k VND fee
        ],

        'bank_transfer' => [
            'enabled' => env('BANK_TRANSFER_ENABLED', true),
            'banks' => [
                'VCB' => [
                    'name' => 'Vietcombank',
                    'account_number' => env('VCB_ACCOUNT_NUMBER'),
                    'account_name' => env('VCB_ACCOUNT_NAME'),
                    'swift_code' => 'BFTVVNVX',
                    'branch' => env('VCB_BRANCH', 'Chi nhánh Hoàn Kiếm'),
                ],
                'TCB' => [
                    'name' => 'Techcombank',
                    'account_number' => env('TCB_ACCOUNT_NUMBER'),
                    'account_name' => env('TCB_ACCOUNT_NAME'),
                    'swift_code' => 'VTCBVNVX',
                    'branch' => env('TCB_BRANCH', 'Chi nhánh Cầu Giấy'),
                ],
                'MB' => [
                    'name' => 'MBBank',
                    'account_number' => env('MB_ACCOUNT_NUMBER'),
                    'account_name' => env('MB_ACCOUNT_NAME'),
                    'swift_code' => 'MSCBVNVX',
                    'branch' => env('MB_BRANCH', 'Chi nhánh Thái Hà'),
                ]
            ],
            'min_amount' => 50000, // 50k VND minimum for bank transfer
            'max_amount' => 500000000, // 500M VND
            'processing_time' => '1-24 hours',
            'fee_percent' => 0, // No fee for bank transfer
        ]
    ],

    'currencies' => [
        'VND' => [
            'symbol' => '₫',
            'code' => 'VND',
            'decimal_places' => 0,
            'exchange_rate' => 1.0, // Base currency
        ],
        'USD' => [
            'symbol' => '$',
            'code' => 'USD',
            'decimal_places' => 2,
            'exchange_rate' => 0.000041, // Will be updated from API
        ]
    ],

    'transaction_limits' => [
        'daily_deposit_limit' => 200000000, // 200M VND per day
        'daily_withdrawal_limit' => 100000000, // 100M VND per day
        'monthly_deposit_limit' => 2000000000, // 2B VND per month
        'monthly_withdrawal_limit' => 1000000000, // 1B VND per month
    ],

    'webhook_security' => [
        'verify_ip' => env('PAYMENT_VERIFY_IP', true),
        'allowed_ips' => [
            // VNPay IPs
            '203.171.21.0/24',
            '203.171.22.0/24',
            // MoMo IPs
            '123.30.235.52',
            '123.30.235.53',
        ],
        'signature_timeout' => 300, // 5 minutes
    ],

    'auto_approval' => [
        'enabled' => env('PAYMENT_AUTO_APPROVAL', false),
        'max_amount' => 10000000, // Auto approve up to 10M VND
        'trusted_gateways' => ['vnpay', 'momo'],
    ]
];
