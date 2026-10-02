<?php

declare(strict_types=1);

namespace PayWire\Core\Domain\Shared\Event;

interface PublishedEvent extends \JsonSerializable
{
    public \DateTimeImmutable $occurredAt {
        get;
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array;
}
