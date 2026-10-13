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
        return (string) file_get_contents(__DIR__ . '/Fixtures/sample_response.csv');
    }

    public function testParsesCsvAndSkipsBadRows(): void
    {
        $dtos = (new Client(new Config('_VIX')))->parse($this->fixture(), '_VIX');

        $this->assertCount(2, $dtos);
        $this->assertSame('_VIX', $dtos[0]->ticker);
        $this->assertSame('2026-10-09', $dtos[0]->observedAt->format('Y-m-d'));
        $this->assertSame(15.70, $dtos[0]->close);
    }

    public function testFetchUsesMockedHttp(): void
    {
        $http = new GuzzleClient([
            'handler' => HandlerStack::create(new MockHandler([new Response(200, [], $this->fixture())])),
        ]);

        $dtos = (new Client(new Config('_VVIX'), $http))->fetchQuotes();

        $this->assertCount(2, $dtos);
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

    public function testMissingColumnThrows(): void
    {
        $this->expectException(BalconyClientException::class);
        (new Client(new Config()))->parse("Date,Open,High,Low\n2026-10-09,1,2,3\n", '_VIX');
    }

    public function testInvalidTickerRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Config('vix; drop');
    }
}
