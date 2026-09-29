<?php

namespace PayWire\Core\Domain\Payment;

final class InvalidPaymentStateTransition extends \LogicException
{
    public function __construct(PaymentStatus $from, PaymentStatus $to)
    {
        parent::__construct(
            \sprintf(
                'Cannot transition payment from "%s" to "%s".',
                $from->value,
                $to->value,
            )
        );
    }
}
