<?php

declare(strict_types=1);

namespace PayWire\Core\Tests\Domain\Payment;

use PayWire\Core\Domain\Payment\GatewayEnum;
use PayWire\Core\Domain\Payment\Payment;
use PayWire\Core\Domain\Payment\PaymentAlreadyInStatus;
use PayWire\Core\Domain\Payment\PaymentCompleted;
use PayWire\Core\Domain\Payment\PaymentId;
use PayWire\Core\Domain\Payment\PaymentInitialized;
use PayWire\Core\Domain\Payment\PaymentStatus;
use PayWire\Core\Domain\Payment\PaymentSubmitted;
use PayWire\Core\Domain\Shared\Money;
use PHPUnit\Framework\TestCase;

final class PaymentTest extends TestCase
{
    public function testInitializeRecordsEventAndReleaseClearsEvents(): void
    {
        $payment = Payment::initialize(
            PaymentId::generate(),
            GatewayEnum::PayU,
            Money::create('12.34', 'USD'),
        );

        $events = \iterator_to_array($payment->releaseEvents());

        self::assertCount(1, $events);
        self::assertInstanceOf(PaymentInitialized::class, $events[0]);
        self::assertSame([], \iterator_to_array($payment->releaseEvents()));
    }

    public function testCompletingPaymentRecordsCompletionEvent(): void
    {
        $payment = Payment::initialize(
            PaymentId::generate(),
            GatewayEnum::PayU,
            Money::zero('USD'),
        );

        $payment->markSubmitted('order-id');
        $payment->markCompleted();

        self::assertSame(PaymentStatus::COMPLETED, $payment->status);
        $events = \iterator_to_array($payment->releaseEvents());

        self::assertInstanceOf(PaymentInitialized::class, $events[0]);
        self::assertInstanceOf(PaymentSubmitted::class, $events[1]);
        self::assertInstanceOf(PaymentCompleted::class, $events[2]);
    }

    public function testCannotCompletePaymentTwice(): void
    {
        $payment = Payment::initialize(
            PaymentId::generate(),
            GatewayEnum::PayU,
            Money::zero('USD'),
        );

        $payment->markCompleted();

        $this->expectException(PaymentAlreadyInStatus::class);

        $payment->markCompleted();
    }

    public function testCanSubmitOnlyWhenStateMachineAllowsTransitionToSubmitted(): void
    {
        $payment = Payment::initialize(
            PaymentId::generate(),
            GatewayEnum::PayU,
            Money::zero('USD'),
        );

        self::assertTrue($payment->canSubmit());

        $payment->markSubmitted('order-id');

        self::assertFalse($payment->canSubmit());

        $payment->markCompleted();

        self::assertFalse($payment->canSubmit());
    }
}
