<?php

declare(strict_types=1);

namespace PayWire\Core\Tests\Domain\Shared\Clock;

use PayWire\Core\Domain\Shared\Clock\Clock;

/**
 * Fixed clock for testing - returns a fixed time.
 */
final readonly class FixedClock implements Clock
{
    public function __construct(
        private \DateTimeImmutable $fixedTime,
    ) {
    }

    public function now(): \DateTimeImmutable
    {
        return $this->fixedTime;
    }

    public function nowWithMicroseconds(): \DateTimeImmutable
    {
        return $this->fixedTime;
    }
}
