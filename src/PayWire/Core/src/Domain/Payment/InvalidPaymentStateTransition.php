<?php

declare(strict_types=1);

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
