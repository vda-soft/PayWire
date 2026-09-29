<?php

namespace PayWire\Core\Shared\Infrastructure;

use PayWire\Core\Shared\Infrastructure\Event\PublishedEvent;
use Symfony\Component\Uid\Uuid;

class OutboxMessage
{
    /**
     * @param array<string, mixed> $payload
     */
    private function __construct(
        public readonly string $id,
        public readonly string $eventClass,
        public readonly array $payload,
        public readonly \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
        public ?\DateTimeImmutable $publishedAt = null,
    ) {
    }

    public static function fromPublishedEvent(PublishedEvent $event): self
    {
        return new self(
            Uuid::v7(),
            \get_class($event),
            $event->jsonSerialize(),
        );
    }
}
