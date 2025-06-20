<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255'
            ],
            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:255',
                'unique:users,email'
            ],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
            ],
            'phone' => [
                'required',
                'string',
                'regex:/^(\+84|84|0)[3|5|7|8|9][0-9]{8}$/', // Vietnamese phone format
                'unique:users,phone'
            ],
            'terms_accepted' => [
                'required',
                'accepted'
            ],
            'referral_code' => [
                'sometimes',
                'string',
                'alpha_num',
                'size:8'
            ],
            'device_name' => [
                'sometimes',
                'string',
                'max:255'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Full name is required',
            'name.min' => 'Full name must be at least 2 characters',
            'name.max' => 'Full name must not exceed 255 characters',
            'name.regex' => 'Full name can only contain letters and spaces',

            'email.required' => 'Email address is required',
            'email.email' => 'Please provide a valid email address',
            'email.max' => 'Email address must not exceed 255 characters',
            'email.unique' => 'This email address is already registered',

            'password.required' => 'Password is required',
            'password.confirmed' => 'Password confirmation does not match',

            'phone.required' => 'Phone number is required',
            'phone.regex' => 'Please provide a valid Vietnamese phone number',
            'phone.unique' => 'This phone number is already registered',

            'terms_accepted.required' => 'You must accept the terms and conditions',
            'terms_accepted.accepted' => 'You must accept the terms and conditions',

            'referral_code.alpha_num' => 'Referral code must contain only letters and numbers',
            'referral_code.size' => 'Referral code must be exactly 8 characters',

            'device_name.max' => 'Device name must not exceed 255 characters'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'full name',
            'email' => 'email address',
            'password' => 'password',
            'phone' => 'phone number',
            'terms_accepted' => 'terms and conditions',
            'referral_code' => 'referral code',
            'device_name' => 'device name'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Check for disposable email domains
            if ($this->email && $this->isDisposableEmail($this->email)) {
                $validator->errors()->add('email', 'Disposable email addresses are not allowed');
            }

            // Check for suspicious patterns in name (basic check only)
            if ($this->name && preg_match('/[<>]/i', $this->name)) {
                $validator->errors()->add('name', 'Name contains invalid characters');
            }

            // Validate referral code if provided
            if ($this->referral_code && !$this->isValidReferralCode($this->referral_code)) {
                $validator->errors()->add('referral_code', 'Invalid referral code');
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
                'message' => 'Registration validation failed',
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
            'email' => strtolower(trim($this->email)),
            'name' => trim($this->name),
            'phone' => $this->normalizePhoneNumber($this->phone),
        ]);
    }

    /**
     * Normalize Vietnamese phone number
     */
    private function normalizePhoneNumber(?string $phone): ?string
    {
        if (!$phone) return null;

        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Convert to standard format
        if (str_starts_with($phone, '84')) {
            $phone = '0' . substr($phone, 2);
        } elseif (str_starts_with($phone, '+84')) {
            $phone = '0' . substr($phone, 3);
        }

        return $phone;
    }

    /**
     * Check if email is from a disposable email provider
     */
    private function isDisposableEmail(string $email): bool
    {
        $disposableDomains = [
            '10minutemail.com',
            'guerrillamail.com',
            'mailinator.com',
            'tempmail.org',
            'throwaway.email',
            'yopmail.com',
            // Add more as needed
        ];

        $domain = strtolower(substr(strrchr($email, '@'), 1));

        return in_array($domain, $disposableDomains);
    }

    /**
     * Check for suspicious patterns
     */
    private function containsSuspiciousPatterns(string $text): bool
    {
        $suspiciousPatterns = [
            '/[<>]/i',                    // HTML tags
            '/javascript:/i',             // JavaScript injection
            '/script/i',                  // Script tags
            '/union.*select/i',           // SQL injection patterns
            '/\bor\s+1\s*=\s*1\b/i',     // SQL injection
            '/admin|root|test|null/i',    // Common test/admin names
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate referral code
     */
    private function isValidReferralCode(string $code): bool
    {
        // Check if referral code exists in database
        // This would typically check against a referrals table
        return true; // Placeholder implementation
    }
}
