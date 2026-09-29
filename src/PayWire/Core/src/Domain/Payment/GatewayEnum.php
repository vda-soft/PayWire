<?php

namespace PayWire\Core\Domain\Payment;

enum GatewayEnum: string
{
    case PayPal = 'PayPal';
    case PayPo = 'PayPo';
    case PayU = 'PayU';
    case Stripe = 'Stripe';
}
