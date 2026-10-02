<?php

declare(strict_types=1);

namespace PayWire\Core\Domain\Payment;

use Symfony\Component\Uid\Uuid;

final readonly class PaymentId implements \Stringable
{
    private function __construct(public private(set) Uuid $id)
    {
    }

    public static function generate(): self
    {
        return new self(Uuid::v7());
    }

    public static function fromString(string $id): self
    {
        return new self(Uuid::fromString($id));
    }

    public function __toString(): string
    {
        return $this->id->__toString();
    }
}
