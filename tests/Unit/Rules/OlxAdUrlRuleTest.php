<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\OlxAdUrlRule;
use Tests\TestCase;

class OlxAdUrlRuleTest extends TestCase
{
    private OlxAdUrlRule $rule;

    private \Closure $failCallback;

    private bool $failed;

    private string $failMessage;

    public function test_valid_olx_pl_url_passes_validation(): void
    {
        $url = 'https://www.olx.pl/d/oferta/test-ad-ID123abc.html';

        $this->rule->validate('url', $url, $this->failCallback);

        $this->assertFalse($this->failed);
    }

    public function test_valid_olx_ro_url_passes_validation(): void
    {
        $url = 'https://www.olx.ro/d/oferta/test-ad-ID123abc.html';

        $this->rule->validate('url', $url, $this->failCallback);

        $this->assertFalse($this->failed);
    }

    public function test_valid_olx_ua_url_passes_validation(): void
    {
        $url = 'https://www.olx.ua/d/uk/test-ad-ID123abc.html';

        $this->rule->validate('url', $url, $this->failCallback);

        $this->assertFalse($this->failed);
    }

    public function test_url_with_query_parameters_passes_validation(): void
    {
        $url = 'https://www.olx.pl/d/oferta/test-ad-ID123abc.html?param=value';

        $this->rule->validate('url', $url, $this->failCallback);

        $this->assertFalse($this->failed);
    }

    public function test_non_olx_url_fails_validation(): void
    {
        $url = 'https://www.example.com/test-ad';

        $this->rule->validate('url', $url, $this->failCallback);

        $this->assertTrue($this->failed);
        $this->assertEquals('The :attribute must be from a supported OLX domain.', $this->failMessage);
    }

    public function test_invalid_url_format_fails_validation(): void
    {
        $url = 'not-a-url';

        $this->rule->validate('url', $url, $this->failCallback);

        $this->assertTrue($this->failed);
        $this->assertEquals('The :attribute must be a valid URL.', $this->failMessage);
    }

    public function test_olx_url_without_id_fails_validation(): void
    {
        $url = 'https://www.olx.pl/d/oferta/test-ad.html';

        $this->rule->validate('url', $url, $this->failCallback);

        $this->assertTrue($this->failed);
        $this->assertEquals('The :attribute must be a valid OLX advertisement URL.', $this->failMessage);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule        = new OlxAdUrlRule;
        $this->failed      = false;
        $this->failMessage = '';

        $this->failCallback = function ($message) {
            $this->failed      = true;
            $this->failMessage = $message;
        };
    }
}
