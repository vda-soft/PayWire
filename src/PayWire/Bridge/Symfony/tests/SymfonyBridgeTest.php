<?php

declare(strict_types=1);

namespace PayWire\Bridge\Symfony\Tests;

use PayWire\Bridge\Symfony\SymfonyBridge;
use PHPUnit\Framework\TestCase;

final class SymfonyBridgeTest extends TestCase
{
    public function testBridgeCanBeInstantiated(): void
    {
        self::assertInstanceOf(SymfonyBridge::class, new SymfonyBridge());
    }
}
