<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\OlxAdUrlRule;
use App\Rules\UniqueSubscriptionRule;
use Illuminate\Foundation\Http\FormRequest;

class SubscribeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'url'   => ['required', new OlxAdUrlRule],
            'email' => [
                'required',
                'email',
                'indisposable',
                new UniqueSubscriptionRule($this->input('url')),
            ],
        ];
    }
}
