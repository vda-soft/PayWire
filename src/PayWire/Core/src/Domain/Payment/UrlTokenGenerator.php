<?php

declare(strict_types=1);

namespace PayWire\Core\Domain\Payment;

final class UrlTokenGenerator
{
    private function __construct()
    {
    }

    public static function generate(): string
    {
        return \rtrim(\strtr(\base64_encode(\random_bytes(32)), '+/', '-_'), '=');
    }
}
