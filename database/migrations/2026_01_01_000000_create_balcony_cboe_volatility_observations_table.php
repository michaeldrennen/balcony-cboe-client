<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('balcony_cboe_volatility_observations', function (Blueprint $table): void {
            $table->id();
            $table->string('ticker', 16);
            $table->date('observed_at');
            $table->decimal('open', 8, 4);
            $table->decimal('high', 8, 4);
            $table->decimal('low', 8, 4);
            $table->decimal('close', 8, 4);
            $table->timestamps();

            $table->unique(['ticker', 'observed_at'], 'balcony_cboe_ticker_observed_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('balcony_cboe_volatility_observations');
    }
};
