<?php

declare(strict_types=1);

namespace MichaelDrennen\Balcony\CboeClient;

use DateTimeImmutable;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use MichaelDrennen\Balcony\CboeClient\DTO\CboeQuoteDTO;
use MichaelDrennen\Balcony\CboeClient\Exceptions\BalconyClientException;

final class Client
{
    private readonly ClientInterface $http;

    public function __construct(
        private readonly Config $config,
        ?ClientInterface $http = null,
    ) {
        $this->http = $http ?? new GuzzleClient([
            'timeout' => $config->timeoutSeconds,
            'http_errors' => false,
        ]);
    }

    /**
     * @return CboeQuoteDTO[]
     */
    public function fetchQuotes(): array
    {
        try {
            $response = $this->http->request('GET', $this->config->url());
        } catch (GuzzleException $e) {
            throw BalconyClientException::transport($e->getMessage(), $e);
        }

        $status = $response->getStatusCode();

        if ($status === 429) {
            $retryHeader = $response->getHeaderLine('Retry-After');
            throw BalconyClientException::rateLimited(is_numeric($retryHeader) ? (int) $retryHeader : null);
        }

        if ($status >= 400) {
            throw BalconyClientException::httpError($status, (string) $response->getBody());
        }

        return $this->parse((string) $response->getBody(), $this->config->ticker);
    }

    /**
     * Parse the Cboe delayed-quotes historical JSON ({"symbol", "timestamp", "data": [{date, open,
     * high, low, close, volume}, ...]}, oldest first). Rows with a missing date or non-numeric price
     * are skipped.
     *
     * @return CboeQuoteDTO[]
     */
    public function parse(string $json, string $ticker): array
    {
        $decoded = json_decode(ltrim($json, "\xEF\xBB\xBF"), true);

        if (!is_array($decoded) || !isset($decoded['data']) || !is_array($decoded['data'])) {
            throw BalconyClientException::malformed('Cboe history JSON has no "data" array.');
        }

        $rows = [];

        foreach ($decoded['data'] as $row) {
            if (!is_array($row)) {
                continue;
            }

            $observedAt = self::parseDate(self::stringOrNull($row['date'] ?? null));
            $open = self::parseNumber(self::stringOrNull($row['open'] ?? null));
            $high = self::parseNumber(self::stringOrNull($row['high'] ?? null));
            $low = self::parseNumber(self::stringOrNull($row['low'] ?? null));
            $close = self::parseNumber(self::stringOrNull($row['close'] ?? null));

            if ($observedAt === null || $open === null || $high === null || $low === null || $close === null) {
                continue;
            }

            $rows[] = new CboeQuoteDTO($ticker, $observedAt, $open, $high, $low, $close);
        }

        return $rows;
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return is_string($value) || is_int($value) || is_float($value) ? (string) $value : null;
    }

    public static function parseNumber(?string $raw): ?float
    {
        if ($raw === null) {
            return null;
        }

        $trimmed = trim($raw);

        return ($trimmed !== '' && is_numeric($trimmed)) ? (float) $trimmed : null;
    }

    public static function parseDate(?string $raw): ?DateTimeImmutable
    {
        if ($raw === null) {
            return null;
        }

        $trimmed = trim($raw);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $trimmed);

        return ($date !== false && $date->format('Y-m-d') === $trimmed) ? $date : null;
    }
}
