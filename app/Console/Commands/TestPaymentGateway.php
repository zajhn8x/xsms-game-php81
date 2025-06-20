<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\PaymentService;
use App\Services\WalletService;
use Illuminate\Console\Command;

class TestPaymentGateway extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:test
                            {gateway : Payment gateway to test (vnpay|momo)}
                            {amount=100000 : Amount to test (default 100000 VND)}
                            {--user-id=1 : User ID to test with}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test payment gateway integration';

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

        $this->info("🚀 Testing {$gateway} payment gateway");
        $this->info("💰 Amount: " . number_format($amount) . " VND");
        $this->info("👤 User ID: {$userId}");
        $this->line('');

        // Validate gateway
        if (!in_array($gateway, ['vnpay', 'momo'])) {
            $this->error('❌ Invalid gateway. Use: vnpay or momo');
            return 1;
        }

        // Find user
        $user = User::find($userId);
        if (!$user) {
            $this->error('❌ User not found');
            return 1;
        }

        try {
            // Step 1: Validate payment
            $this->info('1️⃣ Validating payment...');
            $validation = $this->paymentService->validatePayment($amount, $gateway);

            if (!$validation['valid']) {
                $this->error('❌ Validation failed: ' . $validation['message']);
                return 1;
            }
            $this->info('✅ Payment validation passed');

            // Step 2: Calculate fee
            $fee = $this->paymentService->calculateFee($amount, $gateway);
            $this->info("💳 Transaction fee: " . number_format($fee) . " VND");

            // Step 3: Create transaction
            $this->info('2️⃣ Creating transaction...');
            $transaction = $this->walletService->deposit(
                $userId,
                $amount,
                $gateway,
                ['test' => true, 'command' => true]
            );
            $this->info('✅ Transaction created: ' . $transaction->transaction_id);

            // Step 4: Generate payment URL/data
            $this->info('3️⃣ Generating payment URL...');

            if ($gateway === 'vnpay') {
                $paymentUrl = $this->paymentService->createVNPayPayment($transaction);
                $this->info('✅ VNPay payment URL generated');
                $this->line('🔗 Payment URL: ' . $paymentUrl);

                // Show QR code hint
                $this->warn('📱 You can open this URL in browser or scan QR code to test');

            } elseif ($gateway === 'momo') {
                $momoResult = $this->paymentService->createMoMoPayment($transaction);
                $this->info('✅ MoMo payment created');
                $this->line('🔗 Payment URL: ' . $momoResult['payUrl']);
                $this->line('📱 Deep link: ' . ($momoResult['deeplink'] ?? 'N/A'));
            }

            // Step 5: Show test callback data
            $this->info('4️⃣ Test callback simulation...');
            $this->showTestCallbacks($transaction, $gateway);

            // Step 6: Show verification test
            $this->info('5️⃣ Testing signature verification...');
            $this->testSignatureVerification($gateway);

            $this->line('');
            $this->info('🎉 Payment gateway test completed successfully!');

            // Show next steps
            $this->warn('📋 Next steps:');
            $this->line('   - Test with real payment gateway credentials');
            $this->line('   - Test webhook endpoints with actual calls');
            $this->line('   - Verify transaction processing in database');

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Test failed: ' . $e->getMessage());
            $this->line('🔍 Stack trace:');
            $this->line($e->getTraceAsString());
            return 1;
        }
    }

    private function showTestCallbacks($transaction, $gateway)
    {
        $this->line('');
        $this->comment('📞 Test callback URLs:');

        if ($gateway === 'vnpay') {
            $this->line('   Return URL: ' . config('app.url') . '/wallet/vnpay/return');
            $this->line('   IPN URL: ' . config('app.url') . '/webhook/vnpay/notify');

            $this->comment('📝 VNPay test callback parameters:');
            $testParams = [
                'vnp_TxnRef' => $transaction->transaction_id,
                'vnp_ResponseCode' => '00',
                'vnp_TransactionNo' => 'TEST' . time(),
                'vnp_Amount' => $transaction->amount * 100,
                'vnp_OrderInfo' => 'Test payment',
                'vnp_SecureHash' => 'test_hash_here'
            ];

            foreach ($testParams as $key => $value) {
                $this->line("   {$key}: {$value}");
            }

        } elseif ($gateway === 'momo') {
            $this->line('   Return URL: ' . config('app.url') . '/wallet/momo/return');
            $this->line('   IPN URL: ' . config('app.url') . '/webhook/momo/notify');

            $this->comment('📝 MoMo test callback parameters:');
            $testParams = [
                'orderId' => $transaction->transaction_id,
                'resultCode' => 0,
                'transId' => time(),
                'amount' => $transaction->amount,
                'message' => 'Success',
                'signature' => 'test_signature_here'
            ];

            foreach ($testParams as $key => $value) {
                $this->line("   {$key}: {$value}");
            }
        }
    }

    private function testSignatureVerification($gateway)
    {
        try {
            if ($gateway === 'vnpay') {
                // Test VNPay signature verification with dummy data
                $testParams = [
                    'vnp_TxnRef' => 'TEST123',
                    'vnp_ResponseCode' => '00',
                    'vnp_SecureHash' => 'dummy_hash'
                ];

                // This should fail with dummy data
                $result = $this->paymentService->verifyVNPayCallback($testParams);
                $this->line('   VNPay signature verification: ' . ($result ? '✅ PASS' : '❌ FAIL (expected with test data)'));

            } elseif ($gateway === 'momo') {
                // Test MoMo signature verification with dummy data
                $testParams = [
                    'orderId' => 'TEST123',
                    'resultCode' => 0,
                    'signature' => 'dummy_signature',
                    'amount' => 100000,
                    'extraData' => '',
                    'message' => 'Success',
                    'orderInfo' => 'Test',
                    'orderType' => 'momo_wallet',
                    'partnerCode' => 'TEST',
                    'payType' => 'qr',
                    'requestId' => 'TEST123',
                    'responseTime' => time(),
                    'transId' => time()
                ];

                // This should fail with dummy data
                $result = $this->paymentService->verifyMoMoCallback($testParams);
                $this->line('   MoMo signature verification: ' . ($result ? '✅ PASS' : '❌ FAIL (expected with test data)'));
            }

        } catch (\Exception $e) {
            $this->line('   Signature verification test: ❌ ERROR - ' . $e->getMessage());
        }
    }
}
