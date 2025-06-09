<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyEmailRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'token' => [
                'required',
                'string',
                'min:32',
                'max:32',
                Rule::exists('subscriptions', 'verification_token')
                    ->where(function ($query) {
                        // Only allow tokens that haven't expired
                        $query->whereNull('token_expires_at')
                            ->orWhere('token_expires_at', '>', now());
                    }),
            ],
            'email' => [
                'required',
                'email',
                Rule::exists('subscriptions', 'email')
                    ->where(function ($query) {
                        // Ensure the email and token match
                        if ($this->input('token')) {
                            $query->where('verification_token', $this->input('token'));
                        }
                    }),
            ],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'token.exists' => 'The verification token is invalid or has expired.',
            'email.exists' => 'The email address does not match the verification token.',
        ];
    }
}
