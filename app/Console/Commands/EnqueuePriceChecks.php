<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AdvertisementStatusType;
use App\Jobs\CheckPriceForUrl;
use App\Models\Advertisement;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class EnqueuePriceChecks extends Command
{
    protected $signature = 'prices:enqueue {--chunk=100 : Number of advertisements to process in each chunk}';

    protected $description = 'Enqueue price checks for all advertisement URLs with verified subscribers';

    public function handle(): void
    {
        $chunkSize = (int) $this->option('chunk');
        $count     = 0;

        $this->info('Starting to enqueue price checks...');

        // Only process advertisements with active status and verified subscribers
        Advertisement::whereIn('status', [AdvertisementStatusType::NEW, AdvertisementStatusType::ACTIVE])
            ->whereHas('subscribers', fn (Builder $builder) => $builder->whereNull('verification_token'))
            ->chunkById($chunkSize, function ($advertisements) use (&$count) {
                foreach ($advertisements as $advertisement) {
                    CheckPriceForUrl::dispatch($advertisement)->onQueue('price-checks');
                    $count++;
                }

                $this->info("Enqueued {$count} advertisements for price checking so far...");
            });

        $this->info("Finished! Total of {$count} advertisements enqueued for price checking.");
        Log::info("Enqueued {$count} advertisements for price checking");
    }
}
