<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TwoFactorSetupRequest extends FormRequest
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
            'confirmTotp' => [
                'code' => [
                    'required',
                    'string',
                    'size:6',
                    'regex:/^[0-9]{6}$/'
                ]
            ],
            'disable' => [
                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'current_password'
                ]
            ],
            'generateRecoveryCodes' => [
                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'current_password'
                ]
            ],
            'verifyChallenge' => [
                'code' => [
                    'required',
                    'string',
                    function ($attribute, $value, $fail) {
                        $method = $this->input('method');

                        if ($method === 'totp' && !preg_match('/^[0-9]{6}$/', $value)) {
                            $fail('Mã TOTP phải là 6 chữ số.');
                        }

                        if (in_array($method, ['sms', 'email']) && !preg_match('/^[0-9]{6}$/', $value)) {
                            $fail('Mã xác thực phải là 6 chữ số.');
                        }

                        if ($method === 'recovery' && !preg_match('/^[A-Z0-9]{10}$/', $value)) {
                            $fail('Mã khôi phục phải là 10 ký tự chữ và số.');
                        }
                    }
                ],
                'method' => [
                    'required',
                    'string',
                    Rule::in(['totp', 'sms', 'email', 'recovery'])
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
            'code.required' => 'Vui lòng nhập mã xác thực.',
            'code.size' => 'Mã xác thực phải có đúng 6 ký tự.',
            'code.regex' => 'Mã xác thực chỉ được chứa số.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.current_password' => 'Mật khẩu không chính xác.',
            'method.required' => 'Vui lòng chọn phương thức xác thực.',
            'method.in' => 'Phương thức xác thực không hợp lệ.'
        ];
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

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Additional security checks
            $this->performSecurityChecks($validator);
        });
    }

    /**
     * Perform additional security checks
     */
    private function performSecurityChecks($validator)
    {
        /** @var User $user */
        $user = auth()->user();
        $action = $this->route()->getActionMethod();

        // Rate limiting checks
        if (in_array($action, ['confirmTotp', 'verifyChallenge'])) {
            $recentAttempts = cache()->get("2fa_attempts_{$user->id}", 0);

            if ($recentAttempts >= 5) {
                $validator->errors()->add('security', 'Quá nhiều lần thử. Vui lòng đợi 15 phút.');
            }
        }

        // Check if user already has 2FA enabled for certain actions
        if ($action === 'confirmTotp' && $user->hasTwoFactorEnabled()) {
            $validator->errors()->add('security', '2FA đã được kích hoạt cho tài khoản này.');
        }

        // Check if user has 2FA enabled for disable action
        if ($action === 'disable' && !$user->hasTwoFactorEnabled()) {
            $validator->errors()->add('security', '2FA chưa được kích hoạt.');
        }
    }
}
