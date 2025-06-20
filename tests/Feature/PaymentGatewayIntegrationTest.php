<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\PaymentService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PaymentGatewayIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $paymentService;
    protected $walletService;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentService = app(PaymentService::class);
        $this->walletService = app(WalletService::class);

        // Create test user with wallet
        $this->user = User::factory()->create();

        // Create wallet only if not exists (since UserObserver might create it)
        if (!$this->user->wallet) {
            Wallet::create([
                'user_id' => $this->user->id,
                'balance' => 0,
                'currency' => 'VND'
            ]);
        }

        // Mock HTTP requests to prevent actual API calls in tests
        Http::fake([
            '*sandbox.vnpayment.vn*' => Http::response(['status' => 'ok'], 200),
            '*test-payment.momo.vn*' => Http::response([
                'resultCode' => 0,
                'message' => 'Success',
                'payUrl' => 'https://test-payment.momo.vn/pay/test123',
                'deeplink' => 'momo://pay/test123'
            ], 200)
        ]);
    }

    /** @test */
    public function can_create_vnpay_payment_url()
    {
        // Create transaction
        $transaction = WalletTransaction::create([
            'user_id' => $this->user->id,
            'transaction_id' => 'TEST_' . time(),
            'type' => 'deposit',
            'amount' => 100000,
            'status' => 'pending',
            'gateway' => 'vnpay',
            'gateway_data' => []
        ]);

        // Generate payment URL
        $paymentUrl = $this->paymentService->createVNPayPayment($transaction);

        // Assertions
        $this->assertIsString($paymentUrl);
        $this->assertStringContainsString('vnpayment.vn', $paymentUrl);
        $this->assertStringContainsString('vnp_TxnRef=' . $transaction->transaction_id, $paymentUrl);
        $this->assertStringContainsString('vnp_Amount=' . ($transaction->amount * 100), $paymentUrl);
        $this->assertStringContainsString('vnp_SecureHash=', $paymentUrl);

        // Check transaction was updated
        $transaction->refresh();
        $this->assertArrayHasKey('gateway', $transaction->gateway_data);
        $this->assertEquals('vnpay', $transaction->gateway_data['gateway']);
    }

    /** @test */
    public function can_create_momo_payment()
    {
        // Create transaction
        $transaction = WalletTransaction::create([
            'user_id' => $this->user->id,
            'transaction_id' => 'TEST_' . time(),
            'type' => 'deposit',
            'amount' => 100000,
            'status' => 'pending',
            'gateway' => 'momo',
            'gateway_data' => []
        ]);

        // Generate MoMo payment
        $result = $this->paymentService->createMoMoPayment($transaction);

        // Assertions
        $this->assertIsArray($result);
        $this->assertArrayHasKey('payUrl', $result);
        $this->assertArrayHasKey('deeplink', $result);
        $this->assertEquals(0, $result['resultCode']);

        // Check transaction was updated
        $transaction->refresh();
        $this->assertArrayHasKey('gateway', $transaction->gateway_data);
        $this->assertEquals('momo', $transaction->gateway_data['gateway']);
    }

    /** @test */
    public function can_validate_vnpay_callback()
    {
        $config = config('payment.gateways.vnpay');

        // Create valid callback parameters
        $params = [
            'vnp_TxnRef' => 'TEST123',
            'vnp_ResponseCode' => '00',
            'vnp_TransactionNo' => 'VNP123456',
            'vnp_Amount' => 10000000, // 100k VND
            'vnp_OrderInfo' => 'Test payment'
        ];

        // Sort and create hash
        ksort($params);
        $hashData = http_build_query($params, '', '&');
        $params['vnp_SecureHash'] = hash_hmac('sha512', $hashData, $config['hash_secret']);

        // Test verification
        $isValid = $this->paymentService->verifyVNPayCallback($params);
        $this->assertTrue($isValid);

        // Test with invalid hash
        $params['vnp_SecureHash'] = 'invalid_hash';
        $isValid = $this->paymentService->verifyVNPayCallback($params);
        $this->assertFalse($isValid);
    }

    /** @test */
    public function can_validate_momo_callback()
    {
        $config = config('payment.gateways.momo');

        // Create valid callback parameters
        $params = [
            'orderId' => 'TEST123',
            'requestId' => 'REQ123',
            'amount' => 100000,
            'resultCode' => 0,
            'message' => 'Success',
            'transId' => '123456789',
            'extraData' => '',
            'orderInfo' => 'Test payment',
            'orderType' => 'momo_wallet',
            'partnerCode' => $config['partner_code'],
            'payType' => 'qr',
            'responseTime' => time()
        ];

        // Create signature
        $rawHash = "accessKey=" . $config['access_key'] .
                   "&amount=" . $params['amount'] .
                   "&extraData=" . $params['extraData'] .
                   "&message=" . $params['message'] .
                   "&orderId=" . $params['orderId'] .
                   "&orderInfo=" . $params['orderInfo'] .
                   "&orderType=" . $params['orderType'] .
                   "&partnerCode=" . $params['partnerCode'] .
                   "&payType=" . $params['payType'] .
                   "&requestId=" . $params['requestId'] .
                   "&responseTime=" . $params['responseTime'] .
                   "&resultCode=" . $params['resultCode'] .
                   "&transId=" . $params['transId'];

        $params['signature'] = hash_hmac("sha256", $rawHash, $config['secret_key']);

        // Test verification
        $isValid = $this->paymentService->verifyMoMoCallback($params);
        $this->assertTrue($isValid);

        // Test with invalid signature
        $params['signature'] = 'invalid_signature';
        $isValid = $this->paymentService->verifyMoMoCallback($params);
        $this->assertFalse($isValid);
    }

    /** @test */
    public function can_process_successful_payment()
    {
        // Create transaction
        $transaction = WalletTransaction::create([
            'user_id' => $this->user->id,
            'transaction_id' => 'TEST_' . time(),
            'type' => 'deposit',
            'amount' => 100000,
            'status' => 'pending',
            'gateway' => 'vnpay',
            'gateway_data' => []
        ]);

        $initialBalance = $this->user->wallet->balance;
        $gatewayTransactionId = 'VNP_' . time();

        // Process successful payment
        $result = $this->paymentService->processSuccessfulPayment(
            $transaction->transaction_id,
            $gatewayTransactionId,
            'vnpay'
        );

        // Assertions
        $this->assertTrue($result);

        // Check transaction status
        $transaction->refresh();
        $this->assertEquals('completed', $transaction->status);
        $this->assertEquals($gatewayTransactionId, $transaction->gateway_transaction_id);
        $this->assertNotNull($transaction->completed_at);

        // Check wallet balance updated
        $this->user->wallet->refresh();
        $this->assertEquals(
            $initialBalance + $transaction->amount,
            $this->user->wallet->balance
        );
    }

    /** @test */
    public function prevents_duplicate_payment_processing()
    {
        // Create and complete transaction
        $transaction = WalletTransaction::create([
            'user_id' => $this->user->id,
            'transaction_id' => 'TEST_' . time(),
            'type' => 'deposit',
            'amount' => 100000,
            'status' => 'completed',
            'gateway' => 'vnpay',
            'gateway_transaction_id' => 'VNP_ORIGINAL',
            'completed_at' => now(),
            'gateway_data' => []
        ]);

        $initialBalance = $this->user->wallet->balance + $transaction->amount;
        $this->user->wallet->update(['balance' => $initialBalance]);

        // Try to process again
        $result = $this->paymentService->processSuccessfulPayment(
            $transaction->transaction_id,
            'VNP_DUPLICATE',
            'vnpay'
        );

        // Should return true but not double-process
        $this->assertTrue($result);

        // Balance should not change
        $this->user->wallet->refresh();
        $this->assertEquals($initialBalance, $this->user->wallet->balance);

        // Gateway transaction ID should not change
        $transaction->refresh();
        $this->assertEquals('VNP_ORIGINAL', $transaction->gateway_transaction_id);
    }

    /** @test */
    public function validates_payment_amounts()
    {
        $testCases = [
            ['amount' => 5000, 'gateway' => 'vnpay', 'should_pass' => false], // Below minimum
            ['amount' => 10000, 'gateway' => 'vnpay', 'should_pass' => true], // Minimum
            ['amount' => 100000000, 'gateway' => 'vnpay', 'should_pass' => true], // Maximum
            ['amount' => 200000000, 'gateway' => 'vnpay', 'should_pass' => false], // Above maximum

            ['amount' => 5000, 'gateway' => 'momo', 'should_pass' => false], // Below minimum
            ['amount' => 10000, 'gateway' => 'momo', 'should_pass' => true], // Minimum
            ['amount' => 50000000, 'gateway' => 'momo', 'should_pass' => true], // Maximum
            ['amount' => 60000000, 'gateway' => 'momo', 'should_pass' => false], // Above maximum
        ];

        foreach ($testCases as $case) {
            $result = $this->paymentService->validatePayment($case['amount'], $case['gateway']);

            if ($case['should_pass']) {
                $this->assertTrue($result['valid'], "Amount {$case['amount']} should be valid for {$case['gateway']}");
            } else {
                $this->assertFalse($result['valid'], "Amount {$case['amount']} should be invalid for {$case['gateway']}");
            }
        }
    }

    /** @test */
    public function calculates_correct_fees()
    {
        // VNPay: 2.2% fee, min 2000 VND
        $vnpayFee1 = $this->paymentService->calculateFee(50000, 'vnpay'); // 1100 -> 2000 (min)
        $vnpayFee2 = $this->paymentService->calculateFee(100000, 'vnpay'); // 2200
        $vnpayFee3 = $this->paymentService->calculateFee(1000000, 'vnpay'); // 22000

        $this->assertEquals(2000, $vnpayFee1);
        $this->assertEquals(2200, $vnpayFee2);
        $this->assertEquals(22000, $vnpayFee3);

        // MoMo: 2.0% fee, min 1500 VND
        $momoFee1 = $this->paymentService->calculateFee(50000, 'momo'); // 1000 -> 1500 (min)
        $momoFee2 = $this->paymentService->calculateFee(100000, 'momo'); // 2000
        $momoFee3 = $this->paymentService->calculateFee(1000000, 'momo'); // 20000

        $this->assertEquals(1500, $momoFee1);
        $this->assertEquals(2000, $momoFee2);
        $this->assertEquals(20000, $momoFee3);
    }

    /** @test */
    public function webhook_ip_verification_works()
    {
        // Test allowed IPs
        $allowedIPs = [
            '203.171.21.100',
            '203.171.22.50',
            '123.30.235.52'
        ];

        foreach ($allowedIPs as $ip) {
            $this->assertTrue(
                $this->paymentService->isAllowedIP($ip),
                "IP {$ip} should be allowed"
            );
        }

        // Test disallowed IPs
        $disallowedIPs = [
            '192.168.1.1',
            '10.0.0.1',
            '8.8.8.8'
        ];

        foreach ($disallowedIPs as $ip) {
            $this->assertFalse(
                $this->paymentService->isAllowedIP($ip),
                "IP {$ip} should not be allowed"
            );
        }
    }

    /** @test */
    public function throws_exception_when_gateway_disabled()
    {
        // Temporarily disable VNPay
        config(['payment.gateways.vnpay.enabled' => false]);

        $transaction = WalletTransaction::create([
            'user_id' => $this->user->id,
            'transaction_id' => 'TEST_' . time(),
            'type' => 'deposit',
            'amount' => 100000,
            'status' => 'pending',
            'gateway' => 'vnpay',
            'gateway_data' => []
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('VNPay is currently disabled');

        $this->paymentService->createVNPayPayment($transaction);
    }

    /** @test */
    public function handles_missing_transaction_gracefully()
    {
        $result = $this->paymentService->processSuccessfulPayment(
            'NONEXISTENT_TRANSACTION',
            'GATEWAY_123',
            'vnpay'
        );

        $this->assertFalse($result);
    }

    /** @test */
    public function logs_payment_activities()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('VNPay payment created', \Mockery::type('array'));

        $transaction = WalletTransaction::create([
            'user_id' => $this->user->id,
            'transaction_id' => 'TEST_' . time(),
            'type' => 'deposit',
            'amount' => 100000,
            'status' => 'pending',
            'gateway' => 'vnpay',
            'gateway_data' => []
        ]);

        $this->paymentService->createVNPayPayment($transaction);
    }

    /** @test */
    public function webhook_endpoints_are_accessible()
    {
        // Test VNPay webhook
        $response = $this->post('/webhook/vnpay/notify', [
            'test' => true
        ]);

        $this->assertLessThan(500, $response->status());

        // Test MoMo webhook
        $response = $this->post('/webhook/momo/notify', [
            'test' => true
        ]);

        $this->assertLessThan(500, $response->status());
    }

    /** @test */
    public function wallet_return_urls_work()
    {
        $this->actingAs($this->user);

        // Test VNPay return with success
        $response = $this->get('/wallet/vnpay/return?' . http_build_query([
            'vnp_TxnRef' => 'TEST123',
            'vnp_ResponseCode' => '00',
            'vnp_TransactionNo' => 'VNP123',
            'vnp_SecureHash' => 'valid_hash' // This will fail verification, which is expected
        ]));

        $response->assertRedirect(route('wallet.index'));

        // Test MoMo return
        $response = $this->get('/wallet/momo/return?' . http_build_query([
            'orderId' => 'TEST123',
            'resultCode' => 0,
            'transId' => '123456',
            'signature' => 'valid_signature' // This will fail verification, which is expected
        ]));

        $response->assertRedirect(route('wallet.index'));
    }

    /** @test */
    public function bank_transfer_info_is_retrieved_correctly()
    {
        $bankInfo = $this->paymentService->getBankTransferInfo('VCB');

        $this->assertIsArray($bankInfo);
        $this->assertArrayHasKey('name', $bankInfo);
        $this->assertArrayHasKey('account_number', $bankInfo);
        $this->assertArrayHasKey('account_name', $bankInfo);
        $this->assertEquals('Vietcombank', $bankInfo['name']);
    }
}
