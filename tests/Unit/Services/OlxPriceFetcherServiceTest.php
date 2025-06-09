<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\NoSuchPageException;
use App\Exceptions\ParseErrorException;
use App\Services\OlxPriceFetcherService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OlxPriceFetcherServiceTest extends TestCase
{
    public function test_fetch_price_from_valid_html(): void
    {
        // Arrange
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html>
        <head><title>Test Page</title></head>
        <body>
            <script type="application/ld+json">
            {
                "offers": {
                    "price": "2500",
                    "priceCurrency": "zł"
                }
            }
            </script>
        </body>
        </html>
        HTML;

        Http::fake([
            '*' => Http::response($html, 200),
        ]);

        $service = new OlxPriceFetcherService;

        // Act
        $result = $service->fetchPrice('https://example.com/ad/123');

        // Assert
        $this->assertEquals(['price' => '2500', 'currency' => 'zł'], $result);
    }

    public function test_fetch_price_with_html_fallback(): void
    {
        // Arrange
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html>
        <head><title>Test Page</title></head>
        <body>
            <div data-testid="ad-price-container" class="css-e2ir3r">
                <h3 class="css-fqcbii">2 350 $</h3>
            </div>
        </body>
        </html>
        HTML;

        Http::fake([
            '*' => Http::response($html, 200),
        ]);

        $service = new OlxPriceFetcherService;

        // Act
        $result = $service->fetchPrice('https://example.com/ad/123');

        // Assert
        $this->assertEquals(['price' => '2350', 'currency' => '$'], $result);
    }

    public function test_fetch_price_with_invalid_html(): void
    {
        // Arrange
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html>
        <head><title>Test Page</title></head>
        <body>
            <div class="no-price-here">
                <p>Some content</p>
            </div>
        </body>
        </html>
        HTML;

        Http::fake([
            '*' => Http::response($html, 200),
        ]);

        $service = new OlxPriceFetcherService;

        // Act & Assert
        $this->expectException(ParseErrorException::class);
        $service->fetchPrice('https://example.com/ad/123');
    }

    public function test_fetch_price_with_http_error(): void
    {
        // Arrange
        Http::fake([
            '*' => Http::response('Not Found', 404),
        ]);

        $service = new OlxPriceFetcherService;

        // Act & Assert
        $this->expectException(NoSuchPageException::class);
        $service->fetchPrice('https://example.com/ad/123');
    }
}
