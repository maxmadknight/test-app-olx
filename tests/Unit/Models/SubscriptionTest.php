<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Advertisement;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_token_expired_returns_true_for_expired_token(): void
    {
        $subscription                     = new Subscription;
        $subscription->verification_token = 'test-token';
        $subscription->token_expires_at   = Carbon::now()->subHour();

        $this->assertTrue($subscription->isTokenExpired());
    }

    public function test_is_token_expired_returns_false_for_valid_token(): void
    {
        $subscription                     = new Subscription;
        $subscription->verification_token = 'test-token';
        $subscription->token_expires_at   = Carbon::now()->addHour();

        $this->assertFalse($subscription->isTokenExpired());
    }

    public function test_is_token_expired_returns_false_for_null_token(): void
    {
        $subscription                     = new Subscription;
        $subscription->verification_token = null;
        $subscription->token_expires_at   = Carbon::now()->addHour();

        $this->assertFalse($subscription->isTokenExpired());
    }

    public function test_is_token_expired_returns_false_for_null_expiration(): void
    {
        $subscription                     = new Subscription;
        $subscription->verification_token = 'test-token';
        $subscription->token_expires_at   = null;

        $this->assertFalse($subscription->isTokenExpired());
    }

    public function test_generate_verification_token_creates_token_and_expiration(): void
    {
        $subscription = new Subscription;
        $subscription->generateVerificationToken();

        $this->assertNotNull($subscription->verification_token);
        $this->assertEquals(32, strlen($subscription->verification_token));
        $this->assertNotNull($subscription->token_expires_at);
        $this->assertGreaterThan(Carbon::now(), $subscription->token_expires_at);
    }

    public function test_verify_clears_token_and_expiration(): void
    {
        $subscription                     = new Subscription;
        $subscription->email              = 'test@example.com'; // Add email field
        $subscription->verification_token = 'test-token';
        $subscription->token_expires_at   = Carbon::now()->addHour();

        // Save to database so we can test the save() call
        $advertisement = Advertisement::create([
            'url' => 'https://www.olx.ro/d/oferta/test-ad-ID123.html',
        ]);
        $advertisement->subscribers()->save($subscription);

        $subscription->verify();

        $this->assertNull($subscription->verification_token);
        $this->assertNull($subscription->token_expires_at);

        // Check that it was saved to the database
        $this->assertDatabaseHas('subscriptions', [
            'id'                 => $subscription->id,
            'verification_token' => null,
            'email'              => 'test@example.com',
        ]);
    }

    public function test_is_verified_returns_true_for_verified_subscription(): void
    {
        $subscription                     = new Subscription;
        $subscription->verification_token = null;

        $this->assertTrue($subscription->isVerified());
    }

    public function test_is_verified_returns_false_for_unverified_subscription(): void
    {
        $subscription                     = new Subscription;
        $subscription->verification_token = 'test-token';

        $this->assertFalse($subscription->isVerified());
    }

    public function test_route_notification_for_mail_returns_email(): void
    {
        $subscription        = new Subscription;
        $subscription->email = 'test@example.com';

        $this->assertEquals('test@example.com', $subscription->routeNotificationForMail());
    }
}
