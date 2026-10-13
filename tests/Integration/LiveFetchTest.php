<?php

declare(strict_types=1);

namespace MichaelDrennen\Balcony\CboeClient\Tests\Integration;

use DateTimeImmutable;
use MichaelDrennen\Balcony\CboeClient\Client;
use MichaelDrennen\Balcony\CboeClient\Config;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Hits the real remote source. Excluded from the default run; use `vendor/bin/phpunit --group integration`.
 */
#[Group('integration')]
final class LiveFetchTest extends TestCase
{
    private const MAX_AGE_DAYS = 10;

    public function testFetchesRecentDataFromLiveSource(): void
    {
        $dtos = (new Client(new Config()))->fetchQuotes();

        $this->assertNotEmpty($dtos, 'Expected VIX history from the Cboe CDN.');

        $newest = max(array_map(static fn ($dto): DateTimeImmutable => $dto->observedAt, $dtos));
        $ageDays = (int) $newest->diff(new DateTimeImmutable())->format('%a');

        $this->assertLessThanOrEqual(
            self::MAX_AGE_DAYS,
            $ageDays,
            sprintf('Newest record is from %s; the source may have stopped updating.', $newest->format('Y-m-d')),
        );
    }
}
