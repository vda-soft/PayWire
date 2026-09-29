<?php

namespace PayWire\Core\Payment;

final class PaymentStateMachine
{
    /** @var array<value-of<PaymentStatus>, array<PaymentStatus>> */
    private const array TRANSITIONS = [
        PaymentStatus::NEW->value => [
            PaymentStatus::SUBMITTED,
            PaymentStatus::COMPLETED,
            PaymentStatus::CANCELED,
            PaymentStatus::FAILED,
        ],
        PaymentStatus::SUBMITTED->value => [
            PaymentStatus::COMPLETED,
            PaymentStatus::CANCELED,
            PaymentStatus::FAILED,
        ],
        PaymentStatus::COMPLETED->value => [],
        PaymentStatus::CANCELED->value => [],
        PaymentStatus::FAILED->value => [],
    ];

    public static function transition(PaymentStatus $from, PaymentStatus $to): PaymentStatus
    {
        if ($from === $to) {
            throw new PaymentAlreadyInStatus($to);
        }

        if (!\in_array($to, self::TRANSITIONS[$from->value], true)) {
            throw new InvalidPaymentStateTransition($from, $to);
        }

        return $to;
    }
}
