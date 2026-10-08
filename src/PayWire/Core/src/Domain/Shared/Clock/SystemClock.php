<?php

declare(strict_types=1);

namespace PayWire\Core\Domain\Shared\Clock;

final readonly class SystemClock implements Clock
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }

    public function nowWithMicroseconds(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromFormat('U.u', (string) \microtime(true)) ?: new \DateTimeImmutable();
    }
}
