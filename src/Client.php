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
     * Parse Cboe historical CSV (Date,Open,High,Low,Close). Malformed rows are skipped.
     *
     * @return CboeQuoteDTO[]
     */
    public function parse(string $csv, string $ticker): array
    {
        $csv = ltrim($csv, "\xEF\xBB\xBF");
        $lines = preg_split('/\r\n|\r|\n/', trim($csv)) ?: [];

        if ($lines === []) {
            throw BalconyClientException::malformed('Cboe CSV is empty.');
        }

        $header = array_map(static fn (string $h): string => strtolower(trim($h)), str_getcsv(array_shift($lines)));
        $required = ['date', 'open', 'high', 'low', 'close'];

        foreach ($required as $column) {
            if (!in_array($column, $header, true)) {
                throw BalconyClientException::malformed(sprintf('Cboe CSV is missing the "%s" column.', $column));
            }
        }

        $idx = array_flip($header);
        $rows = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $cells = str_getcsv($line);

            if (count($cells) < count($header)) {
                continue;
            }

            $observedAt = self::parseDate($cells[$idx['date']] ?? null);
            $open = self::parseNumber($cells[$idx['open']] ?? null);
            $high = self::parseNumber($cells[$idx['high']] ?? null);
            $low = self::parseNumber($cells[$idx['low']] ?? null);
            $close = self::parseNumber($cells[$idx['close']] ?? null);

            if ($observedAt === null || $open === null || $high === null || $low === null || $close === null) {
                continue;
            }

            $rows[] = new CboeQuoteDTO($ticker, $observedAt, $open, $high, $low, $close);
        }

        return $rows;
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
