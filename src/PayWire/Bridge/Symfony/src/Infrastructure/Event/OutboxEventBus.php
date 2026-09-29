<?php

namespace PayWire\Bridge\Symfony\Infrastructure\Event;

use Doctrine\ORM\EntityManagerInterface;
use PayWire\Core\Application\EventBusInterface;
use PayWire\Core\Domain\Shared\Event\PublishedEvent;
use PayWire\Core\Infrastructure\OutboxMessage;

final class OutboxEventBus implements EventBusInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function commitAll(\Generator $releaseEvents): void
    {
        foreach ($releaseEvents as $releaseEvent) {
            $this->persistEvent($releaseEvent);
        }

        $this->entityManager->flush();
    }

    private function persistEvent(PublishedEvent $event): void
    {
        $outboxMessage = OutboxMessage::fromPublishedEvent($event);

        $this->entityManager->persist($outboxMessage);
    }
}
