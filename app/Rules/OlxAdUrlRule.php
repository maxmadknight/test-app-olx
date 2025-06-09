<?php

declare(strict_types=1);

namespace App\Rules;

use Illuminate\Contracts\Validation\ValidationRule;

class OlxAdUrlRule implements ValidationRule
{
    /**
     * Supported OLX domains
     */
    protected array $supportedDomains = [
        'olx.pl',
        'olx.ua',
        'olx.ro',
        'olx.bg',
        'olx.pt',
    ];

    /**
     * Validate that the URL is a valid OLX advertisement URL
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        // Basic URL validation
        if (! filter_var($value, FILTER_VALIDATE_URL)) {
            $fail('The :attribute must be a valid URL.');

            return;
        }

        // Parse URL
        $parsedUrl = parse_url($value);
        if (! isset($parsedUrl['host'], $parsedUrl['path']) || ! $parsedUrl) {
            $fail('The :attribute must be a valid URL.');

            return;
        }

        // Check domain
        $host          = $parsedUrl['host'];
        $isValidDomain = false;

        foreach ($this->supportedDomains as $domain) {
            if ($host === $domain || $host === 'www.'.$domain) {
                $isValidDomain = true;
                break;
            }
        }

        if (! $isValidDomain) {
            $fail('The :attribute must be from a supported OLX domain.');

            return;
        }

        // Check path pattern for advertisement
        $path = $parsedUrl['path'];

        // Different OLX sites have different URL patterns
        $validPatterns = [
            '~^/d/(uk|ru|oferta|obyavlenie)/.+\-ID[a-zA-Z0-9]+\.html$~',
            '~^/oferta/.+\-ID[a-zA-Z0-9]+\.html$~',
            '~^/item/.+\-ID[a-zA-Z0-9]+\.html$~',
        ];

        $isValidPath = false;
        foreach ($validPatterns as $pattern) {
            if (preg_match($pattern, $path)) {
                $isValidPath = true;
                break;
            }
        }

        if (! $isValidPath) {
            $fail('The :attribute must be a valid OLX advertisement URL.');

            return;
        }
    }
}
