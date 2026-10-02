<?php

declare(strict_types=1);

namespace PayWire\Core\Application;

use PayWire\Core\Domain\Payment\GatewayEnum;

final class GatewayNotRegistered extends \LogicException
{
    public static function for(GatewayEnum $gateway): self
    {
        return new self(\sprintf('Gateway "%s" is not registered.', $gateway->value));
    }
}
