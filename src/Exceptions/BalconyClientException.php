<?php

declare(strict_types=1);

namespace MichaelDrennen\Balcony\CboeClient\Exceptions;

use RuntimeException;
use Throwable;

final class BalconyClientException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $statusCode = 0,
        public readonly ?int $retryAfterSeconds = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public static function transport(string $message, ?Throwable $previous = null): self
    {
        return new self('Transport error contacting Cboe: ' . $message, 0, null, $previous);
    }

    public static function rateLimited(?int $retryAfterSeconds): self
    {
        return new self('Cboe rate limit reached (HTTP 429).', 429, $retryAfterSeconds);
    }

    public static function httpError(int $status, string $body): self
    {
        return new self(sprintf('Cboe returned HTTP %d: %s', $status, substr($body, 0, 500)), $status);
    }

    public static function malformed(string $message): self
    {
        return new self($message, 0);
    }

    public function isRateLimited(): bool
    {
        return $this->statusCode === 429;
    }
}
