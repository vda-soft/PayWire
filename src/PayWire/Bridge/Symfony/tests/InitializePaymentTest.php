<?php

declare(strict_types=1);

namespace PayWire\Bridge\Symfony\Tests;

use PayWire\Core\Application\Command\InitializePayment;
use PayWire\Core\Domain\Payment\CustomerReference;
use PayWire\Core\Domain\Payment\GatewayEnum;
use PayWire\Core\Domain\Payment\OrderReference;
use PayWire\Core\Domain\Shared\Money;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;
use Symfony\Component\Serializer\Serializer;

final class InitializePaymentTest extends TestCase
{
    public function testInitializePaymentCanBeSerializedAndDeserializedAsJson(): void
    {
        $serializer = new Serializer(
            [
                new UidNormalizer([UidNormalizer::NORMALIZATION_FORMAT_KEY => UidNormalizer::NORMALIZATION_FORMAT_RFC4122]),
                new BackedEnumNormalizer(),
                new JsonSerializableNormalizer(),
                new PropertyNormalizer(),
                new ObjectNormalizer(),
            ],
            [new JsonEncoder()],
        );
        $command = new InitializePayment(
            GatewayEnum::PayU,
            Money::of('12.34', 'USD'),
            'Test payment',
            new OrderReference('order', 'order-123'),
            new CustomerReference('customer@example.com', 'customer-123'),
            'pos-123',
        );
        $paymentId = $command->paymentId;

        $json = $serializer->serialize($command, 'json');

        self::assertSame(
            \sprintf(
                '{"paymentId":"%s","gateway":"PayU","total":{"amount":"12.34","currency":"USD"},"description":"Test payment","order":{"type":"order","id":"order-123"},"customer":{"email":"customer@example.com","id":"customer-123"},"posId":"pos-123"}',
                (string) $paymentId,
            ),
            $json,
        );

        $deserializedCommand = $serializer->deserialize($json, InitializePayment::class, 'json');

        self::assertInstanceOf(InitializePayment::class, $deserializedCommand);

        self::assertSame(
            [
                'paymentId' => (string) $paymentId,
                'gateway' => GatewayEnum::PayU,
                'total' => [
                    'amount' => '12.34',
                    'currency' => 'USD',
                ],
                'description' => 'Test payment',
                'order' => [
                    'type' => 'order',
                    'id' => 'order-123',
                ],
                'customer' => [
                    'email' => 'customer@example.com',
                    'id' => 'customer-123',
                ],
                'posId' => 'pos-123',
            ],
            [
                'paymentId' => (string) $deserializedCommand->paymentId,
                'gateway' => $deserializedCommand->gateway,
                'total' => [
                    'amount' => $deserializedCommand->total->amount,
                    'currency' => $deserializedCommand->total->currency,
                ],
                'description' => $deserializedCommand->description,
                'order' => [
                    'type' => $deserializedCommand->order->type,
                    'id' => $deserializedCommand->order->id,
                ],
                'customer' => [
                    'email' => $deserializedCommand->customer->email,
                    'id' => $deserializedCommand->customer->id,
                ],
                'posId' => $deserializedCommand->posId,
            ]
        );
    }
}
