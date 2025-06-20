<?php

namespace App\Services;

use App\Models\WalletTransaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class PaymentService
{
    protected $config;

    public function __construct()
    {
        $this->config = config('payment');
    }

    /**
     * Create payment URL for VNPay
     */
    public function createVNPayPayment(WalletTransaction $transaction): string
    {
        $vnpayConfig = $this->config['gateways']['vnpay'];

        if (!$vnpayConfig['enabled']) {
            throw new Exception('VNPay is currently disabled');
        }

        try {
            $params = [
                'vnp_Version' => '2.1.0',
                'vnp_Command' => 'pay',
                'vnp_TmnCode' => $vnpayConfig['merchant_id'],
                'vnp_Amount' => $transaction->amount * 100, // VNPay requires amount in smallest unit
                'vnp_CurrCode' => 'VND',
                'vnp_TxnRef' => $transaction->transaction_id,
                'vnp_OrderInfo' => "Nap tien vao vi XSMB Game - " . $transaction->transaction_id,
                'vnp_OrderType' => 'other',
                'vnp_Locale' => 'vn',
                'vnp_ReturnUrl' => $vnpayConfig['return_url'],
                'vnp_IpnUrl' => $vnpayConfig['notify_url'],
                'vnp_CreateDate' => now()->format('YmdHis'),
                'vnp_ExpireDate' => now()->addMinutes(15)->format('YmdHis'), // 15 minutes expiry
            ];

            // Sort parameters alphabetically
            ksort($params);

            // Create signature
            $hashData = http_build_query($params, '', '&');
            $vnpSecureHash = hash_hmac('sha512', $hashData, $vnpayConfig['hash_secret']);

            $paymentUrl = $vnpayConfig['url'] . '?' . $hashData . '&vnp_SecureHash=' . $vnpSecureHash;

            // Enhanced logging with structured data
            Log::info('VNPay payment created', [
                'transaction_id' => $transaction->transaction_id,
                'user_id' => $transaction->user_id,
                'amount' => $transaction->amount,
                'currency' => 'VND',
                'gateway' => 'vnpay',
                'url_length' => strlen($paymentUrl),
                'expires_at' => now()->addMinutes(15)->toISOString(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);

            // Update transaction with gateway info
            $transaction->update([
                'gateway_data' => [
                    'gateway' => 'vnpay',
                    'created_at' => now()->toISOString(),
                    'expires_at' => now()->addMinutes(15)->toISOString(),
                    'vnp_txn_ref' => $transaction->transaction_id,
                    'vnp_amount' => $transaction->amount * 100
                ]
            ]);

            return $paymentUrl;

        } catch (Exception $e) {
            Log::error('VNPay payment creation failed', [
                'transaction_id' => $transaction->transaction_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Failed to create VNPay payment: ' . $e->getMessage());
        }
    }

    /**
     * Create payment for MoMo with enhanced error handling
     */
    public function createMoMoPayment(WalletTransaction $transaction): array
    {
        $momoConfig = $this->config['gateways']['momo'];

        if (!$momoConfig['enabled']) {
            throw new Exception('MoMo is currently disabled');
        }

        try {
            $orderId = $transaction->transaction_id;
            $requestId = $orderId . '_' . time();
            $amount = (int)$transaction->amount;
            $orderInfo = "Nạp tiền vào ví XSMB Game - " . $orderId;
            $redirectUrl = $momoConfig['return_url'];
            $ipnUrl = $momoConfig['notify_url'];
            $extraData = base64_encode(json_encode([
                'user_id' => $transaction->user_id,
                'created_at' => now()->toISOString()
            ]));

            // Create signature
            $rawHash = "accessKey=" . $momoConfig['access_key'] .
                       "&amount=" . $amount .
                       "&extraData=" . $extraData .
                       "&ipnUrl=" . $ipnUrl .
                       "&orderId=" . $orderId .
                       "&orderInfo=" . $orderInfo .
                       "&partnerCode=" . $momoConfig['partner_code'] .
                       "&redirectUrl=" . $redirectUrl .
                       "&requestId=" . $requestId .
                       "&requestType=payWithATM";

            $signature = hash_hmac("sha256", $rawHash, $momoConfig['secret_key']);

            $data = [
                'partnerCode' => $momoConfig['partner_code'],
                'partnerName' => 'XSMB Game',
                'storeId' => 'XSMBGameStore',
                'requestId' => $requestId,
                'amount' => $amount,
                'orderId' => $orderId,
                'orderInfo' => $orderInfo,
                'redirectUrl' => $redirectUrl,
                'ipnUrl' => $ipnUrl,
                'lang' => 'vi',
                'extraData' => $extraData,
                'requestType' => 'payWithATM',
                'signature' => $signature
            ];

            // Retry logic for MoMo API call
            $maxRetries = 3;
            $retryDelay = 1; // seconds
            $lastException = null;

            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    Log::info('MoMo API call attempt', [
                        'attempt' => $attempt,
                        'transaction_id' => $transaction->transaction_id,
                        'request_id' => $requestId
                    ]);

                    $response = Http::timeout(30)
                        ->withHeaders([
                            'Content-Type' => 'application/json',
                            'User-Agent' => 'XSMB-Game/1.0'
                        ])
                        ->post($momoConfig['url'], $data);

                    if ($response->successful()) {
                        $result = $response->json();

                        Log::info('MoMo payment created successfully', [
                            'transaction_id' => $transaction->transaction_id,
                            'request_id' => $requestId,
                            'amount' => $transaction->amount,
                            'momo_result_code' => $result['resultCode'] ?? null,
                            'attempt' => $attempt
                        ]);

                        if (($result['resultCode'] ?? -1) !== 0) {
                            throw new Exception('MoMo payment creation failed: ' . ($result['message'] ?? 'Unknown error'));
                        }

                        // Update transaction with gateway info
                        $transaction->update([
                            'gateway_data' => [
                                'gateway' => 'momo',
                                'created_at' => now()->toISOString(),
                                'request_id' => $requestId,
                                'order_id' => $orderId,
                                'amount' => $amount,
                                'result_code' => $result['resultCode'],
                                'pay_url' => $result['payUrl'] ?? null,
                                'deeplink' => $result['deeplink'] ?? null
                            ]
                        ]);

                        return $result;
                    }

                    throw new Exception('HTTP Error: ' . $response->status() . ' - ' . $response->body());

                } catch (Exception $e) {
                    $lastException = $e;

                    Log::warning('MoMo API call failed', [
                        'attempt' => $attempt,
                        'max_retries' => $maxRetries,
                        'transaction_id' => $transaction->transaction_id,
                        'error' => $e->getMessage()
                    ]);

                    if ($attempt < $maxRetries) {
                        sleep($retryDelay);
                        $retryDelay *= 2; // Exponential backoff
                    }
                }
            }

            // All retries failed
            Log::error('MoMo API call failed after all retries', [
                'transaction_id' => $transaction->transaction_id,
                'max_retries' => $maxRetries,
                'final_error' => $lastException->getMessage()
            ]);

            throw new Exception('MoMo API call failed after ' . $maxRetries . ' attempts: ' . $lastException->getMessage());

        } catch (Exception $e) {
            Log::error('MoMo payment creation error', [
                'transaction_id' => $transaction->transaction_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Failed to create MoMo payment: ' . $e->getMessage());
        }
    }

    /**
     * Verify VNPay callback with enhanced security
     */
    public function verifyVNPayCallback(array $params): bool
    {
        try {
            $vnpayConfig = $this->config['gateways']['vnpay'];

            // Check if required parameters exist
            if (!isset($params['vnp_SecureHash'])) {
                Log::warning('VNPay callback missing vnp_SecureHash', ['params' => $params]);
                return false;
            }

            $vnpSecureHash = $params['vnp_SecureHash'];
            unset($params['vnp_SecureHash'], $params['vnp_SecureHashType']);

            // Remove empty values and sort
            $params = array_filter($params, function($value) {
                return $value !== '' && $value !== null;
            });
            ksort($params);

            $hashData = http_build_query($params, '', '&');
            $expectedHash = hash_hmac('sha512', $hashData, $vnpayConfig['hash_secret']);

            $isValid = hash_equals($expectedHash, $vnpSecureHash);

            Log::info('VNPay callback verification', [
                'transaction_id' => $params['vnp_TxnRef'] ?? null,
                'response_code' => $params['vnp_ResponseCode'] ?? null,
                'is_valid' => $isValid,
                'ip_address' => request()->ip()
            ]);

            return $isValid;

        } catch (Exception $e) {
            Log::error('VNPay callback verification error', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            return false;
        }
    }

    /**
     * Verify MoMo callback with enhanced security
     */
    public function verifyMoMoCallback(array $params): bool
    {
        try {
            $momoConfig = $this->config['gateways']['momo'];

            // Check required parameters
            $requiredParams = ['signature', 'orderId', 'requestId', 'amount', 'resultCode'];
            foreach ($requiredParams as $param) {
                if (!isset($params[$param])) {
                    Log::warning('MoMo callback missing required parameter', [
                        'missing_param' => $param,
                        'params' => array_keys($params)
                    ]);
                    return false;
                }
            }

            $signature = $params['signature'];

            // Build raw hash string according to MoMo spec
            $rawHash = "accessKey=" . $momoConfig['access_key'] .
                       "&amount=" . $params['amount'] .
                       "&extraData=" . ($params['extraData'] ?? '') .
                       "&message=" . ($params['message'] ?? '') .
                       "&orderId=" . $params['orderId'] .
                       "&orderInfo=" . ($params['orderInfo'] ?? '') .
                       "&orderType=" . ($params['orderType'] ?? '') .
                       "&partnerCode=" . ($params['partnerCode'] ?? '') .
                       "&payType=" . ($params['payType'] ?? '') .
                       "&requestId=" . $params['requestId'] .
                       "&responseTime=" . ($params['responseTime'] ?? '') .
                       "&resultCode=" . $params['resultCode'] .
                       "&transId=" . ($params['transId'] ?? '');

            $expectedSignature = hash_hmac("sha256", $rawHash, $momoConfig['secret_key']);
            $isValid = hash_equals($expectedSignature, $signature);

            Log::info('MoMo callback verification', [
                'order_id' => $params['orderId'],
                'result_code' => $params['resultCode'],
                'trans_id' => $params['transId'] ?? null,
                'is_valid' => $isValid,
                'ip_address' => request()->ip()
            ]);

            return $isValid;

        } catch (Exception $e) {
            Log::error('MoMo callback verification error', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            return false;
        }
    }

    /**
     * Process successful payment with enhanced error handling
     */
    public function processSuccessfulPayment(string $transactionId, string $gatewayTransactionId, string $gateway): bool
    {
        try {
            $transaction = WalletTransaction::where('transaction_id', $transactionId)->first();

            if (!$transaction) {
                Log::error('Transaction not found for payment processing', [
                    'transaction_id' => $transactionId,
                    'gateway' => $gateway,
                    'gateway_transaction_id' => $gatewayTransactionId
                ]);
                return false;
            }

            if ($transaction->status === 'completed') {
                Log::warning('Duplicate payment callback received', [
                    'transaction_id' => $transactionId,
                    'gateway' => $gateway,
                    'current_status' => $transaction->status
                ]);
                return true; // Already processed, return success
            }

            // Start transaction processing
            \DB::beginTransaction();

            try {
                // Update transaction
                $transaction->update([
                    'status' => 'completed',
                    'gateway_transaction_id' => $gatewayTransactionId,
                    'completed_at' => now(),
                    'gateway_data' => array_merge($transaction->gateway_data ?? [], [
                        'completed_at' => now()->toISOString(),
                        'gateway_transaction_id' => $gatewayTransactionId,
                        'processing_gateway' => $gateway
                    ])
                ]);

                // Update user wallet
                $wallet = Wallet::where('user_id', $transaction->user_id)->first();
                if (!$wallet) {
                    throw new Exception('User wallet not found');
                }

                $oldBalance = $wallet->balance;
                $wallet->increment('balance', $transaction->amount);
                $newBalance = $wallet->fresh()->balance;

                // Create wallet history record
                \DB::table('wallet_transactions')->insert([
                    'wallet_id' => $wallet->id,
                    'type' => 'deposit',
                    'amount' => $transaction->amount,
                    'balance_before' => $oldBalance,
                    'balance_after' => $newBalance,
                    'reference_id' => $transaction->id,
                    'description' => "Deposit from {$gateway}: {$transactionId}",
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                \DB::commit();

                Log::info('Payment processed successfully', [
                    'transaction_id' => $transactionId,
                    'user_id' => $transaction->user_id,
                    'amount' => $transaction->amount,
                    'gateway' => $gateway,
                    'gateway_transaction_id' => $gatewayTransactionId,
                    'old_balance' => $oldBalance,
                    'new_balance' => $newBalance
                ]);

                // TODO: Send notification to user about successful deposit
                // $this->sendDepositNotification($transaction);

                return true;

            } catch (Exception $e) {
                \DB::rollback();
                throw $e;
            }

        } catch (Exception $e) {
            Log::error('Payment processing failed', [
                'transaction_id' => $transactionId,
                'gateway' => $gateway,
                'gateway_transaction_id' => $gatewayTransactionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Process failed payment
     */
    public function processFailedPayment(string $transactionId, string $reason, string $gateway): bool
    {
        try {
            $transaction = WalletTransaction::where('transaction_id', $transactionId)
                ->where('status', 'pending')
                ->first();

            if (!$transaction) {
                return false;
            }

            $transaction->update([
                'status' => 'failed',
                'description' => $reason,
                'processed_at' => now(),
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'failure_reason' => $reason,
                    'gateway_response' => request()->all()
                ])
            ]);

            Log::info('Payment marked as failed', [
                'transaction_id' => $transactionId,
                'gateway' => $gateway,
                'reason' => $reason
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed payment processing error', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Get bank transfer information
     */
    public function getBankTransferInfo(string $bankCode): array
    {
        $bankConfig = $this->config['gateways']['bank_transfer']['banks'][$bankCode] ?? null;

        if (!$bankConfig) {
            throw new \Exception('Bank not supported');
        }

        return $bankConfig;
    }

    /**
     * Calculate transaction fee
     */
    public function calculateFee(float $amount, string $gateway): float
    {
        $gatewayConfig = $this->config['gateways'][$gateway] ?? null;

        if (!$gatewayConfig) {
            return 0;
        }

        $feePercent = $gatewayConfig['fee_percent'] ?? 0;
        $feeMin = $gatewayConfig['fee_min'] ?? 0;

        return max($amount * ($feePercent / 100), $feeMin);
    }

    /**
     * Validate payment amount and gateway
     */
    public function validatePayment(float $amount, string $gateway): array
    {
        $gatewayConfig = $this->config['gateways'][$gateway] ?? null;

        if (!$gatewayConfig) {
            return ['valid' => false, 'message' => 'Payment gateway not supported'];
        }

        if (!$gatewayConfig['enabled']) {
            return ['valid' => false, 'message' => 'Payment gateway is currently disabled'];
        }

        if ($amount < $gatewayConfig['min_amount']) {
            return [
                'valid' => false,
                'message' => "Minimum amount is " . number_format($gatewayConfig['min_amount']) . ' VND'
            ];
        }

        if ($amount > $gatewayConfig['max_amount']) {
            return [
                'valid' => false,
                'message' => "Maximum amount is " . number_format($gatewayConfig['max_amount']) . ' VND'
            ];
        }

        return ['valid' => true, 'message' => 'Payment valid'];
    }

    /**
     * Check if IP is allowed for webhook
     */
    public function isAllowedIP(string $ip): bool
    {
        if (!$this->config['webhook_security']['verify_ip']) {
            return true;
        }

        $allowedIPs = $this->config['webhook_security']['allowed_ips'];

        foreach ($allowedIPs as $allowedIP) {
            if ($this->ipInRange($ip, $allowedIP)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP is in range
     */
    private function ipInRange(string $ip, string $range): bool
    {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }

        list($subnet, $bits) = explode('/', $range);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet &= $mask;

        return ($ip & $mask) === $subnet;
    }
}
