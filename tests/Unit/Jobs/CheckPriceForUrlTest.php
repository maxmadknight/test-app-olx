<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Enums\AdvertisementStatusType;
use App\Exceptions\NoSuchPageException;
use App\Exceptions\ParseErrorException;
use App\Jobs\CheckPriceForUrl;
use App\Models\Advertisement;
use App\Models\Subscription;
use App\Notifications\AdvertisementPriceChangedNotification;
use App\Services\OlxPriceFetcherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

class CheckPriceForUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_handles_new_advertisement_correctly(): void
    {
        // Mock the OlxPriceFetcherService
        $mockService = Mockery::mock(OlxPriceFetcherService::class);
        $mockService->shouldReceive('fetchPrice')
            ->once()
            ->andReturn(['price' => '1000', 'currency' => 'PLN']);

        $this->app->instance(OlxPriceFetcherService::class, $mockService);

        // Create a new advertisement
        $advertisement = Advertisement::create([
            'url'    => 'https://www.olx.pl/d/oferta/test-ad-CID123',
            'status' => AdvertisementStatusType::NEW,
        ]);

        // Create a verified subscriber
        $advertisement->subscribers()->create([
            'email'              => 'test@example.com',
            'verification_token' => null,
        ]);

        // Run the job
        (new CheckPriceForUrl($advertisement))->handle($mockService);

        // Refresh the model from database
        $advertisement->refresh();

        // Assert the advertisement was updated correctly
        $this->assertEquals(AdvertisementStatusType::ACTIVE, $advertisement->status);
        $this->assertEquals('1000', $advertisement->price);
        $this->assertEquals('PLN', $advertisement->currency);
        $this->assertNotNull($advertisement->change_date);
    }

    public function test_handles_price_change_correctly(): void
    {
        Notification::fake();

        // Mock the OlxPriceFetcherService
        $mockService = Mockery::mock(OlxPriceFetcherService::class);
        $mockService->shouldReceive('fetchPrice')
            ->once()
            ->andReturn(['price' => '1500', 'currency' => 'PLN']);

        $this->app->instance(OlxPriceFetcherService::class, $mockService);

        // Create an active advertisement with existing price
        $advertisement = Advertisement::create([
            'url'         => 'https://www.olx.pl/d/oferta/test-ad-CID123',
            'status'      => AdvertisementStatusType::ACTIVE,
            'price'       => 1000,
            'currency'    => 'PLN',
            'change_date' => now()->subDay(),
        ]);

        // Create a verified subscriber
        $subscriber = new Subscription([
            'email'              => 'test@example.com',
            'verification_token' => null,
        ]);
        $advertisement->subscribers()->save($subscriber);

        // Run the job
        (new CheckPriceForUrl($advertisement))->handle($mockService);

        // Refresh the model from database
        $advertisement->refresh();

        // Assert the advertisement was updated correctly
        $this->assertEquals('1500', $advertisement->price);

        // Assert history record was created
        $this->assertDatabaseHas('advertisement_histories', [
            'advertisement_id' => $advertisement->id,
            'price'            => 1000,
            'currency'         => 'PLN',
        ]);

        // Assert notification was sent
        Notification::assertSentTo(
            $subscriber,
            AdvertisementPriceChangedNotification::class
        );
    }

    public function test_handles_no_such_page_exception(): void
    {
        // Mock the OlxPriceFetcherService
        $mockService = Mockery::mock(OlxPriceFetcherService::class);
        $mockService->shouldReceive('fetchPrice')
            ->once()
            ->andThrow(new NoSuchPageException('Page not found'));

        $this->app->instance(OlxPriceFetcherService::class, $mockService);

        // Create an active advertisement
        $advertisement = Advertisement::create([
            'url'    => 'https://www.olx.pl/d/oferta/test-ad-CID123',
            'status' => AdvertisementStatusType::ACTIVE,
        ]);

        // Run the job
        (new CheckPriceForUrl($advertisement))->handle($mockService);

        // Refresh the model from database
        $advertisement->refresh();

        // Assert the advertisement status was updated
        $this->assertEquals(AdvertisementStatusType::NOT_FOUND, $advertisement->status);
    }

    public function test_handles_parse_error_exception(): void
    {
        // Mock the OlxPriceFetcherService
        $mockService = Mockery::mock(OlxPriceFetcherService::class);
        $mockService->shouldReceive('fetchPrice')
            ->once()
            ->andThrow(new ParseErrorException('Failed to parse price'));

        $this->app->instance(OlxPriceFetcherService::class, $mockService);

        // Create an active advertisement
        $advertisement = Advertisement::create([
            'url'    => 'https://www.olx.pl/d/oferta/test-ad-CID123',
            'status' => AdvertisementStatusType::ACTIVE,
        ]);

        // Run the job
        (new CheckPriceForUrl($advertisement))->handle($mockService);

        // Refresh the model from database
        $advertisement->refresh();

        // Assert the advertisement status was updated
        $this->assertEquals(AdvertisementStatusType::PARSE_ERROR, $advertisement->status);
    }

    public function test_handles_empty_price_correctly(): void
    {
        // Mock the OlxPriceFetcherService
        $mockService = Mockery::mock(OlxPriceFetcherService::class);
        $mockService->shouldReceive('fetchPrice')
            ->once()
            ->andReturn(['price' => '', 'currency' => '']);

        $this->app->instance(OlxPriceFetcherService::class, $mockService);

        // Create an active advertisement
        $advertisement = Advertisement::create([
            'url'    => 'https://www.olx.pl/d/oferta/test-ad-CID123',
            'status' => AdvertisementStatusType::ACTIVE,
        ]);

        // Run the job
        (new CheckPriceForUrl($advertisement))->handle($mockService);

        // Refresh the model from database
        $advertisement->refresh();

        // Assert the advertisement status was updated
        $this->assertEquals(AdvertisementStatusType::NO_PRICE, $advertisement->status);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
