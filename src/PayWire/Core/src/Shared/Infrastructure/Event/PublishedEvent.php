<?php

namespace PayWire\Core\Shared\Infrastructure\Event;

interface PublishedEvent extends \JsonSerializable
{
    public \DateTimeImmutable $occurredAt {
        get;
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array;
}
