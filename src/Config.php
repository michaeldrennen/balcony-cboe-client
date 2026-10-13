<?php

declare(strict_types=1);

namespace MichaelDrennen\Balcony\CboeClient;

use InvalidArgumentException;

final class Config
{
    public const DEFAULT_URL_TEMPLATE = 'https://cdn.cboe.com/api/global/delayed_quotes/charts/historical/{TICKER}.json';

    public function __construct(
        public readonly string $ticker = '_VIX',
        public readonly string $urlTemplate = self::DEFAULT_URL_TEMPLATE,
        public readonly float $timeoutSeconds = 15.0,
    ) {
        if (preg_match('/^_?[A-Z0-9]{1,15}$/', $ticker) !== 1) {
            throw new InvalidArgumentException('Cboe ticker must be uppercase letters/digits, optionally prefixed with an underscore.');
        }
    }

    public function url(): string
    {
        return str_replace('{TICKER}', $this->ticker, $this->urlTemplate);
    }
}
