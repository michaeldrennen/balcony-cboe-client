<?php

declare(strict_types=1);

namespace MichaelDrennen\Balcony\CboeClient\Tests;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use MichaelDrennen\Balcony\CboeClient\Client;
use MichaelDrennen\Balcony\CboeClient\Config;
use MichaelDrennen\Balcony\CboeClient\Exceptions\BalconyClientException;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    private function fixture(): string
    {
        return (string) file_get_contents(__DIR__ . '/Fixtures/sample_response.json');
    }

    public function testParsesHistoryJson(): void
    {
        $dtos = (new Client(new Config('_VIX')))->parse($this->fixture(), '_VIX');

        // Real Cboe rows, oldest first.
        $this->assertCount(3, $dtos);
        $this->assertSame('_VIX', $dtos[0]->ticker);
        $this->assertSame('2026-10-08', $dtos[0]->observedAt->format('Y-m-d'));
        $this->assertSame(15.68, $dtos[0]->open);
        $this->assertSame(16.46, $dtos[0]->high);
        $this->assertSame(15.15, $dtos[0]->low);
        $this->assertSame(15.41, $dtos[0]->close);
        $this->assertSame('2026-10-12', $dtos[2]->observedAt->format('Y-m-d'));
        $this->assertSame(15.21, $dtos[2]->close);
    }

    public function testSkipsBadRows(): void
    {
        $json = '{"data": [{"date": "2026-10-07", "open": "bad", "high": "15.0", "low": "14.0", "close": "14.5"},'
            . ' {"date": "2026-10-08", "open": "15.68", "high": "16.46", "low": "15.15"},'
            . ' {"date": "2026-10-09", "open": "15.34", "high": "15.34", "low": "14.77", "close": "14.84"}]}';

        $dtos = (new Client(new Config('_VIX')))->parse($json, '_VIX');

        $this->assertCount(1, $dtos);
        $this->assertSame('2026-10-09', $dtos[0]->observedAt->format('Y-m-d'));
        $this->assertSame(14.84, $dtos[0]->close);
    }

    public function testFetchUsesMockedHttp(): void
    {
        $http = new GuzzleClient([
            'handler' => HandlerStack::create(new MockHandler([new Response(200, [], $this->fixture())])),
        ]);

        $dtos = (new Client(new Config('_VVIX'), $http))->fetchQuotes();

        $this->assertCount(3, $dtos);
        $this->assertSame('_VVIX', $dtos[0]->ticker);
    }

    public function testRateLimitThrows(): void
    {
        $http = new GuzzleClient([
            'handler' => HandlerStack::create(new MockHandler([new Response(429, ['Retry-After' => '5'], '')])),
            'http_errors' => false,
        ]);

        try {
            (new Client(new Config('_VIX'), $http))->fetchQuotes();
            $this->fail('Expected BalconyClientException.');
        } catch (BalconyClientException $e) {
            $this->assertTrue($e->isRateLimited());
            $this->assertSame(5, $e->retryAfterSeconds);
        }
    }

    public function testMissingDataArrayThrows(): void
    {
        $this->expectException(BalconyClientException::class);
        (new Client(new Config()))->parse('{"symbol": "_VIX"}', '_VIX');
    }

    public function testDefaultUrlIsJsonHistory(): void
    {
        $this->assertSame(
            'https://cdn.cboe.com/api/global/delayed_quotes/charts/historical/_VIX.json',
            (new Config())->url(),
        );
    }

    public function testInvalidTickerRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Config('vix; drop');
    }
}
