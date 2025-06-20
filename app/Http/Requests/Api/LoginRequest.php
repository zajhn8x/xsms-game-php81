<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:255'
            ],
            'password' => [
                'required',
                'string',
                'min:8'
            ],
            'device_name' => [
                'sometimes',
                'string',
                'max:255'
            ],
            'fcm_token' => [
                'sometimes',
                'string',
                'max:500'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email address is required',
            'email.email' => 'Please provide a valid email address',
            'email.max' => 'Email address must not exceed 255 characters',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 8 characters',
            'device_name.max' => 'Device name must not exceed 255 characters',
            'fcm_token.max' => 'FCM token must not exceed 500 characters'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'email' => 'email address',
            'password' => 'password',
            'device_name' => 'device name',
            'fcm_token' => 'FCM token'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Additional custom validation can be added here

            // Check for common attack patterns
            $email = $this->input('email');
            if ($email && $this->containsSuspiciousPatterns($email)) {
                $validator->errors()->add('email', 'Invalid email format');
            }
        });
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'data' => null
            ], 422)
        );
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower($this->email),
        ]);
    }

    /**
     * Check for suspicious patterns in email
     */
    private function containsSuspiciousPatterns(string $email): bool
    {
        $suspiciousPatterns = [
            '/[<>]/i',                    // HTML tags
            '/javascript:/i',             // JavaScript injection
            '/script/i',                  // Script tags
            '/union.*select/i',           // SQL injection patterns
            '/\bor\s+1\s*=\s*1\b/i',     // SQL injection
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $email)) {
                return true;
            }
        }

        return false;
    }
}
