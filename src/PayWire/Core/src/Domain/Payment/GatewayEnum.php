<?php

declare(strict_types=1);

namespace PayWire\Core\Domain\Payment;

enum GatewayEnum: string
{
    case PayPal = 'PayPal';
    case PayPo = 'PayPo';
    case PayU = 'PayU';
    case Stripe = 'Stripe';
}
