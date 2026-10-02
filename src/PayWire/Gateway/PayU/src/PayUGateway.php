<?php

declare(strict_types=1);

namespace PayWire\Gateway\PayU;

use PayWire\Core\Domain\Payment\Payment;
use PayWire\Core\Domain\Shared\Gateway;
use PayWire\Core\Domain\Shared\SubmissionResult;

final class PayUGateway implements Gateway
{
    public function submit(Payment $payment): SubmissionResult
    {
        throw new \LogicException('PayU gateway submission is not implemented.');
    }
}
