<?php

namespace App\Http\Controllers;

use App\Services\WalletService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    protected $walletService;
    protected $paymentService;

    public function __construct(WalletService $walletService, PaymentService $paymentService)
    {
        $this->middleware('auth');
        $this->walletService = $walletService;
        $this->paymentService = $paymentService;
    }

    public function index()
    {
        $user = Auth::user();
        $wallet = $this->walletService->getOrCreateWallet($user->id);
        $transactions = $this->walletService->getTransactionHistory($user->id);

        return view('wallet.index', compact('wallet', 'transactions'));
    }

    public function deposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10000|max:100000000', // 10k to 100M VND
            'gateway' => 'required|in:vnpay,momo,bank_transfer'
        ]);

        try {
            // Validate payment with PaymentService
            $validation = $this->paymentService->validatePayment($request->amount, $request->gateway);
            if (!$validation['valid']) {
                return back()->with('error', $validation['message'])->withInput();
            }

            // Calculate and show fee
            $fee = $this->paymentService->calculateFee($request->amount, $request->gateway);

            $transaction = $this->walletService->deposit(
                Auth::id(),
                $request->amount,
                $request->gateway,
                ['ip' => $request->ip(), 'user_agent' => $request->userAgent()]
            );

            // Redirect to appropriate payment gateway
            if ($request->gateway === 'vnpay') {
                $paymentUrl = $this->paymentService->createVNPayPayment($transaction);
                return redirect($paymentUrl);
            } elseif ($request->gateway === 'momo') {
                $momoResult = $this->paymentService->createMoMoPayment($transaction);
                return redirect($momoResult['payUrl']);
            } else {
                $bankInfo = $this->paymentService->getBankTransferInfo('VCB'); // Default to VCB
                return view('wallet.bank-transfer', compact('transaction', 'bankInfo', 'fee'));
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi tạo giao dịch: ' . $e->getMessage())->withInput();
        }
    }

    public function withdraw(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:50000', // Min 50k VND
            'bank_account' => 'required|string',
            'bank_name' => 'required|string'
        ]);

        try {
            $transaction = $this->walletService->withdraw(
                Auth::id(),
                $request->amount,
                'bank_transfer',
                [
                    'bank_account' => $request->bank_account,
                    'bank_name' => $request->bank_name,
                    'ip' => $request->ip()
                ]
            );

            return back()->with('success', 'Yêu cầu rút tiền đã được tạo. Chúng tôi sẽ xử lý trong 24h.');

        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi rút tiền: ' . $e->getMessage());
        }
    }

    public function transfer(Request $request)
    {
        $request->validate([
            'from_type' => 'required|in:real,virtual,bonus',
            'to_type' => 'required|in:real,virtual,bonus',
            'amount' => 'required|numeric|min:1000'
        ]);

        if ($request->from_type === $request->to_type) {
            return back()->with('error', 'Không thể chuyển đổi cùng loại ví');
        }

        try {
            $this->walletService->transfer(
                Auth::id(),
                $request->from_type,
                $request->to_type,
                $request->amount,
                $request->description
            );

            return back()->with('success', 'Chuyển đổi ví thành công');

        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi chuyển đổi: ' . $e->getMessage());
        }
    }

    public function history(Request $request)
    {
        $filters = $request->only(['type', 'status', 'date_from', 'date_to']);
        $transactions = $this->walletService->getTransactionHistory(Auth::id(), $filters);

        if ($request->ajax()) {
            return response()->json([
                'transactions' => $transactions->items(),
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total()
                ]
            ]);
        }

        return view('wallet.history', compact('transactions', 'filters'));
    }

    // Payment gateway callbacks
    public function vnpayReturn(Request $request)
    {
        try {
            // Verify VNPay signature
            if (!$this->paymentService->verifyVNPayCallback($request->all())) {
                return redirect()->route('wallet.index')->with('error', 'Xác thực giao dịch thất bại');
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
                    return redirect()->route('wallet.index')->with('success', 'Nạp tiền thành công qua VNPay');
                } else {
                    return redirect()->route('wallet.index')->with('error', 'Có lỗi xử lý giao dịch');
                }
            } else {
                return redirect()->route('wallet.index')->with('error', 'Thanh toán VNPay thất bại');
            }

        } catch (\Exception $e) {
            return redirect()->route('wallet.index')->with('error', 'Lỗi xử lý callback: ' . $e->getMessage());
        }
    }

    public function momoReturn(Request $request)
    {
        try {
            // Verify MoMo signature
            if (!$this->paymentService->verifyMoMoCallback($request->all())) {
                return redirect()->route('wallet.index')->with('error', 'Xác thực giao dịch MoMo thất bại');
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

                if ($success) {
                    return redirect()->route('wallet.index')->with('success', 'Nạp tiền thành công qua MoMo');
                } else {
                    return redirect()->route('wallet.index')->with('error', 'Có lỗi xử lý giao dịch');
                }
            } else {
                return redirect()->route('wallet.index')->with('error', 'Thanh toán MoMo thất bại');
            }

        } catch (\Exception $e) {
            return redirect()->route('wallet.index')->with('error', 'Lỗi xử lý callback: ' . $e->getMessage());
        }
    }

    // Admin methods
    public function adminProcessWithdrawal(Request $request, $transactionId)
    {
        $request->validate([
            'status' => 'required|in:completed,failed',
            'admin_note' => 'nullable|string'
        ]);

        try {
            $this->walletService->processWithdrawal(
                $transactionId,
                $request->gateway_transaction_id,
                $request->status
            );

            return response()->json(['success' => true, 'message' => 'Đã xử lý giao dịch']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
