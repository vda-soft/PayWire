<?php

declare(strict_types=1);

namespace PayWire\Bridge\Symfony\Tests;

use PayWire\Core\Domain\Shared\Money;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

final class MoneyTest extends TestCase
{
    public function testMoneyCanBeSerializedAndDeserializedAsJson(): void
    {
        $serializer = new Serializer(
            [
                new JsonSerializableNormalizer(),
                new PropertyNormalizer(),
                new ObjectNormalizer(),
            ],
            [new JsonEncoder()],
        );
        $money = Money::of('12.34', 'USD');

        $json = $serializer->serialize($money, 'json');

        self::assertSame('{"amount":"12.34","currency":"USD"}', $json);

        $deserializedMoney = $serializer->deserialize($json, Money::class, 'json');

        self::assertInstanceOf(Money::class, $deserializedMoney);
        self::assertSame('12.34', $deserializedMoney->getValueAtScale(2));
        self::assertSame('USD 12.34', (string) $deserializedMoney);
        self::assertSame($json, $serializer->serialize($deserializedMoney, 'json'));
    }
}
