<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NoSuchPageException;
use App\Exceptions\ParseErrorException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class OlxPriceFetcherService
{
    /**
     * Fetch price data from an OLX advertisement URL
     *
     * @param  string  $url  The URL of the OLX advertisement
     * @return array{price: string, currency: string} Associative array with price and currency
     *
     * @throws NoSuchPageException If the page cannot be fetched
     * @throws ParseErrorException If the price data cannot be parsed
     */
    public function fetchPrice(string $url): array
    {
        $attempts = config('app.olx.attempts', 3);
        $timeout  = config('app.olx.timeout', 15);
        $proxies  = config('app.proxies', []);

        for ($i = 1; $i <= $attempts; $i++) {
            try {
                $httpOptions = [
                    'timeout' => $timeout,
                ];

                // Use proxy if available
                if (! empty($proxies)) {
                    $httpOptions['proxy'] = Arr::random($proxies);
                }

                $response = Http::withOptions($httpOptions)
                    ->withHeaders([
                        'User-Agent'      => $this->getRandomUserAgent(),
                        'Accept-Language' => 'uk,en;q=0.9,ru;q=0.8',
                    ])
                    ->get($url);

                if ($response->status() === 429) {
                    // Exponential backoff
                    $sleepTime = pow(2, $i - 1);
                    Log::warning("Got 429 on attempt {$i}, backing off for {$sleepTime} seconds...");
                    sleep($sleepTime);

                    continue;
                }

                if ($response->failed()) {
                    throw new NoSuchPageException("Failed to fetch URL with status {$response->status()}");
                }

                $html = $response->body();

                // Try JSON-LD parsing first
                try {
                    return $this->parseJsonLd($html);
                } catch (ParseErrorException $e) {
                    // Fallback to HTML parsing
                    Log::info('JSON-LD parsing failed, trying HTML parsing');

                    return $this->parseHtml($html);
                }
            } catch (NoSuchPageException $exception) {
                throw $exception;
            } catch (\Throwable $e) {
                Log::error("Attempt {$i}: {$e->getMessage()}");
                // Exponential backoff
                $sleepTime = pow(2, $i - 1);
                sleep($sleepTime);
            }
        }

        throw new ParseErrorException("Failed to fetch price after {$attempts} attempts.");
    }

    /**
     * Parse price data from JSON-LD script tags
     */
    protected function parseJsonLd(string $html): array
    {
        $scriptNodes = (new Crawler($html))->filterXPath('//script[@type="application/ld+json"]');

        foreach ($scriptNodes as $scriptNode) {
            $json = json_decode($scriptNode->nodeValue, true);

            if (
                isset($json['offers']['price'])
                && isset($json['offers']['priceCurrency'])
            ) {
                return [
                    'price'    => $json['offers']['price'],
                    'currency' => $json['offers']['priceCurrency'],
                ];
            }
        }

        throw new ParseErrorException('Price data not found in JSON-LD.');
    }

    /**
     * Fallback method to parse price from HTML
     */
    protected function parseHtml(string $html): array
    {
        $crawler = new Crawler($html);

        // Try to find price using data-testid attribute first (most reliable)
        try {
            $priceContainer = $crawler->filter('[data-testid="ad-price-container"]');
            if ($priceContainer->count() > 0) {
                $priceText = trim($priceContainer->text());

                // Extract numeric price and currency
                if (preg_match('/([0-9\s,.]+)\s*([A-Za-z$€£¥₽]+)/', $priceText, $matches)) {
                    $price    = preg_replace('/[^0-9]/', '', $matches[1]);
                    $currency = trim($matches[2]);

                    return [
                        'price'    => $price,
                        'currency' => $currency,
                    ];
                }
            }
        } catch (\Exception $e) {
            // Continue to other selectors
        }

        // Try other common OLX price selectors as fallback
        $priceSelectors = [
            '.price-wrapper .price h3',
            '.css-okktvh-Text',
            '.css-10b0gli',
            '.pricelabel__value',
        ];

        foreach ($priceSelectors as $selector) {
            try {
                $priceElement = $crawler->filter($selector);
                if ($priceElement->count() > 0) {
                    $priceText = trim($priceElement->text());

                    // Extract numeric price and currency
                    if (preg_match('/([0-9\s,.]+)\s*([A-Za-z$€£¥₽]+)/', $priceText, $matches)) {
                        $price    = preg_replace('/[^0-9]/', '', $matches[1]);
                        $currency = trim($matches[2]);

                        return [
                            'price'    => $price,
                            'currency' => $currency,
                        ];
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        throw new ParseErrorException('Price data not found in HTML.');
    }

    protected function getRandomUserAgent(): string
    {
        return Arr::random([
            // Chrome Win
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
            // Chrome Mac
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
            // Firefox Win
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:126.0) Gecko/20100101 Firefox/126.0',
            // Firefox Mac
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:126.0) Gecko/20100101 Firefox/126.0',
            // Safari Mac
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 12_5) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Safari/605.1.15',
            // Edge Win
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36 Edg/125.0.0.0',
            // Opera Win
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36 OPR/109.0.0.0',
            // Yandex Browser
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 YaBrowser/24.3.3.827 Yowser/2.5 Safari/537.36',
            // Chrome Android
            'Mozilla/5.0 (Linux; Android 13; Pixel 6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Mobile Safari/537.36',
            // Safari iPhone
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Mobile/15E148 Safari/604.1',
        ]);
    }
}
