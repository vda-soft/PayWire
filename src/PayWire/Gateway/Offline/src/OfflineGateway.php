<?php

declare(strict_types=1);

namespace PayWire\Gateway\Offline;

use PayWire\Core\Domain\Payment\Payment;
use PayWire\Core\Domain\Shared\Gateway;
use PayWire\Core\Domain\Shared\SubmissionResult;

final class OfflineGateway implements Gateway
{
    public function submit(Payment $payment): SubmissionResult
    {
        throw new \LogicException('Offline gateway submission is not implemented.');
    }
}
