<?php

declare(strict_types=1);

namespace PayWire\Core\Application\Command;

use PayWire\Core\Domain\Payment\PaymentId;

final readonly class SubmitPayment
{
    public function __construct(
        public PaymentId $paymentId,
    ) {
    }
}
