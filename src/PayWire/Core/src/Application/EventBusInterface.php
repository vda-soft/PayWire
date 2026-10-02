<?php

declare(strict_types=1);

namespace PayWire\Core\Application;

use PayWire\Core\Domain\Shared\Event\PublishedEvent;

interface EventBusInterface
{
    /** @param \Generator<mixed, PublishedEvent> $releaseEvents */
    public function commitAll(\Generator $releaseEvents): void;
}
