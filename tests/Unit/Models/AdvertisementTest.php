<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\AdvertisementStatusType;
use App\Models\Advertisement;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvertisementTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_subscribers_returns_only_verified_subscriptions(): void
    {
        // Create an advertisement
        $advertisement = Advertisement::create([
            'url'    => 'https://www.olx.ro/d/oferta/test-ad-ID123.html',
            'status' => AdvertisementStatusType::ACTIVE,
        ]);

        // Create verified subscription
        $verifiedSubscription = $advertisement->subscribers()->create([
            'email'              => 'verified@example.com',
            'verification_token' => null,
        ]);

        // Create unverified subscription
        $unverifiedSubscription = $advertisement->subscribers()->create([
            'email'              => 'unverified@example.com',
            'verification_token' => 'some-token',
        ]);

        // Get verified subscribers
        $verifiedSubscribers = $advertisement->verifiedSubscribers()->get();

        // Assert only verified subscription is returned
        $this->assertEquals(1, $verifiedSubscribers->count());
        $this->assertEquals($verifiedSubscription->id, $verifiedSubscribers->first()->id);
        $this->assertEquals('verified@example.com', $verifiedSubscribers->first()->email);
    }

    public function test_formatted_price_attribute_returns_price_with_currency(): void
    {
        $advertisement           = new Advertisement;
        $advertisement->price    = 1500;
        $advertisement->currency = 'USD';

        $this->assertEquals('1500 USD', $advertisement->formatted_price);
    }

    public function test_formatted_price_attribute_returns_na_when_price_missing(): void
    {
        $advertisement           = new Advertisement;
        $advertisement->price    = null;
        $advertisement->currency = 'USD';

        $this->assertEquals('N/A', $advertisement->formatted_price);
    }

    public function test_formatted_price_attribute_returns_na_when_currency_missing(): void
    {
        $advertisement           = new Advertisement;
        $advertisement->price    = 1500;
        $advertisement->currency = null;

        $this->assertEquals('N/A', $advertisement->formatted_price);
    }
}
