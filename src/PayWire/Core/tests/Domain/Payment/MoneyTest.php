<?php

declare(strict_types=1);

namespace PayWire\Core\Tests\Domain\Payment;

use PayWire\Core\Domain\Shared\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function testArithmeticPreservesAmountsAndCurrencyScale(): void
    {
        $money = Money::of('12.34', 'USD')
            ->add(Money::of('1.66', 'USD'))
            ->multiplyBy(2)
            ->divideBy(4);

        self::assertSame('7.00', $money->getValueAtScale(2));
        self::assertSame('USD 7.00', (string) $money);
    }

    public function testSerialization(): void
    {
        $money = Money::of('12.34', 'USD');
        $encoded = \json_encode($money, JSON_THROW_ON_ERROR);

        self::assertSame('{"amount":"12.34","currency":"USD"}', $encoded);

        $decoded = \json_decode($encoded, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(['amount' => '12.34', 'currency' => 'USD'], $decoded);
    }
}
