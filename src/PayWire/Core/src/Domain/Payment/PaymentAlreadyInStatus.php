<?php

namespace PayWire\Core\Domain\Payment;

final class PaymentAlreadyInStatus extends \LogicException
{
    public function __construct(PaymentStatus $status)
    {
        parent::__construct(
            \sprintf(
                'Payment is already in status "%s".',
                $status->value,
            )
        );
    }
}
