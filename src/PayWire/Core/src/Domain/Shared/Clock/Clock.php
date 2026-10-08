<?php

declare(strict_types=1);

namespace PayWire\Core\Domain\Shared\Clock;

interface Clock
{
    public function now(): \DateTimeImmutable;

    public function nowWithMicroseconds(): \DateTimeImmutable;
}
