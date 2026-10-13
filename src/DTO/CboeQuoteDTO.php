<?php

declare(strict_types=1);

namespace MichaelDrennen\Balcony\CboeClient\DTO;

use DateTimeImmutable;

final class CboeQuoteDTO implements MetricObservation
{
    public function __construct(
        public readonly string $ticker,
        public readonly DateTimeImmutable $observedAt,
        public readonly float $open,
        public readonly float $high,
        public readonly float $low,
        public readonly float $close,
    ) {
    }

    public function asModelAttributes(): array
    {
        return [
            'ticker' => $this->ticker,
            'observed_at' => $this->observedAt->format('Y-m-d'),
            'open' => $this->open,
            'high' => $this->high,
            'low' => $this->low,
            'close' => $this->close,
        ];
    }
}
