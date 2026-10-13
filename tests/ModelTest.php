<?php

declare(strict_types=1);

namespace MichaelDrennen\Balcony\CboeClient\Tests;

use DateTimeImmutable;
use MichaelDrennen\Balcony\CboeClient\DTO\CboeQuoteDTO;
use PHPUnit\Framework\TestCase;

final class ModelTest extends TestCase
{
    public function testDtoMapsToModelAttributes(): void
    {
        $dto = new CboeQuoteDTO('_VIX', new DateTimeImmutable('2026-10-09'), 15.22, 16.45, 14.88, 15.70);

        $this->assertSame([
            'ticker' => '_VIX',
            'observed_at' => '2026-10-09',
            'open' => 15.22,
            'high' => 16.45,
            'low' => 14.88,
            'close' => 15.70,
        ], $dto->asModelAttributes());
    }

    public function testEloquentModelAcceptsDtoAttributes(): void
    {
        if (!class_exists('Illuminate\Database\Eloquent\Model')) {
            $this->markTestSkipped('illuminate/database is not installed.');
        }

        // Eloquent's guarded-attribute check reads the column listing, so the model needs a connection.
        $capsule = new \Illuminate\Database\Capsule\Manager();
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $dto = new CboeQuoteDTO('_VIX', new DateTimeImmutable('2026-10-09'), 15.22, 16.45, 14.88, 15.70);
        $model = new \MichaelDrennen\Balcony\CboeClient\Models\CboeVolatilityObservation($dto->asModelAttributes());

        $this->assertSame('_VIX', $model->ticker);
        $this->assertSame('balcony_cboe_volatility_observations', $model->getTable());
        $this->assertSame(['id'], $model->getGuarded());
    }
}
