<?php

declare(strict_types=1);

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
