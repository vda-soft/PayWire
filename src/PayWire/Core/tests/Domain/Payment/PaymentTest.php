<?php

declare(strict_types=1);

namespace PayWire\Core\Tests\Domain\Payment;

use PayWire\Core\Domain\Payment\CustomerReference;
use PayWire\Core\Domain\Payment\GatewayEnum;
use PayWire\Core\Domain\Payment\OrderReference;
use PayWire\Core\Domain\Payment\Payment;
use PayWire\Core\Domain\Payment\PaymentAlreadyInStatus;
use PayWire\Core\Domain\Payment\PaymentCompleted;
use PayWire\Core\Domain\Payment\PaymentId;
use PayWire\Core\Domain\Payment\PaymentInitialized;
use PayWire\Core\Domain\Payment\PaymentStatus;
use PayWire\Core\Domain\Payment\PaymentSubmitted;
use PayWire\Core\Domain\Shared\Money;
use PayWire\Core\Domain\Shared\SubmissionResult;
use PayWire\Core\Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;

final class PaymentTest extends TestCase
{
    public function testInitializeRecordsEventAndReleaseClearsEvents(): void
    {
        $payment = Payment::initialize(
            new PaymentId(),
            GatewayEnum::PayU,
            Money::of('12.34', 'USD'),
            new OrderReference('giftCard', 'order-id'),
            new CustomerReference('customer@paywire.fake', 'customer-id'),
            'Payment for #123'
        );

        $events = \iterator_to_array($payment->releaseEvents());

        self::assertCount(1, $events);
        self::assertInstanceOf(PaymentInitialized::class, $events[0]);
        self::assertSame($payment->createdAt, $events[0]->occurredAt);
        self::assertSame($payment->createdAt, $payment->updatedAt);
        self::assertSame($events[0]->occurredAt->getTimestamp(), $payment->eventLog[0]['timestamp']);
        self::assertSame([], \iterator_to_array($payment->releaseEvents()));
    }

    public function testCompletingPaymentRecordsCompletionEvent(): void
    {
        $payment = Payment::initialize(
            new PaymentId(),
            GatewayEnum::PayU,
            Money::zero('USD'),
            new OrderReference('giftCard', 'order-id'),
            new CustomerReference('customer@paywire.fake', 'customer-id'),
            'Payment for #123'
        );

        $payment->markSubmitted(new SubmissionResult('external-id', ['accepted' => true]));
        $payment->markCompleted();

        self::assertSame(PaymentStatus::COMPLETED, $payment->status);
        $events = \iterator_to_array($payment->releaseEvents());

        self::assertInstanceOf(PaymentInitialized::class, $events[0]);
        self::assertInstanceOf(PaymentSubmitted::class, $events[1]);
        self::assertInstanceOf(PaymentCompleted::class, $events[2]);
        self::assertSame(
            [
                PaymentInitialized::class,
                PaymentSubmitted::class,
                SubmissionResult::class,
                PaymentCompleted::class,
            ],
            \array_column($payment->eventLog, 'type')
        );
        self::assertSame(['accepted' => true], $payment->eventLog[2]['data']['rawResponse']);
        self::assertSame($events[2]->occurredAt->getTimestamp(), $payment->eventLog[2]['timestamp']);
        self::assertSame($events[2]->occurredAt->getTimestamp(), $payment->eventLog[1]['timestamp']);
        self::assertSame($events[1]->occurredAt->getTimestamp(), $payment->updatedAt->getTimestamp());
    }

    public function testLargeSerializedEventDataIsTruncatedInEventLogWithNotice(): void
    {
        $payment = Payment::initialize(
            new PaymentId(),
            GatewayEnum::PayU,
            Money::zero('USD'),
            new OrderReference('giftCard', 'order-id'),
            new CustomerReference('customer@paywire.fake', 'customer-id'),
            'Payment for #123'
        );
        $response = \str_repeat('x', 5000);
        $result = new SubmissionResult('external-id', $response);

        $payment->markSubmitted($result);

        self::assertSame($response, $result->rawResponse);
        self::assertLessThanOrEqual(4096, \strlen($payment->eventLog[2]['data']['preview']));
        self::assertStringStartsWith('{"externalId":"external-id","rawResponse":"', $payment->eventLog[2]['data']['preview']);
    }

    public function testLargeSerializedArrayEventDataIsTruncatedInEventLog(): void
    {
        $payment = Payment::initialize(
            new PaymentId(),
            GatewayEnum::PayU,
            Money::zero('USD'),
            new OrderReference('giftCard', 'order-id'),
            new CustomerReference('customer@paywire.fake', 'customer-id'),
            'Payment for #123'
        );

        $payment->markSubmitted(new SubmissionResult('external-id', ['payload' => \str_repeat('x', 5000)]));

        self::assertIsString($payment->eventLog[2]['data']['preview']);
        self::assertLessThanOrEqual(4096, \strlen($payment->eventLog[2]['data']['preview']));
    }

    public function testCannotCompletePaymentTwice(): void
    {
        $payment = Payment::initialize(
            new PaymentId(),
            GatewayEnum::PayU,
            Money::zero('USD'),
            new OrderReference('giftCard', 'order-id'),
            new CustomerReference('customer@paywire.fake', 'customer-id'),
            'Payment for #123'
        );

        $payment->markCompleted();

        $this->expectException(PaymentAlreadyInStatus::class);

        $payment->markCompleted();
    }

    public function testCanSubmitOnlyWhenStateMachineAllowsTransitionToSubmitted(): void
    {
        $payment = Payment::initialize(
            new PaymentId(),
            GatewayEnum::PayU,
            Money::zero('USD'),
            new OrderReference('giftCard', 'order-id'),
            new CustomerReference('customer@paywire.fake', 'customer-id'),
            'Payment for #123'
        );

        self::assertTrue($payment->canSubmit());

        $payment->markSubmitted(new SubmissionResult('external-id', 'accepted'));

        self::assertFalse($payment->canSubmit());

        $payment->markCompleted();

        self::assertFalse($payment->canSubmit());
    }

    #[Group('time-sensitive')]
    public function testCreatedAtAndUpdatedAtAreFrozenWhenClockMocked(): void
    {
        $fixedTime = new \DateTimeImmutable('2024-01-15 10:30:00');
        $this->freezeTime($fixedTime);

        $payment = Payment::initialize(
            new PaymentId(),
            GatewayEnum::PayU,
            Money::zero('USD'),
            new OrderReference('giftCard', 'order-id'),
            new CustomerReference('customer@paywire.fake', 'customer-id'),
            'Payment for #123'
        );

        self::assertSame($fixedTime, $payment->createdAt);
        self::assertSame($fixedTime, $payment->updatedAt);

        $events = \iterator_to_array($payment->releaseEvents());
        self::assertSame($fixedTime, $events[0]->occurredAt);
        self::assertSame($fixedTime->getTimestamp(), $payment->eventLog[0]['timestamp']);

        // tearDown() will reset the clock automatically
    }
}
