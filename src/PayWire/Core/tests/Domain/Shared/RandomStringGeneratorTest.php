<?php

declare(strict_types=1);

namespace PayWire\Core\Tests\Domain\Shared;

use PayWire\Core\Domain\Payment\UrlTokenGenerator;
use PHPUnit\Framework\TestCase;

final class RandomStringGeneratorTest extends TestCase
{
    public function testGeneratesUrlSafeToken(): void
    {
        $value = UrlTokenGenerator::generate();

        self::assertSame(43, \strlen($value));
        self::assertMatchesRegularExpression('/\A[A-Za-z0-9_-]+\z/', $value);
    }
}
