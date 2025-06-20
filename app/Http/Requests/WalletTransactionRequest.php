<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WalletTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $action = $this->route()->getActionMethod();

        return match($action) {
            'deposit' => [
                'amount' => [
                    'required',
                    'numeric',
                    'min:50000',
                    'max:1000000000'
                ],
                'payment_method' => [
                    'required',
                    'string',
                    Rule::in(['bank_transfer', 'momo', 'zalopay', 'vnpay', 'card'])
                ],
                'bank_code' => [
                    'required_if:payment_method,bank_transfer',
                    'string',
                    'max:20'
                ],
                'account_number' => [
                    'nullable',
                    'string',
                    'max:20',
                    'regex:/^[0-9]+$/'
                ]
            ],
            'withdraw' => [
                'amount' => [
                    'required',
                    'numeric',
                    'min:100000',
                    'max:500000000'
                ],
                'bank_name' => [
                    'required',
                    'string',
                    'max:100'
                ],
                'account_number' => [
                    'required',
                    'string',
                    'max:20',
                    'regex:/^[0-9]+$/'
                ],
                'account_name' => [
                    'required',
                    'string',
                    'max:100',
                    'regex:/^[a-zA-ZÀ-ỹ\s]+$/'
                ],
                'otp_code' => [
                    'nullable',
                    'string',
                    'size:6',
                    'regex:/^[0-9]{6}$/'
                ]
            ],
            'convert' => [
                'from_currency' => [
                    'required',
                    'string',
                    Rule::in(['VND', 'USD', 'BTC'])
                ],
                'to_currency' => [
                    'required',
                    'string',
                    Rule::in(['VND', 'USD', 'BTC']),
                    'different:from_currency'
                ],
                'amount' => [
                    'required',
                    'numeric',
                    'min:1'
                ]
            ],
            default => []
        };
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Vui lòng nhập số tiền.',
            'amount.numeric' => 'Số tiền phải là số.',
            'amount.min' => 'Số tiền tối thiểu là :min VNĐ.',
            'amount.max' => 'Số tiền tối đa là :max VNĐ.',

            'payment_method.required' => 'Vui lòng chọn phương thức thanh toán.',
            'payment_method.in' => 'Phương thức thanh toán không hợp lệ.',

            'bank_code.required_if' => 'Vui lòng chọn ngân hàng.',

            'account_number.regex' => 'Số tài khoản chỉ được chứa số.',
            'account_number.required' => 'Vui lòng nhập số tài khoản.',

            'account_name.required' => 'Vui lòng nhập tên chủ tài khoản.',
            'account_name.regex' => 'Tên chủ tài khoản chỉ được chứa chữ cái và khoảng trắng.',

            'bank_name.required' => 'Vui lòng nhập tên ngân hàng.',

            'otp_code.size' => 'Mã OTP phải có đúng 6 chữ số.',
            'otp_code.regex' => 'Mã OTP chỉ được chứa số.',

            'from_currency.required' => 'Vui lòng chọn loại tiền gốc.',
            'to_currency.required' => 'Vui lòng chọn loại tiền đích.',
            'to_currency.different' => 'Loại tiền đích phải khác loại tiền gốc.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->performTransactionValidation($validator);
        });
    }

    /**
     * Perform transaction-specific validation
     */
    private function performTransactionValidation($validator)
    {
        /** @var User $user */
        $user = auth()->user();
        $action = $this->route()->getActionMethod();
        $amount = $this->input('amount');

        // Withdraw specific validations
        if ($action === 'withdraw') {
            // Check balance
            if ($amount > $user->balance) {
                $validator->errors()->add('amount', 'Số dư trong ví không đủ để thực hiện giao dịch này.');
            }

            // Check daily withdraw limit
            $todayWithdraws = $user->walletTransactions()
                ->where('type', 'withdrawal')
                ->whereDate('created_at', today())
                ->sum('amount');

            $dailyLimit = $this->getDailyWithdrawLimit($user);
            if (($todayWithdraws + $amount) > $dailyLimit) {
                $validator->errors()->add('amount', "Vượt quá giới hạn rút tiền hàng ngày ({$dailyLimit} VNĐ).");
            }

            // Require 2FA for large withdrawals
            if ($amount > 10000000 && !$user->hasTwoFactorEnabled()) {
                $validator->errors()->add('security', 'Giao dịch rút tiền trên 10 triệu VNĐ yêu cầu kích hoạt 2FA.');
            }

            // Check withdrawal time restrictions
            $currentHour = now()->hour;
            if ($currentHour < 6 || $currentHour > 22) {
                $validator->errors()->add('time', 'Rút tiền chỉ được phép từ 6:00 đến 22:00 hàng ngày.');
            }

            // Validate account name matches user name (basic check)
            $accountName = $this->input('account_name');
            if ($accountName && !$this->isNameSimilar($user->name, $accountName)) {
                $validator->errors()->add('account_name', 'Tên chủ tài khoản phải trùng với tên đăng ký.');
            }
        }

        // Deposit specific validations
        if ($action === 'deposit') {
            // Check daily deposit limit
            $todayDeposits = $user->walletTransactions()
                ->where('type', 'deposit')
                ->whereDate('created_at', today())
                ->sum('amount');

            $dailyDepositLimit = $this->getDailyDepositLimit($user);
            if (($todayDeposits + $amount) > $dailyDepositLimit) {
                $validator->errors()->add('amount', "Vượt quá giới hạn nạp tiền hàng ngày ({$dailyDepositLimit} VNĐ).");
            }
        }

        // Convert specific validations
        if ($action === 'convert') {
            $fromCurrency = $this->input('from_currency');
            $toCurrency = $this->input('to_currency');

            // Check if user has balance in source currency
            $balanceField = $fromCurrency === 'VND' ? 'balance' : strtolower($fromCurrency) . '_balance';
            $availableBalance = $user->$balanceField ?? 0;

            if ($amount > $availableBalance) {
                $validator->errors()->add('amount', "Số dư {$fromCurrency} không đủ để thực hiện chuyển đổi.");
            }

            // Check conversion rate availability
            if (!$this->isConversionAvailable($fromCurrency, $toCurrency)) {
                $validator->errors()->add('currency', 'Tỷ giá chuyển đổi hiện không khả dụng.');
            }
        }

        // Rate limiting for all transactions
        $recentTransactions = cache()->get("transactions_{$user->id}", 0);
        if ($recentTransactions >= 10) {
            $validator->errors()->add('rate_limit', 'Quá nhiều giao dịch trong thời gian ngắn. Vui lòng thử lại sau 15 phút.');
        }
    }

    /**
     * Get daily withdraw limit based on user subscription
     */
    private function getDailyWithdrawLimit(User $user): int
    {
        return match($user->subscription_type) {
            'trial' => 1000000,      // 1M
            'basic' => 10000000,     // 10M
            'premium' => 100000000,  // 100M
            'vip' => 1000000000,     // 1B
            default => 1000000
        };
    }

    /**
     * Get daily deposit limit based on user subscription
     */
    private function getDailyDepositLimit(User $user): int
    {
        return match($user->subscription_type) {
            'trial' => 5000000,      // 5M
            'basic' => 50000000,     // 50M
            'premium' => 500000000,  // 500M
            'vip' => 2000000000,     // 2B
            default => 5000000
        };
    }

    /**
     * Check if account name is similar to user name
     */
    private function isNameSimilar(string $userName, string $accountName): bool
    {
        // Remove accents and convert to lowercase for comparison
        $userName = $this->removeAccents(strtolower($userName));
        $accountName = $this->removeAccents(strtolower($accountName));

        // Simple similarity check (can be improved with more sophisticated algorithms)
        return similar_text($userName, $accountName) / max(strlen($userName), strlen($accountName)) > 0.7;
    }

    /**
     * Remove Vietnamese accents
     */
    private function removeAccents(string $str): string
    {
        $accents = [
            'à','á','ạ','ả','ã','â','ầ','ấ','ậ','ẩ','ẫ','ă','ằ','ắ','ặ','ẳ','ẵ',
            'è','é','ẹ','ẻ','ẽ','ê','ề','ế','ệ','ể','ễ',
            'ì','í','ị','ỉ','ĩ',
            'ò','ó','ọ','ỏ','õ','ô','ồ','ố','ộ','ổ','ỗ','ơ','ờ','ớ','ợ','ở','ỡ',
            'ù','ú','ụ','ủ','ũ','ư','ừ','ứ','ự','ử','ữ',
            'ỳ','ý','ỵ','ỷ','ỹ',
            'đ'
        ];

        $noAccents = [
            'a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a',
            'e','e','e','e','e','e','e','e','e','e','e',
            'i','i','i','i','i',
            'o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o',
            'u','u','u','u','u','u','u','u','u','u','u',
            'y','y','y','y','y',
            'd'
        ];

        return str_replace($accents, $noAccents, $str);
    }

    /**
     * Check if currency conversion is available
     */
    private function isConversionAvailable(string $from, string $to): bool
    {
        // In production, this would check with external exchange rate API
        // For now, we'll assume all conversions are available during business hours
        $currentHour = now()->hour;
        return $currentHour >= 6 && $currentHour <= 22;
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        if ($this->expectsJson()) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu giao dịch không hợp lệ.',
                    'errors' => $validator->errors()
                ], 422)
            );
        }

        parent::failedValidation($validator);
    }
}
