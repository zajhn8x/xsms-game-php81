<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\PaymentService;
use App\Services\WalletService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestPaymentGateway extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:test
                            {gateway : Payment gateway to test (vnpay|momo|all)}
                            {amount=100000 : Amount to test (default 100000 VND)}
                            {--user-id=1 : User ID to test with}
                            {--production : Test with production configuration}
                            {--webhook : Test webhook endpoints}
                            {--error-scenarios : Test error scenarios}
                            {--load-test=0 : Number of concurrent transactions for load testing}
                            {--verify-config : Only verify configuration without creating transactions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test payment gateway integration with comprehensive scenarios';

    protected $paymentService;
    protected $walletService;

    public function __construct(PaymentService $paymentService, WalletService $walletService)
    {
        parent::__construct();
        $this->paymentService = $paymentService;
        $this->walletService = $walletService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $gateway = $this->argument('gateway');
        $amount = (float) $this->argument('amount');
        $userId = $this->option('user-id');
        $production = $this->option('production');
        $webhook = $this->option('webhook');
        $errorScenarios = $this->option('error-scenarios');
        $loadTest = (int) $this->option('load-test');
        $verifyConfig = $this->option('verify-config');

        $this->info("🚀 Testing Payment Gateway Integration");
        $this->info("💳 Gateway: {$gateway}");
        $this->info("💰 Amount: " . number_format($amount) . " VND");
        $this->info("🔧 Mode: " . ($production ? 'PRODUCTION' : 'SANDBOX'));
        $this->line('');

        // Step 1: Verify Configuration
        if (!$this->verifyConfiguration($gateway, $production)) {
            $this->error('❌ Configuration verification failed');
            return 1;
        }

        if ($verifyConfig) {
            $this->info('✅ Configuration verification completed');
            return 0;
        }

        // Step 2: Test individual gateway or all
        if ($gateway === 'all') {
            $gateways = ['vnpay', 'momo'];
            foreach ($gateways as $gw) {
                $this->line('');
                $this->info("Testing {$gw}...");
                $this->testGateway($gw, $amount, $userId, $production);
            }
        } else {
            $this->testGateway($gateway, $amount, $userId, $production);
        }

        // Step 3: Test webhook endpoints
        if ($webhook) {
            $this->line('');
            $this->info('🔗 Testing webhook endpoints...');
            $this->testWebhookEndpoints($gateway);
        }

        // Step 4: Test error scenarios
        if ($errorScenarios) {
            $this->line('');
            $this->info('💥 Testing error scenarios...');
            $this->testErrorScenarios($gateway, $userId);
        }

        // Step 5: Load testing
        if ($loadTest > 0) {
            $this->line('');
            $this->info("⚡ Running load test with {$loadTest} concurrent transactions...");
            $this->runLoadTest($gateway, $amount, $userId, $loadTest);
        }

        $this->line('');
        $this->info('🎉 Payment gateway testing completed!');
        return 0;
    }

    private function verifyConfiguration($gateway, $production): bool
    {
        $this->info('1️⃣ Verifying configuration...');

        $gateways = $gateway === 'all' ? ['vnpay', 'momo'] : [$gateway];

        foreach ($gateways as $gw) {
            $this->line("  Checking {$gw}...");

            $config = config("payment.gateways.{$gw}");
            if (!$config) {
                $this->error("    ❌ Configuration not found for {$gw}");
                return false;
            }

            if (!$config['enabled']) {
                $this->warn("    ⚠️ {$gw} is disabled");
                continue;
            }

            $requiredKeys = $this->getRequiredConfigKeys($gw);
            foreach ($requiredKeys as $key) {
                if (empty($config[$key])) {
                    $this->error("    ❌ Missing required config: {$key}");
                    return false;
                }
            }

            // Check URL accessibility
            $url = $config['url'];
            try {
                $response = Http::timeout(10)->get($url);
                if ($response->status() === 200 || $response->status() === 405) {
                    $this->info("    ✅ {$gw} endpoint accessible");
                } else {
                    $this->warn("    ⚠️ {$gw} endpoint returned status: " . $response->status());
                }
            } catch (\Exception $e) {
                $this->warn("    ⚠️ {$gw} endpoint not accessible: " . $e->getMessage());
            }

            $this->info("    ✅ {$gw} configuration valid");
        }

        return true;
    }

    private function getRequiredConfigKeys($gateway): array
    {
        switch ($gateway) {
            case 'vnpay':
                return ['merchant_id', 'hash_secret', 'url', 'return_url', 'notify_url'];
            case 'momo':
                return ['partner_code', 'access_key', 'secret_key', 'url', 'return_url', 'notify_url'];
            default:
                return [];
        }
    }

    private function testGateway($gateway, $amount, $userId, $production): bool
    {
        if (!in_array($gateway, ['vnpay', 'momo'])) {
            $this->error('❌ Invalid gateway. Use: vnpay, momo, or all');
            return false;
        }

        // Find user
        $user = User::find($userId);
        if (!$user) {
            $this->error('❌ User not found');
            return false;
        }

        try {
            // Step 1: Validate payment
            $this->info('  Validating payment...');
            $validation = $this->paymentService->validatePayment($amount, $gateway);

            if (!$validation['valid']) {
                $this->error('  ❌ Validation failed: ' . $validation['message']);
                return false;
            }
            $this->info('  ✅ Payment validation passed');

            // Step 2: Calculate fee
            $fee = $this->paymentService->calculateFee($amount, $gateway);
            $this->info("  💳 Transaction fee: " . number_format($fee) . " VND");

            // Step 3: Create transaction
            $this->info('  Creating transaction...');
            $transaction = $this->walletService->deposit(
                $userId,
                $amount,
                $gateway,
                [
                    'test' => true,
                    'command' => true,
                    'production' => $production
                ]
            );
            $this->info('  ✅ Transaction created: ' . $transaction->transaction_id);

            // Step 4: Generate payment URL/data
            $this->info('  Generating payment URL...');

            if ($gateway === 'vnpay') {
                $paymentUrl = $this->paymentService->createVNPayPayment($transaction);
                $this->info('  ✅ VNPay payment URL generated');
                $this->line('  🔗 Payment URL: ' . substr($paymentUrl, 0, 100) . '...');

                // Extract key parameters for testing
                $this->showVNPayTestData($transaction, $paymentUrl);

            } elseif ($gateway === 'momo') {
                $momoResult = $this->paymentService->createMoMoPayment($transaction);
                $this->info('  ✅ MoMo payment created');
                $this->line('  🔗 Payment URL: ' . ($momoResult['payUrl'] ?? 'N/A'));
                $this->line('  📱 Deep link: ' . ($momoResult['deeplink'] ?? 'N/A'));

                $this->showMoMoTestData($transaction, $momoResult);
            }

            return true;

        } catch (\Exception $e) {
            $this->error('  ❌ Test failed: ' . $e->getMessage());
            return false;
        }
    }

    private function showVNPayTestData($transaction, $paymentUrl)
    {
        parse_str(parse_url($paymentUrl, PHP_URL_QUERY), $params);

        $this->comment('  📝 VNPay Test Data:');
        $this->line('    vnp_TxnRef: ' . ($params['vnp_TxnRef'] ?? 'N/A'));
        $this->line('    vnp_Amount: ' . ($params['vnp_Amount'] ?? 'N/A'));
        $this->line('    vnp_CreateDate: ' . ($params['vnp_CreateDate'] ?? 'N/A'));
        $this->line('    vnp_ExpireDate: ' . ($params['vnp_ExpireDate'] ?? 'N/A'));

        // Generate test callback URL
        $callbackUrl = config('app.url') . '/webhook/vnpay/notify';
        $this->line('    Webhook URL: ' . $callbackUrl);
    }

    private function showMoMoTestData($transaction, $momoResult)
    {
        $this->comment('  📝 MoMo Test Data:');
        $this->line('    orderId: ' . ($momoResult['orderId'] ?? 'N/A'));
        $this->line('    requestId: ' . ($momoResult['requestId'] ?? 'N/A'));
        $this->line('    amount: ' . ($momoResult['amount'] ?? 'N/A'));
        $this->line('    resultCode: ' . ($momoResult['resultCode'] ?? 'N/A'));

        // Generate test callback URL
        $callbackUrl = config('app.url') . '/webhook/momo/notify';
        $this->line('    Webhook URL: ' . $callbackUrl);
    }

    private function testWebhookEndpoints($gateway)
    {
        $gateways = $gateway === 'all' ? ['vnpay', 'momo'] : [$gateway];

        foreach ($gateways as $gw) {
            $this->line("  Testing {$gw} webhook...");

            $webhookUrl = config('app.url') . "/webhook/{$gw}/notify";

            try {
                // Test webhook endpoint accessibility
                $response = Http::timeout(10)->post($webhookUrl, [
                    'test' => true
                ]);

                if ($response->status() < 500) {
                    $this->info("    ✅ {$gw} webhook endpoint accessible (Status: {$response->status()})");
                } else {
                    $this->warn("    ⚠️ {$gw} webhook returned error: " . $response->status());
                }

                // Test with sample callback data
                $this->testSampleCallback($gw, $webhookUrl);

            } catch (\Exception $e) {
                $this->error("    ❌ {$gw} webhook test failed: " . $e->getMessage());
            }
        }
    }

    private function testSampleCallback($gateway, $webhookUrl)
    {
        if ($gateway === 'vnpay') {
            $sampleData = [
                'vnp_TxnRef' => 'TEST' . time(),
                'vnp_ResponseCode' => '00',
                'vnp_TransactionNo' => 'VNP' . time(),
                'vnp_Amount' => 10000000, // 100k VND
                'vnp_OrderInfo' => 'Test payment',
                'vnp_SecureHash' => 'test_hash'
            ];
        } else {
            $sampleData = [
                'orderId' => 'TEST' . time(),
                'resultCode' => 0,
                'transId' => time(),
                'amount' => 100000,
                'message' => 'Success',
                'signature' => 'test_signature'
            ];
        }

        try {
            $response = Http::timeout(10)->post($webhookUrl, $sampleData);
            $this->line("    📞 Sample callback test: Status {$response->status()}");
        } catch (\Exception $e) {
            $this->line("    📞 Sample callback failed: " . $e->getMessage());
        }
    }

    private function testErrorScenarios($gateway, $userId)
    {
        $scenarios = [
            'invalid_amount' => 0,
            'negative_amount' => -1000,
            'excessive_amount' => 999999999999,
        ];

        foreach ($scenarios as $scenario => $amount) {
            $this->line("  Testing {$scenario}...");

            try {
                $validation = $this->paymentService->validatePayment($amount, $gateway);
                if (!$validation['valid']) {
                    $this->info("    ✅ {$scenario} correctly rejected: " . $validation['message']);
                } else {
                    $this->warn("    ⚠️ {$scenario} was accepted (unexpected)");
                }
            } catch (\Exception $e) {
                $this->info("    ✅ {$scenario} threw exception: " . $e->getMessage());
            }
        }
    }

    private function runLoadTest($gateway, $amount, $userId, $numberOfTransactions)
    {
        $this->line("  Creating {$numberOfTransactions} concurrent transactions...");

        $start = microtime(true);
        $successful = 0;
        $failed = 0;

        for ($i = 0; $i < $numberOfTransactions; $i++) {
            try {
                $transaction = $this->walletService->deposit(
                    $userId,
                    $amount,
                    $gateway,
                    ['test' => true, 'load_test' => true, 'batch' => $i]
                );

                if ($gateway === 'vnpay') {
                    $this->paymentService->createVNPayPayment($transaction);
                } else {
                    $this->paymentService->createMoMoPayment($transaction);
                }

                $successful++;
            } catch (\Exception $e) {
                $failed++;
                if ($failed <= 3) { // Only show first 3 errors
                    $this->error("    Transaction {$i} failed: " . $e->getMessage());
                }
            }
        }

        $end = microtime(true);
        $duration = round($end - $start, 2);
        $tps = round($numberOfTransactions / $duration, 2);

        $this->info("  ✅ Load test completed in {$duration}s");
        $this->info("  📊 Successful: {$successful}, Failed: {$failed}");
        $this->info("  ⚡ Throughput: {$tps} transactions/second");
    }
}
