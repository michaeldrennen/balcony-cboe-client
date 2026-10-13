# balcony-cboe-client

PHP 8.1+ client for Cboe historical index CSV files (for example `_VIX` and `_VVIX`), part of The Balcony macro flow system.

## Usage (framework-agnostic)

```php
use MichaelDrennen\Balcony\CboeClient\Client;
use MichaelDrennen\Balcony\CboeClient\Config;

$client = new Client(new Config(ticker: '_VIX'));

foreach ($client->fetchQuotes() as $dto) {
    // $dto->ticker, $dto->observedAt, $dto->open, $dto->high, $dto->low, $dto->close
}
```

Rows with a short column count or non-numeric prices are skipped.

## Laravel persistence

```php
use MichaelDrennen\Balcony\CboeClient\Models\CboeVolatilityObservation;

foreach ($client->fetchQuotes() as $dto) {
    CboeVolatilityObservation::updateOrCreate(
        ['ticker' => $dto->ticker, 'observed_at' => $dto->observedAt->format('Y-m-d')],
        $dto->asModelAttributes(),
    );
}
```

## Errors

Failures throw `MichaelDrennen\Balcony\CboeClient\Exceptions\BalconyClientException`. HTTP 429 sets `isRateLimited()` and exposes `retryAfterSeconds`.

## Tests

```
composer install
vendor/bin/phpunit
```
