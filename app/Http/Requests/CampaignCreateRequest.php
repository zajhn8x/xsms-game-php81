<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CampaignCreateRequest extends FormRequest
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
        return [
            'name' => [
                'required',
                'string',
                'min:3',
                'max:100',
                'regex:/^[a-zA-ZÀ-ỹ0-9\s\-_.]+$/'
            ],
            'description' => [
                'nullable',
                'string',
                'max:500'
            ],
            'initial_balance' => [
                'required',
                'numeric',
                'min:100000',
                'max:1000000000'
            ],
            'target_profit' => [
                'nullable',
                'numeric',
                'min:0',
                'gt:0'
            ],
            'max_loss' => [
                'nullable',
                'numeric',
                'min:0',
                'lte:initial_balance'
            ],
            'formula_ids' => [
                'required',
                'array',
                'min:1',
                'max:10'
            ],
            'formula_ids.*' => [
                'required',
                'integer',
                'exists:lottery_cau_meta,id'
            ],
            'betting_pattern' => [
                'required',
                'string',
                Rule::in(['conservative', 'aggressive', 'balanced'])
            ],
            'max_bet_amount' => [
                'required',
                'numeric',
                'min:10000',
                'max:100000000',
                'lte:initial_balance'
            ],
            'days' => [
                'required',
                'integer',
                'min:1',
                'max:365'
            ],
            'auto_start' => [
                'boolean'
            ],
            'stop_on_profit' => [
                'boolean'
            ],
            'stop_on_loss' => [
                'boolean'
            ],
            'enable_compound' => [
                'boolean'
            ],
            'compound_percentage' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
                'required_if:enable_compound,true'
            ],
            'risk_level' => [
                'required',
                'string',
                Rule::in(['low', 'medium', 'high'])
            ],
            'schedule_type' => [
                'nullable',
                'string',
                Rule::in(['daily', 'specific_times', 'manual'])
            ],
            'schedule_times' => [
                'nullable',
                'array',
                'required_if:schedule_type,specific_times'
            ],
            'schedule_times.*' => [
                'required',
                'date_format:H:i'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tên chiến dịch là bắt buộc.',
            'name.min' => 'Tên chiến dịch phải có ít nhất 3 ký tự.',
            'name.max' => 'Tên chiến dịch không được vượt quá 100 ký tự.',
            'name.regex' => 'Tên chiến dịch chỉ được chứa chữ, số, khoảng trắng và các ký tự đặc biệt: - _ .',

            'initial_balance.required' => 'Số dư ban đầu là bắt buộc.',
            'initial_balance.min' => 'Số dư ban đầu phải ít nhất 100,000 VNĐ.',
            'initial_balance.max' => 'Số dư ban đầu không được vượt quá 1 tỷ VNĐ.',

            'target_profit.gt' => 'Mục tiêu lợi nhuận phải lớn hơn 0.',

            'max_loss.lte' => 'Giới hạn thua lỗ không được vượt quá số dư ban đầu.',

            'formula_ids.required' => 'Vui lòng chọn ít nhất một công thức.',
            'formula_ids.min' => 'Phải chọn ít nhất một công thức.',
            'formula_ids.max' => 'Không được chọn quá 10 công thức.',
            'formula_ids.*.exists' => 'Công thức được chọn không tồn tại.',

            'betting_pattern.required' => 'Vui lòng chọn kiểu đặt cược.',
            'betting_pattern.in' => 'Kiểu đặt cược không hợp lệ.',

            'max_bet_amount.required' => 'Số tiền cược tối đa là bắt buộc.',
            'max_bet_amount.min' => 'Số tiền cược tối đa phải ít nhất 10,000 VNĐ.',
            'max_bet_amount.lte' => 'Số tiền cược tối đa không được vượt quá số dư ban đầu.',

            'days.required' => 'Số ngày chạy là bắt buộc.',
            'days.min' => 'Chiến dịch phải chạy ít nhất 1 ngày.',
            'days.max' => 'Chiến dịch không được chạy quá 365 ngày.',

            'compound_percentage.required_if' => 'Vui lòng nhập tỷ lệ gộp vốn khi bật tính năng này.',
            'compound_percentage.max' => 'Tỷ lệ gộp vốn không được vượt quá 100%.',

            'risk_level.required' => 'Vui lòng chọn mức độ rủi ro.',
            'risk_level.in' => 'Mức độ rủi ro không hợp lệ.',

            'schedule_times.required_if' => 'Vui lòng chọn thời gian cụ thể khi sử dụng lịch trình này.',
            'schedule_times.*.date_format' => 'Thời gian phải có định dạng HH:MM.'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->performBusinessValidation($validator);
        });
    }

    /**
     * Perform business logic validation
     */
    private function performBusinessValidation($validator)
    {
        /** @var User $user */
        $user = auth()->user();

        // Check user balance
        if ($this->input('initial_balance') > $user->balance) {
            $validator->errors()->add('initial_balance', 'Số dư trong ví không đủ để tạo chiến dịch này.');
        }

        // Check campaign limits based on subscription
        $activeCampaigns = $user->getActiveCampaignsCountAttribute();
        $maxCampaigns = $this->getMaxCampaignsForUser($user);

        if ($activeCampaigns >= $maxCampaigns) {
            $validator->errors()->add('limit', "Bạn đã đạt giới hạn {$maxCampaigns} chiến dịch đồng thời. Vui lòng nâng cấp tài khoản hoặc dừng một chiến dịch khác.");
        }

        // Validate target profit vs initial balance
        if ($this->input('target_profit') && $this->input('initial_balance')) {
            $profitRatio = $this->input('target_profit') / $this->input('initial_balance');
            if ($profitRatio > 10) { // Max 1000% profit target
                $validator->errors()->add('target_profit', 'Mục tiêu lợi nhuận quá cao so với vốn ban đầu (tối đa 1000%).');
            }
        }

        // Validate risk level vs betting pattern
        $riskLevel = $this->input('risk_level');
        $bettingPattern = $this->input('betting_pattern');

        if ($riskLevel === 'low' && $bettingPattern === 'aggressive') {
            $validator->errors()->add('consistency', 'Mức rủi ro thấp không phù hợp với kiểu đặt cược tích cực.');
        }

        // Validate schedule consistency
        if ($this->input('schedule_type') === 'specific_times') {
            $times = $this->input('schedule_times', []);
            if (count($times) > 10) {
                $validator->errors()->add('schedule_times', 'Không được đặt quá 10 khung giờ trong ngày.');
            }
        }

        // Check if user has 2FA for high-value campaigns
        if ($this->input('initial_balance') > 50000000 && !$user->hasTwoFactorEnabled()) {
            $validator->errors()->add('security', 'Chiến dịch với vốn trên 50 triệu VNĐ yêu cầu kích hoạt 2FA.');
        }
    }

    /**
     * Get maximum campaigns allowed for user
     */
    private function getMaxCampaignsForUser(User $user): int
    {
        return match($user->subscription_type) {
            'trial' => 1,
            'basic' => 3,
            'premium' => 10,
            'vip' => 50,
            default => 1
        };
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
                    'message' => 'Dữ liệu không hợp lệ.',
                    'errors' => $validator->errors()
                ], 422)
            );
        }

        parent::failedValidation($validator);
    }
}
