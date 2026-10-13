<?php

declare(strict_types=1);

namespace MichaelDrennen\Balcony\CboeClient\Models;

use Illuminate\Database\Eloquent\Model;

final class CboeVolatilityObservation extends Model
{
    protected $table = 'balcony_cboe_volatility_observations';

    protected $guarded = ['id'];

    protected $casts = [
        'observed_at' => 'date',
        'open' => 'decimal:4',
        'high' => 'decimal:4',
        'low' => 'decimal:4',
        'close' => 'decimal:4',
    ];
}
