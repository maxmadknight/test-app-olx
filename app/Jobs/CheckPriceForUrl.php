<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\AdvertisementStatusType;
use App\Exceptions\NoSuchPageException;
use App\Exceptions\ParseErrorException;
use App\Models\Advertisement;
use App\Notifications\AdvertisementPriceChangedNotification;
use App\Services\OlxPriceFetcherService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckPriceForUrl implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [30, 60, 120];

    public function __construct(protected Advertisement $advertisement) {}

    // For tests purposes
    public function getAdvertisement()
    {
        return $this->advertisement;
    }

    public function handle(OlxPriceFetcherService $fetcherService): void
    {
        try {
            $priceData       = $fetcherService->fetchPrice($this->advertisement->url);
            $currentPrice    = $priceData['price'];
            $currentCurrency = $priceData['currency'];
        } catch (NoSuchPageException $exception) {
            report($exception);

            $this->advertisement->status = AdvertisementStatusType::NOT_FOUND;
            $this->advertisement->save();

            return;
        } catch (ParseErrorException $parseErrorException) {
            report($parseErrorException);

            $this->advertisement->status = AdvertisementStatusType::PARSE_ERROR;
            $this->advertisement->save();

            return;
        }

        if (empty($currentPrice) || empty($currentCurrency)) {
            Log::warning("Could not fetch price for {$this->advertisement->url}");

            // Deleted or did not contain price
            $this->advertisement->status = AdvertisementStatusType::NO_PRICE;
            $this->advertisement->save();

            return;
        }
        // It's first time, let`s fill the data
        if ($this->advertisement->status == AdvertisementStatusType::NEW) {
            $this->advertisement->price       = $currentPrice;
            $this->advertisement->currency    = $currentCurrency;
            $this->advertisement->change_date = now();
            $this->advertisement->status      = AdvertisementStatusType::ACTIVE;
            $this->advertisement->save();
        } elseif ($this->advertisement->price !== $currentPrice || $this->advertisement->currency !== $currentCurrency) {
            $oldPrice    = $this->advertisement->price;
            $oldCurrency = $this->advertisement->currency;

            $this->advertisement->history()->create([
                'price'      => $oldPrice,
                'currency'   => $oldCurrency,
                'change_date'=> $this->advertisement->change_date,
            ]);
            $this->advertisement->update([
                'price'       => $currentPrice,
                'currency'    => $currentCurrency,
                'change_date' => now(),
            ]);

            // Notify only verified subscribers
            $this->advertisement->subscribers()
                ->whereNull('verification_token')
                ->get()
                ->each
                ->notify(new AdvertisementPriceChangedNotification(
                    $this->advertisement,
                    $oldPrice,
                    $oldCurrency
                ));

            Log::info("Price changed for {$this->advertisement->url} - old: $oldPrice $oldCurrency, new: $currentPrice $currentCurrency");
        }
    }
}
