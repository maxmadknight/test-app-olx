<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Subscription;

class SubscriptionObserver
{
    public function creating(Subscription $subscription): void
    {
        if ($subscription->verification_token && ! $subscription->token_expires_at) {
            $subscription->token_expires_at = now()->addHours(
                config('olx.token_expiration', 24)
            );
        }
    }

    public function saving(Subscription $subscription): void
    {
        if ($subscription->verification_token === null) {
            $subscription->token_expires_at = null;
        }
    }
}
