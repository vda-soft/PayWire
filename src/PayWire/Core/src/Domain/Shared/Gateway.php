<?php

declare(strict_types=1);

namespace PayWire\Core\Domain\Shared;

use PayWire\Core\Domain\Payment\Payment;

interface Gateway
{
    public function submit(Payment $payment): SubmissionResult;
}
