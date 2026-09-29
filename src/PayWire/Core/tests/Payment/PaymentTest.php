<?php

declare(strict_types=1);

namespace PayWire\Core\Tests\Payment;

use PayWire\Core\Payment\GatewayEnum;
use PayWire\Core\Payment\Payment;
use PayWire\Core\Payment\PaymentCompleted;
use PayWire\Core\Payment\PaymentId;
use PayWire\Core\Payment\PaymentInitialized;
use PayWire\Core\Payment\PaymentStatus;
use PayWire\Core\Payment\PaymentSubmitted;
use PayWire\Core\Shared\Money;
use PHPUnit\Framework\TestCase;

final class PaymentTest extends TestCase
{
    public function testInitializeRecordsEventAndReleaseClearsEvents(): void
    {
        $payment = Payment::initialize(
            PaymentId::generate(),
            Money::create('12.34', 'USD'),
            GatewayEnum::PayU,
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
            Money::zero('USD'),
            GatewayEnum::PayU,
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
            Money::zero('USD'),
            GatewayEnum::PayU,
        );

        $payment->markCompleted();

        $this->expectException(\PayWire\Core\Payment\PaymentAlreadyInStatus::class);

        $payment->markCompleted();
    }
}
