<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\SubscriptionObserver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

#[ObservedBy([SubscriptionObserver::class])]
class Subscription extends Model
{
    use Notifiable;

    protected $fillable = [
        'email',
        'verification_token',
        'token_expires_at',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
    ];

    /**
     * Get the advertisement that this subscription belongs to
     */
    public function advertisement(): BelongsTo
    {
        return $this->belongsTo(Advertisement::class);
    }

    /**
     * Check if the verification token has expired
     */
    public function isTokenExpired(): bool
    {
        if (! $this->verification_token) {
            return false;
        }

        if (! $this->token_expires_at) {
            return false;
        }

        return $this->token_expires_at->isPast();
    }

    /**
     * Generate a new verification token
     */
    public function generateVerificationToken(): void
    {
        $this->verification_token = bin2hex(random_bytes(16)); // 32 characters
        $this->token_expires_at   = Carbon::now()->addHours(24);
    }

    /**
     * Verify the subscription
     */
    public function verify(): void
    {
        $this->verification_token = null;
        $this->token_expires_at   = null;
        $this->save();
    }

    /**
     * Check if the subscription is verified
     */
    public function isVerified(): bool
    {
        return $this->verification_token === null;
    }

    /**
     * Route notifications for the mail channel.
     */
    public function routeNotificationForMail(): string
    {
        return $this->email;
    }
}
