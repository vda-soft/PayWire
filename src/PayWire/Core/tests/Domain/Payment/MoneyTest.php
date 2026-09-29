<?php

declare(strict_types=1);

namespace PayWire\Core\Tests\Domain\Payment;

use PayWire\Core\Domain\Shared\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function testArithmeticPreservesAmountsAndCurrencyScale(): void
    {
        $money = Money::create('12.34', 'USD')
            ->add(Money::create('1.66', 'USD'))
            ->multiplyBy(2)
            ->divideBy(4);

        self::assertSame('7.00', $money->getValueAtScale(2));
        self::assertSame('USD 7.00', (string) $money);
    }
}
