<?php

declare(strict_types=1);

namespace PayWire\Core\Application\Command;

use PayWire\Core\Domain\Payment\GatewayEnum;
use PayWire\Core\Domain\Payment\PaymentId;

final readonly class InitializePayment
{
    public PaymentId $paymentId;

    public function __construct(
        public string $amount,
        public string $currency,
        public GatewayEnum $gateway,
    ) {
        $this->paymentId = PaymentId::generate();
    }
}
