<?php

declare(strict_types=1);

namespace MichaelDrennen\Balcony\CboeClient\DTO;

interface MetricObservation
{
    /**
     * Attributes ready for Eloquent upsert/updateOrCreate.
     *
     * @return array<string, mixed>
     */
    public function asModelAttributes(): array;
}
