<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Micro-task 2.1.3.1-2.1.3.5: Campaign Validation Rules
 * Comprehensive validation for campaign creation and updates
 */
class CampaignValidationRequest extends FormRequest
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
     */
    public function rules(): array
    {
        $campaignId = $this->route('campaign') ? $this->route('campaign')->id : null;

        return [
            // Basic Information
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('campaigns', 'name')
                    ->where('user_id', auth()->id())
                    ->ignore($campaignId)
            ],
            'description' => 'nullable|string|max:2000',
            'campaign_type' => 'required|in:live,historical',

            // Micro-task 2.1.3.2: Date/time validation rules (2h)
            'start_date' => [
                'required',
                'date',
                $this->input('campaign_type') === 'live' ? 'after_or_equal:today' : 'date'
            ],
            'end_date' => 'nullable|date|after:start_date',
            'days' => 'nullable|integer|min:1|max:365',

            // Micro-task 2.1.3.1: Budget validation rules (3h)
            'initial_balance' => [
                'required',
                'numeric',
                'min:100000', // Minimum 100k VND
                'max:100000000', // Maximum 100M VND
                function ($attribute, $value, $fail) {
                    if ($value % 1000 !== 0) {
                        $fail('Số dư ban đầu phải là bội số của 1,000 VND');
                    }
                }
            ],
            'daily_bet_limit' => [
                'nullable',
                'numeric',
                'min:10000',
                'max:5000000',
                function ($attribute, $value, $fail) {
                    if ($value && $this->input('initial_balance')) {
                        if ($value > $this->input('initial_balance') * 0.5) {
                            $fail('Giới hạn đặt cược hàng ngày không được vượt quá 50% số dư ban đầu');
                        }
                    }
                }
            ],
            'max_loss_per_day' => [
                'nullable',
                'numeric',
                'min:10000',
                function ($attribute, $value, $fail) {
                    if ($value && $this->input('initial_balance')) {
                        if ($value > $this->input('initial_balance') * 0.3) {
                            $fail('Giới hạn thua tối đa mỗi ngày không được vượt quá 30% số dư ban đầu');
                        }
                    }
                }
            ],
            'total_loss_limit' => [
                'nullable',
                'numeric',
                'min:50000',
                function ($attribute, $value, $fail) {
                    if ($value && $this->input('initial_balance')) {
                        if ($value > $this->input('initial_balance') * 0.8) {
                            $fail('Giới hạn thua tổng không được vượt quá 80% số dư ban đầu');
                        }
                    }
                    if ($value && $this->input('max_loss_per_day')) {
                        if ($value < $this->input('max_loss_per_day') * 2) {
                            $fail('Giới hạn thua tổng phải ít nhất gấp đôi giới hạn thua mỗi ngày');
                        }
                    }
                }
            ],
            'target_profit' => [
                'nullable',
                'numeric',
                'min:10000',
                function ($attribute, $value, $fail) {
                    if ($value && $this->input('initial_balance')) {
                        if ($value > $this->input('initial_balance') * 10) {
                            $fail('Mục tiêu lợi nhuận không được vượt quá 10 lần số dư ban đầu');
                        }
                    }
                }
            ],

            // Stop Loss & Take Profit
            'auto_stop_loss' => 'boolean',
            'stop_loss_amount' => [
                'nullable',
                'numeric',
                'min:10000',
                'required_if:auto_stop_loss,true',
                function ($attribute, $value, $fail) {
                    if ($value && $this->input('initial_balance')) {
                        if ($value > $this->input('initial_balance')) {
                            $fail('Mức cắt lỗ không được vượt quá số dư ban đầu');
                        }
                    }
                }
            ],
            'auto_take_profit' => 'boolean',
            'take_profit_amount' => [
                'nullable',
                'numeric',
                'min:10000',
                'required_if:auto_take_profit,true'
            ],

            // Micro-task 2.1.3.3: Formula validation rules (4h)
            'betting_strategy' => [
                'required',
                'string',
                Rule::in([
                    'manual',
                    'auto_heatmap',
                    'auto_streak',
                    'auto_pattern',
                    'auto_hybrid',
                    'auto_fibonacci',
                    'auto_martingale'
                ])
            ],
            'strategy_config' => 'nullable|array',
            'strategy_config.min_confidence' => [
                'nullable',
                'numeric',
                'min:0.1',
                'max:1.0',
                'required_if:betting_strategy,auto_heatmap,auto_pattern,auto_hybrid'
            ],
            'strategy_config.max_numbers_per_day' => [
                'nullable',
                'integer',
                'min:1',
                'max:10',
                'required_if:betting_strategy,auto_heatmap,auto_streak'
            ],
            'strategy_config.bet_multiplier' => [
                'nullable',
                'numeric',
                'min:1.0',
                'max:3.0'
            ],
            'strategy_config.min_streak' => [
                'nullable',
                'integer',
                'min:2',
                'max:10',
                'required_if:betting_strategy,auto_streak'
            ],
            'strategy_config.pattern_length' => [
                'nullable',
                'integer',
                'min:3',
                'max:14',
                'required_if:betting_strategy,auto_pattern'
            ],
            'strategy_config.fibonacci_sequence' => [
                'nullable',
                'array',
                'required_if:betting_strategy,auto_fibonacci'
            ],
            'strategy_config.martingale_multiplier' => [
                'nullable',
                'numeric',
                'min:1.5',
                'max:3.0',
                'required_if:betting_strategy,auto_martingale'
            ],

            // Micro-task 2.1.3.4: Risk assessment validation (3h)
            'risk_level' => [
                'nullable',
                'string',
                Rule::in(['low', 'medium', 'high', 'extreme'])
            ],
            'risk_tolerance' => [
                'nullable',
                'numeric',
                'min:0.01',
                'max:1.0'
            ],

            // Additional Settings
            'is_public' => 'boolean',
            'notes' => 'nullable|string|max:2000',
            'template_id' => 'nullable|exists:campaign_templates,id'
        ];
    }

    /**
     * Micro-task 2.1.3.5: Custom validation messages (2h)
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            // Basic Information Messages
            'name.required' => 'Tên chiến dịch là bắt buộc',
            'name.unique' => 'Bạn đã có chiến dịch với tên này',
            'name.max' => 'Tên chiến dịch không được vượt quá 255 ký tự',
            'campaign_type.required' => 'Loại chiến dịch là bắt buộc',
            'campaign_type.in' => 'Loại chiến dịch phải là "live" hoặc "historical"',

            // Date/Time Messages
            'start_date.required' => 'Ngày bắt đầu là bắt buộc',
            'start_date.date' => 'Ngày bắt đầu phải là định dạng ngày hợp lệ',
            'start_date.after_or_equal' => 'Ngày bắt đầu không được là ngày trong quá khứ',
            'end_date.date' => 'Ngày kết thúc phải là định dạng ngày hợp lệ',
            'end_date.after' => 'Ngày kết thúc phải sau ngày bắt đầu',
            'days.integer' => 'Số ngày phải là số nguyên',
            'days.min' => 'Số ngày phải ít nhất 1 ngày',
            'days.max' => 'Số ngày không được vượt quá 365 ngày',

            // Budget Messages
            'initial_balance.required' => 'Số dư ban đầu là bắt buộc',
            'initial_balance.numeric' => 'Số dư ban đầu phải là số',
            'initial_balance.min' => 'Số dư ban đầu phải ít nhất 100,000 VND',
            'initial_balance.max' => 'Số dư ban đầu không được vượt quá 100,000,000 VND',
            'daily_bet_limit.numeric' => 'Giới hạn đặt cược hàng ngày phải là số',
            'daily_bet_limit.min' => 'Giới hạn đặt cược hàng ngày phải ít nhất 10,000 VND',
            'daily_bet_limit.max' => 'Giới hạn đặt cược hàng ngày không được vượt quá 5,000,000 VND',
            'max_loss_per_day.numeric' => 'Giới hạn thua mỗi ngày phải là số',
            'max_loss_per_day.min' => 'Giới hạn thua mỗi ngày phải ít nhất 10,000 VND',
            'total_loss_limit.numeric' => 'Giới hạn thua tổng phải là số',
            'total_loss_limit.min' => 'Giới hạn thua tổng phải ít nhất 50,000 VND',
            'target_profit.numeric' => 'Mục tiêu lợi nhuận phải là số',
            'target_profit.min' => 'Mục tiêu lợi nhuận phải ít nhất 10,000 VND',

            // Stop Loss & Take Profit Messages
            'stop_loss_amount.required_if' => 'Mức cắt lỗ là bắt buộc khi bật tự động cắt lỗ',
            'stop_loss_amount.numeric' => 'Mức cắt lỗ phải là số',
            'stop_loss_amount.min' => 'Mức cắt lỗ phải ít nhất 10,000 VND',
            'take_profit_amount.required_if' => 'Mức chốt lãi là bắt buộc khi bật tự động chốt lãi',
            'take_profit_amount.numeric' => 'Mức chốt lãi phải là số',
            'take_profit_amount.min' => 'Mức chốt lãi phải ít nhất 10,000 VND',

            // Strategy Messages
            'betting_strategy.required' => 'Chiến lược đặt cược là bắt buộc',
            'betting_strategy.in' => 'Chiến lược đặt cược không hợp lệ',
            'strategy_config.min_confidence.required_if' => 'Độ tin cậy tối thiểu là bắt buộc cho chiến lược này',
            'strategy_config.min_confidence.numeric' => 'Độ tin cậy phải là số',
            'strategy_config.min_confidence.min' => 'Độ tin cậy phải ít nhất 0.1 (10%)',
            'strategy_config.min_confidence.max' => 'Độ tin cậy không được vượt quá 1.0 (100%)',
            'strategy_config.max_numbers_per_day.required_if' => 'Số lượng số tối đa mỗi ngày là bắt buộc',
            'strategy_config.max_numbers_per_day.integer' => 'Số lượng số phải là số nguyên',
            'strategy_config.max_numbers_per_day.min' => 'Số lượng số phải ít nhất 1',
            'strategy_config.max_numbers_per_day.max' => 'Số lượng số không được vượt quá 10',
            'strategy_config.bet_multiplier.numeric' => 'Hệ số nhân đặt cược phải là số',
            'strategy_config.bet_multiplier.min' => 'Hệ số nhân đặt cược phải ít nhất 1.0',
            'strategy_config.bet_multiplier.max' => 'Hệ số nhân đặt cược không được vượt quá 3.0',
            'strategy_config.min_streak.required_if' => 'Chuỗi tối thiểu là bắt buộc cho chiến lược streak',
            'strategy_config.min_streak.integer' => 'Chuỗi tối thiểu phải là số nguyên',
            'strategy_config.min_streak.min' => 'Chuỗi tối thiểu phải ít nhất 2',
            'strategy_config.min_streak.max' => 'Chuỗi tối thiểu không được vượt quá 10',
            'strategy_config.pattern_length.required_if' => 'Độ dài pattern là bắt buộc cho chiến lược pattern',
            'strategy_config.pattern_length.integer' => 'Độ dài pattern phải là số nguyên',
            'strategy_config.pattern_length.min' => 'Độ dài pattern phải ít nhất 3',
            'strategy_config.pattern_length.max' => 'Độ dài pattern không được vượt quá 14',
            'strategy_config.fibonacci_sequence.required_if' => 'Dãy Fibonacci là bắt buộc cho chiến lược Fibonacci',
            'strategy_config.fibonacci_sequence.array' => 'Dãy Fibonacci phải là mảng số',
            'strategy_config.martingale_multiplier.required_if' => 'Hệ số Martingale là bắt buộc cho chiến lược Martingale',
            'strategy_config.martingale_multiplier.numeric' => 'Hệ số Martingale phải là số',
            'strategy_config.martingale_multiplier.min' => 'Hệ số Martingale phải ít nhất 1.5',
            'strategy_config.martingale_multiplier.max' => 'Hệ số Martingale không được vượt quá 3.0',

            // Risk Assessment Messages
            'risk_level.in' => 'Mức độ rủi ro phải là: low, medium, high, hoặc extreme',
            'risk_tolerance.numeric' => 'Khả năng chấp nhận rủi ro phải là số',
            'risk_tolerance.min' => 'Khả năng chấp nhận rủi ro phải ít nhất 0.01 (1%)',
            'risk_tolerance.max' => 'Khả năng chấp nhận rủi ro không được vượt quá 1.0 (100%)',

            // Additional Messages
            'notes.max' => 'Ghi chú không được vượt quá 2000 ký tự',
            'template_id.exists' => 'Template được chọn không tồn tại'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'tên chiến dịch',
            'description' => 'mô tả',
            'campaign_type' => 'loại chiến dịch',
            'start_date' => 'ngày bắt đầu',
            'end_date' => 'ngày kết thúc',
            'days' => 'số ngày',
            'initial_balance' => 'số dư ban đầu',
            'daily_bet_limit' => 'giới hạn đặt cược hàng ngày',
            'max_loss_per_day' => 'giới hạn thua mỗi ngày',
            'total_loss_limit' => 'giới hạn thua tổng',
            'target_profit' => 'mục tiêu lợi nhuận',
            'auto_stop_loss' => 'tự động cắt lỗ',
            'stop_loss_amount' => 'mức cắt lỗ',
            'auto_take_profit' => 'tự động chốt lãi',
            'take_profit_amount' => 'mức chốt lãi',
            'betting_strategy' => 'chiến lược đặt cược',
            'strategy_config' => 'cấu hình chiến lược',
            'risk_level' => 'mức độ rủi ro',
            'risk_tolerance' => 'khả năng chấp nhận rủi ro',
            'is_public' => 'công khai',
            'notes' => 'ghi chú',
            'template_id' => 'template'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Cross-field validation logic
            $this->validateDateRange($validator);
            $this->validateBudgetConstraints($validator);
            $this->validateStrategyConsistency($validator);
            $this->calculateRiskAssessment($validator);
        });
    }

    /**
     * Validate date range constraints
     */
    protected function validateDateRange($validator): void
    {
        $startDate = $this->input('start_date');
        $endDate = $this->input('end_date');
        $days = $this->input('days');

        if ($startDate && $endDate && $days) {
            $calculatedDays = \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($endDate)) + 1;
            if (abs($calculatedDays - $days) > 1) {
                $validator->errors()->add('days', 'Số ngày không khớp với khoảng thời gian được chọn');
            }
        }

        if ($startDate && $days && !$endDate) {
            $calculatedEndDate = \Carbon\Carbon::parse($startDate)->addDays($days - 1);
            if ($this->input('campaign_type') === 'live' && $calculatedEndDate->isPast()) {
                $validator->errors()->add('days', 'Ngày kết thúc tính toán không được là quá khứ');
            }
        }
    }

    /**
     * Validate budget constraints
     */
    protected function validateBudgetConstraints($validator): void
    {
        $initialBalance = $this->input('initial_balance');
        $dailyLimit = $this->input('daily_bet_limit');
        $dailyLoss = $this->input('max_loss_per_day');
        $totalLoss = $this->input('total_loss_limit');
        $targetProfit = $this->input('target_profit');
        $days = $this->input('days', 30);

        if ($dailyLimit && $days && $initialBalance) {
            $maxPossibleBets = $dailyLimit * $days;
            if ($maxPossibleBets > $initialBalance * 5) {
                $validator->errors()->add('daily_bet_limit', 'Tổng giới hạn đặt cược có thể vượt quá 5 lần số dư ban đầu');
            }
        }

        if ($dailyLoss && $totalLoss) {
            $minTotalFromDaily = $dailyLoss * min($days, 7);
            if ($totalLoss < $minTotalFromDaily) {
                $validator->errors()->add('total_loss_limit', 'Giới hạn thua tổng quá thấp so với giới hạn hàng ngày');
            }
        }
    }

    /**
     * Validate strategy consistency
     */
    protected function validateStrategyConsistency($validator): void
    {
        $strategy = $this->input('betting_strategy');
        $config = $this->input('strategy_config', []);

        // Validate Fibonacci sequence
        if ($strategy === 'auto_fibonacci' && isset($config['fibonacci_sequence'])) {
            if (!$this->isValidFibonacciSequence($config['fibonacci_sequence'])) {
                $validator->errors()->add('strategy_config.fibonacci_sequence', 'Dãy Fibonacci không hợp lệ');
            }
        }

        // Validate hybrid strategy
        if ($strategy === 'auto_hybrid' && isset($config['strategies'])) {
            if (count($config['strategies']) < 2) {
                $validator->errors()->add('strategy_config.strategies', 'Chiến lược hybrid cần ít nhất 2 chiến lược con');
            }
        }
    }

    /**
     * Calculate and validate risk assessment
     */
    protected function calculateRiskAssessment($validator): void
    {
        $initialBalance = $this->input('initial_balance');
        $totalLoss = $this->input('total_loss_limit');
        $dailyLoss = $this->input('max_loss_per_day');
        $targetProfit = $this->input('target_profit');

        if ($initialBalance && $totalLoss) {
            $riskRatio = $totalLoss / $initialBalance;
            $calculatedRisk = 'low';

            if ($riskRatio > 0.6) $calculatedRisk = 'extreme';
            elseif ($riskRatio > 0.4) $calculatedRisk = 'high';
            elseif ($riskRatio > 0.2) $calculatedRisk = 'medium';

            $inputRisk = $this->input('risk_level');
            if ($inputRisk && $inputRisk !== $calculatedRisk) {
                $validator->errors()->add('risk_level', "Mức độ rủi ro tính toán là '{$calculatedRisk}' dựa trên các thông số đã nhập");
            }
        }
    }

    /**
     * Check if Fibonacci sequence is valid
     */
    protected function isValidFibonacciSequence(array $sequence): bool
    {
        if (count($sequence) < 3) return false;

        for ($i = 2; $i < count($sequence); $i++) {
            if ($sequence[$i] !== $sequence[$i-1] + $sequence[$i-2]) {
                return false;
            }
        }

        return true;
    }
}
