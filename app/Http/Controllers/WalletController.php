<?php

namespace App\Http\Controllers;

use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    protected $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->middleware('auth');
        $this->walletService = $walletService;
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
            'amount' => 'required|numeric|min:10000|max:10000000', // 10k to 10M VND
            'gateway' => 'required|in:vnpay,momo,bank_transfer'
        ]);

        try {
            $transaction = $this->walletService->deposit(
                Auth::id(),
                $request->amount,
                $request->gateway,
                ['ip' => $request->ip()]
            );

            // In real implementation, redirect to payment gateway
            if ($request->gateway === 'vnpay') {
                return redirect()->route('wallet.vnpay', $transaction->transaction_id);
            } elseif ($request->gateway === 'momo') {
                return redirect()->route('wallet.momo', $transaction->transaction_id);
            } else {
                return view('wallet.bank-transfer', compact('transaction'));
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi tạo giao dịch: ' . $e->getMessage());
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
    public function vnpayCallback(Request $request)
    {
        // VNPay callback processing
        $transactionId = $request->vnp_TxnRef;
        $responseCode = $request->vnp_ResponseCode;

        if ($responseCode === '00') {
            $this->walletService->processDeposit($transactionId, $request->vnp_TransactionNo, 'completed');
            return redirect()->route('wallet.index')->with('success', 'Nạp tiền thành công');
        } else {
            $this->walletService->processDeposit($transactionId, null, 'failed');
            return redirect()->route('wallet.index')->with('error', 'Nạp tiền thất bại');
        }
    }

    public function momoCallback(Request $request)
    {
        // MoMo callback processing
        $transactionId = $request->orderId;
        $resultCode = $request->resultCode;

        if ($resultCode === 0) {
            $this->walletService->processDeposit($transactionId, $request->transId, 'completed');
            return redirect()->route('wallet.index')->with('success', 'Nạp tiền thành công');
        } else {
            $this->walletService->processDeposit($transactionId, null, 'failed');
            return redirect()->route('wallet.index')->with('error', 'Nạp tiền thất bại');
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
