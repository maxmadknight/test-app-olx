<?php

declare(strict_types=1);

namespace Feature\Http\Controllers\Api;

use App\Models\Advertisement;
use App\Models\Subscription;
use App\Notifications\EmailConfirmationNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SubscribeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_subscribe_to_advertisement(): void
    {
        Notification::fake();

        $data = [
            'url'   => 'https://www.olx.ro/d/oferta/capota-portbagaj-audi-a5-b9-an-2016-2020-IDhdiGp.html?reason=hp%7Cpromoted',
            'email' => 'test@gmail.com',
        ];

        $response = $this->postJson(route('subscribe'), $data);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Subscription created. Please check your email to verify.',
            ]);

        $this->assertDatabaseHas('advertisements', [
            'url' => $data['url'],
        ]);

        $subscription = Subscription::where('email', $data['email'])->first();
        $this->assertNotNull($subscription);
        $this->assertNotNull($subscription->verification_token);
        $this->assertNotNull($subscription->token_expires_at);

        Notification::assertSentTo(
            $subscription,
            EmailConfirmationNotification::class
        );
    }

    public function test_cannot_subscribe_with_invalid_data(): void
    {
        $response = $this->postJson(route('subscribe'), [
            'url'   => 'invalid-url',
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url', 'email']);
    }

    public function test_cannot_subscribe_with_missing_data(): void
    {
        $response = $this->postJson(route('subscribe'), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url', 'email']);
    }

    public function test_cannot_subscribe_with_missing_url(): void
    {
        $response = $this->postJson(route('subscribe'), [
            'email' => 'valid@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url'])
            ->assertJsonMissingValidationErrors(['email']);
    }

    public function test_cannot_subscribe_with_missing_email(): void
    {
        $response = $this->postJson(route('subscribe'), [
            'url' => 'https://www.olx.ro/d/oferta/capota-portbagaj-audi-a5-b9-an-2016-2020-IDhdiGp.html',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonMissingValidationErrors(['url']);
    }

    public function test_cannot_subscribe_with_non_olx_url(): void
    {
        $response = $this->postJson(route('subscribe'), [
            'url'   => 'https://www.example.com/some-page',
            'email' => 'valid@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    public function test_cannot_subscribe_with_same_email_to_same_advertisement(): void
    {
        Notification::fake();

        // Create first advertisement and subscription
        $advertisement = Advertisement::create([
            'url' => 'https://www.olx.ro/d/oferta/capota-portbagaj-audi-a5-b9-an-2016-2020-IDhdiGp.html',
        ]);

        $subscription = $advertisement->subscribers()->create([
            'email'              => 'test@example.com',
            'verification_token' => null, // Verified subscription
        ]);

        // Try to subscribe to the same advertisement with the same email
        $data = [
            'url'   => $advertisement->url,
            'email' => 'test@example.com',
        ];

        $response = $this->postJson(route('subscribe'), $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJson([
                'errors' => [
                    'email' => [
                        'You are already subscribed to this advertisement.',
                    ],
                ],
            ]);

        // Should not create a new subscription
        $this->assertDatabaseCount('subscriptions', 1);
    }

    public function test_can_resubscribe_with_expired_token(): void
    {
        Notification::fake();

        // Create first advertisement and subscription with expired token
        $advertisement = Advertisement::create([
            'url' => 'https://www.olx.ro/d/oferta/capota-portbagaj-audi-a5-b9-an-2016-2020-IDhdiGp.html',
        ]);

        $subscription = $advertisement->subscribers()->create([
            'email'              => 'test@example.com',
            'verification_token' => 'expired-token',
            'token_expires_at'   => Carbon::now()->subDay(), // Expired token
        ]);

        // Try to subscribe again
        $data = [
            'url'   => $advertisement->url,
            'email' => 'test@example.com',
        ];

        $response = $this->postJson(route('subscribe'), $data);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Subscription created. Please check your email to verify.',
            ]);

        // Should update the existing subscription with a new token
        $this->assertDatabaseCount('subscriptions', 1);

        $updatedSubscription = Subscription::first();
        $this->assertNotEquals('expired-token', $updatedSubscription->verification_token);
        $this->assertGreaterThan(Carbon::now(), $updatedSubscription->token_expires_at);

        Notification::assertSentTo(
            $updatedSubscription,
            EmailConfirmationNotification::class
        );
    }

    public function test_can_subscribe_with_existing_advertisement(): void
    {
        Notification::fake();

        // Create an advertisement first
        $advertisement = Advertisement::create([
            'url' => 'https://www.olx.ro/d/oferta/capota-portbagaj-audi-a5-b9-an-2016-2020-IDhdiGp.html',
        ]);

        $data = [
            'url'   => $advertisement->url,
            'email' => 'new@example.com',
        ];

        $response = $this->postJson(route('subscribe'), $data);

        $response->assertStatus(200);

        // Should not create a new advertisement
        $this->assertEquals(1, Advertisement::count());

        // But should create a new subscription
        $this->assertDatabaseHas('subscriptions', [
            'email'            => $data['email'],
            'advertisement_id' => $advertisement->id,
        ]);
    }

    public function test_can_subscribe_with_existing_email_for_different_advertisement(): void
    {
        Notification::fake();

        // Create first advertisement and subscription
        $advertisement1 = Advertisement::create([
            'url' => 'https://www.olx.ro/d/oferta/capota-portbagaj-audi-a5-b9-an-2016-2020-IDhdiGp.html?reason=hp%7Cpromoted',
        ]);

        $subscription = $advertisement1->subscribers()->create([
            'email'              => 'test@example.com',
            'verification_token' => null, // Verified
        ]);

        // Try to subscribe to a different advertisement with the same email
        $data = [
            'url'   => 'https://www.olx.pl/d/oferta/stara-cegla-cegla-z-rozbiorki-cegla-z-odzysku-25x12x6-5rf-CID628-ID165YM8.html',
            'email' => 'test@example.com',
        ];

        $response = $this->postJson(route('subscribe'), $data);

        $response->assertStatus(200);

        $this->assertDatabaseCount('advertisements', 2);

        // Should create a new subscription
        $this->assertDatabaseCount('subscriptions', 2);
    }
}
