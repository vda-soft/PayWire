<?php

namespace PayWire\Core\Application;

use PayWire\Core\Shared\Infrastructure\Event\PublishedEvent;

interface EventBusInterface
{
    /** @param \Generator<mixed, PublishedEvent> $releaseEvents */
    public function commitAll(\Generator $releaseEvents): void;
}
