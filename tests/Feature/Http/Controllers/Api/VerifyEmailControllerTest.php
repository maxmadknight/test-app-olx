<?php

declare(strict_types=1);

namespace Feature\Http\Controllers\Api;

use App\Models\Advertisement;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifyEmailControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_verify_email(): void
    {
        // Create an advertisement and subscription
        $advertisement = Advertisement::create([
            'url' => 'https://www.olx.ro/d/oferta/test-ad-ID123.html',
        ]);

        $token = '12345678901234567890123456789012'; // Exactly 32 characters

        $subscription = $advertisement->subscribers()->create([
            'email'              => 'test@example.com',
            'verification_token' => $token,
            'token_expires_at'   => Carbon::now()->addHours(24),
        ]);

        // Verify the email
        $response = $this->getJson(route('verify-email', [
            'token' => $token,
            'email' => 'test@example.com',
        ]));

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Email verified successfully, your subscription has been confirmed.',
            ]);

        // Check that the token was cleared
        $subscription->refresh();
        $this->assertNull($subscription->verification_token);
        $this->assertNull($subscription->token_expires_at);
    }

    public function test_cannot_verify_with_invalid_token(): void
    {
        // Create an advertisement and subscription
        $advertisement = Advertisement::create([
            'url' => 'https://www.olx.ro/d/oferta/test-ad-ID123.html',
        ]);

        $validToken   = '12345678901234567890123456789012'; // Exactly 32 characters
        $invalidToken = '12345678901234567890123456789011'; // Different but still 32 chars

        $subscription = $advertisement->subscribers()->create([
            'email'              => 'test@example.com',
            'verification_token' => $validToken,
            'token_expires_at'   => Carbon::now()->addHours(24),
        ]);

        // Try to verify with invalid token
        $response = $this->getJson(route('verify-email', [
            'token' => $invalidToken,
            'email' => 'test@example.com',
        ]));

        $response->assertStatus(422);

        // Check that the token was not cleared
        $subscription->refresh();
        $this->assertNotNull($subscription->verification_token);
    }

    public function test_cannot_verify_with_expired_token(): void
    {
        // Create an advertisement and subscription with expired token
        $advertisement = Advertisement::create([
            'url' => 'https://www.olx.ro/d/oferta/test-ad-ID123.html',
        ]);

        $token = '12345678901234567890123456789012'; // Exactly 32 characters

        $subscription = $advertisement->subscribers()->create([
            'email'              => 'test@example.com',
            'verification_token' => $token,
            'token_expires_at'   => Carbon::now()->subHours(1), // Expired 1 hour ago
        ]);

        // Try to verify with expired token
        $response = $this->getJson(route('verify-email', [
            'token' => $token,
            'email' => 'test@example.com',
        ]));

        $response->assertStatus(422);

        // Check that the token was not cleared
        $subscription->refresh();
        $this->assertNotNull($subscription->verification_token);
    }

    public function test_cannot_verify_with_mismatched_email(): void
    {
        // Create an advertisement and subscription
        $advertisement = Advertisement::create([
            'url' => 'https://www.olx.ro/d/oferta/test-ad-ID123.html',
        ]);

        $token = '12345678901234567890123456789012'; // Exactly 32 characters

        $subscription = $advertisement->subscribers()->create([
            'email'              => 'test@example.com',
            'verification_token' => $token,
            'token_expires_at'   => Carbon::now()->addHours(24),
        ]);

        // Try to verify with wrong email
        $response = $this->getJson(route('verify-email', [
            'token' => $token,
            'email' => 'wrong@example.com',
        ]));

        $response->assertStatus(422);

        // Check that the token was not cleared
        $subscription->refresh();
        $this->assertNotNull($subscription->verification_token);
    }
}
