<?php

namespace PayWire\Core\Application\Command;

use PayWire\Core\Domain\Payment\GatewayEnum;

final readonly class InitializePayment
{
    public function __construct(
        public string $amount,
        public string $currency,
        public GatewayEnum $gateway,
    ) {
    }
}
