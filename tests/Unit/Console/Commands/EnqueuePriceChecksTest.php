<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Enums\AdvertisementStatusType;
use App\Jobs\CheckPriceForUrl;
use App\Models\Advertisement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EnqueuePriceChecksTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_enqueues_jobs_for_active_advertisements_with_verified_subscribers(): void
    {
        Queue::fake();

        // Create active advertisement with verified subscriber
        $activeAd = Advertisement::create([
            'url'    => 'https://www.olx.pl/d/oferta/active-ad-CID123',
            'status' => AdvertisementStatusType::ACTIVE,
        ]);

        $activeAd->subscribers()->create([
            'email'              => 'verified@example.com',
            'verification_token' => null, // Verified
        ]);

        // Create new advertisement with verified subscriber
        $newAd = Advertisement::create([
            'url'    => 'https://www.olx.pl/d/oferta/new-ad-CID456',
            'status' => AdvertisementStatusType::NEW,
        ]);

        $newAd->subscribers()->create([
            'email'              => 'verified2@example.com',
            'verification_token' => null, // Verified
        ]);

        // Run the command
        $this->artisan('prices:enqueue')->assertSuccessful();

        // Assert jobs were dispatched for both advertisements
        Queue::assertPushed(CheckPriceForUrl::class, 2);
        Queue::assertPushed(CheckPriceForUrl::class, fn (CheckPriceForUrl $job) => $job->getAdvertisement()->id === $activeAd->id);
        Queue::assertPushed(CheckPriceForUrl::class, fn (CheckPriceForUrl $job) => $job->getAdvertisement()->id === $newAd->id);
    }

    public function test_command_ignores_advertisements_without_verified_subscribers(): void
    {
        Queue::fake();

        // Create active advertisement with unverified subscriber
        $adWithUnverifiedSubscriber = Advertisement::create([
            'url'    => 'https://www.olx.pl/d/oferta/unverified-ad-CID123',
            'status' => AdvertisementStatusType::ACTIVE,
        ]);

        $adWithUnverifiedSubscriber->subscribers()->create([
            'email'              => 'unverified@example.com',
            'verification_token' => 'some-token', // Unverified
        ]);

        // Create active advertisement with no subscribers
        Advertisement::create([
            'url'    => 'https://www.olx.pl/d/oferta/no-subscribers-ad-CID456',
            'status' => AdvertisementStatusType::ACTIVE,
        ]);

        // Run the command
        $this->artisan('prices:enqueue')->assertSuccessful();

        // Assert no jobs were dispatched
        Queue::assertNotPushed(CheckPriceForUrl::class);
    }

    public function test_command_ignores_advertisements_with_error_status(): void
    {
        Queue::fake();

        // Create advertisements with error statuses but verified subscribers
        $errorStatuses = [
            AdvertisementStatusType::PARSE_ERROR,
            AdvertisementStatusType::NOT_FOUND,
            AdvertisementStatusType::NO_PRICE,
        ];

        foreach ($errorStatuses as $status) {
            $ad = Advertisement::create([
                'url'    => "https://www.olx.pl/d/oferta/error-ad-{$status->value}",
                'status' => $status,
            ]);

            $ad->subscribers()->create([
                'email'              => "verified-{$status->value}@example.com",
                'verification_token' => null, // Verified
            ]);
        }

        // Run the command
        $this->artisan('prices:enqueue')->assertSuccessful();

        // Assert no jobs were dispatched
        Queue::assertNotPushed(CheckPriceForUrl::class);
    }
}
