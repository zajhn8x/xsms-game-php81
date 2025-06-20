<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Handle VNPay IPN webhook
     */
    public function vnpayNotify(Request $request)
    {
        try {
            Log::info('VNPay IPN received', $request->all());

            if (!$this->paymentService->verifyVNPayCallback($request->all())) {
                Log::error('VNPay IPN signature verification failed');
                return response('RspCode=97&Message=Invalid signature', 200);
            }

            $transactionId = $request->vnp_TxnRef;
            $responseCode = $request->vnp_ResponseCode;
            $gatewayTransactionId = $request->vnp_TransactionNo;

            if ($responseCode === '00') {
                $success = $this->paymentService->processSuccessfulPayment(
                    $transactionId,
                    $gatewayTransactionId,
                    'vnpay'
                );

                if ($success) {
                    return response('RspCode=00&Message=Confirm Success', 200);
                } else {
                    return response('RspCode=99&Message=Processing failed', 200);
                }
            } else {
                return response('RspCode=00&Message=Confirm Success', 200);
            }

        } catch (\Exception $e) {
            Log::error('VNPay IPN processing error', ['error' => $e->getMessage()]);
            return response('RspCode=99&Message=Error', 200);
        }
    }

    /**
     * Handle MoMo IPN webhook
     */
    public function momoNotify(Request $request)
    {
        try {
            Log::info('MoMo IPN received', $request->all());

            if (!$this->paymentService->verifyMoMoCallback($request->all())) {
                return response()->json([
                    'partnerCode' => $request->partnerCode,
                    'orderId' => $request->orderId,
                    'requestId' => $request->requestId,
                    'resultCode' => 97,
                    'message' => 'Invalid signature'
                ], 200);
            }

            $transactionId = $request->orderId;
            $resultCode = $request->resultCode;
            $gatewayTransactionId = $request->transId;

            if ($resultCode === 0) {
                $success = $this->paymentService->processSuccessfulPayment(
                    $transactionId,
                    $gatewayTransactionId,
                    'momo'
                );

                return response()->json([
                    'partnerCode' => $request->partnerCode,
                    'orderId' => $request->orderId,
                    'requestId' => $request->requestId,
                    'resultCode' => $success ? 0 : 99,
                    'message' => $success ? 'Success' : 'Processing failed'
                ], 200);
            } else {
                return response()->json([
                    'partnerCode' => $request->partnerCode,
                    'orderId' => $request->orderId,
                    'requestId' => $request->requestId,
                    'resultCode' => 0,
                    'message' => 'Success'
                ], 200);
            }

        } catch (\Exception $e) {
            Log::error('MoMo IPN processing error', ['error' => $e->getMessage()]);

            return response()->json([
                'partnerCode' => $request->partnerCode ?? '',
                'orderId' => $request->orderId ?? '',
                'requestId' => $request->requestId ?? '',
                'resultCode' => 99,
                'message' => 'Error'
            ], 200);
        }
    }
}
