<?php

declare(strict_types=1);

namespace PayWire\Core\Domain\Shared\Clock;

/**
 * Global clock registry for time-sensitive operations.
 * Allows swapping the clock implementation for testing.
 */
final class ClockRegistry
{
    private static ?Clock $clock = null;

    private function __construct()
    {
    }

    public static function get(): Clock
    {
        return self::$clock ??= new SystemClock();
    }

    public static function set(Clock $clock): void
    {
        self::$clock = $clock;
    }

    public static function reset(): void
    {
        self::$clock = null;
    }
}
