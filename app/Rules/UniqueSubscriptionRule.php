<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Advertisement;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueSubscriptionRule implements ValidationRule
{
    public function __construct(protected ?string $url) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check if advertisement exists
        $advertisement = Advertisement::where('url', $this->url)->first();

        if ($advertisement) {
            // Check if subscription with this email already exists for this advertisement
            $exists = $advertisement->subscribers()
                ->where('email', $value)
                ->whereNull('verification_token') // Only consider verified subscriptions
                ->exists();

            if ($exists) {
                $fail('You are already subscribed to this advertisement.');
            }
        }
    }
}
