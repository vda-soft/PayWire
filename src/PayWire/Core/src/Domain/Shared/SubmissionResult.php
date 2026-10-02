<?php

declare(strict_types=1);

namespace PayWire\Core\Domain\Shared;

final readonly class SubmissionResult
{
    public function __construct(
        public string $orderId,
    ) {
    }
}
