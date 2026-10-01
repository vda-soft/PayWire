<?php

declare(strict_types=1);

namespace PayWire\Gateway\Offline\Tests;

use PayWire\Gateway\Offline\OfflineGateway;
use PHPUnit\Framework\TestCase;

final class OfflineGatewayTest extends TestCase
{
    public function testGatewayCanBeInstantiated(): void
    {
        self::assertInstanceOf(OfflineGateway::class, new OfflineGateway());
    }
}
