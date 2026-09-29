<?php

declare(strict_types=1);

namespace PayWire\Gateway\PayU\Tests;

use PayWire\Gateway\PayU\PayUGateway;
use PHPUnit\Framework\TestCase;

final class PayUGatewayTest extends TestCase
{
    public function testGatewayCanBeInstantiated(): void
    {
        self::assertInstanceOf(PayUGateway::class, new PayUGateway());
    }
}
