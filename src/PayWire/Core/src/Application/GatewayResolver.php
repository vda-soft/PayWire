<?php

declare(strict_types=1);

namespace PayWire\Core\Application;

use PayWire\Core\Domain\Payment\GatewayEnum;
use PayWire\Core\Domain\Shared\Gateway;

interface GatewayResolver
{
    public function resolve(GatewayEnum $gateway): Gateway;
}
