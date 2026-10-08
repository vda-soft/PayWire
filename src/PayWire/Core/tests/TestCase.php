<?php

declare(strict_types=1);

namespace PayWire\Core\Tests;

use PayWire\Core\Domain\Shared\Clock\Clock;
use PayWire\Core\Domain\Shared\Clock\ClockRegistry;
use PayWire\Core\Tests\Domain\Shared\Clock\FixedClock;
use PHPUnit\Framework\TestCase as PhpUnitTestCase;

abstract class TestCase extends PhpUnitTestCase
{
    public static function tearDownAfterClass(): void
    {
        ClockRegistry::reset();
        parent::tearDownAfterClass();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        ClockRegistry::reset();
    }

    protected function freezeTime(\DateTimeImmutable $fixedTime): Clock
    {
        $clock = new FixedClock($fixedTime);
        ClockRegistry::set($clock);

        return $clock;
    }
}
