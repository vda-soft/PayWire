<?php

declare(strict_types=1);

namespace PayWire\Core\Domain\Shared;

final readonly class SubmissionResult implements \JsonSerializable
{
    /** @param string|array<array-key, mixed> $rawResponse */
    public function __construct(
        public string $externalId,
        public string|array $rawResponse,
    ) {
    }

    /** @return array{'externalId': string, 'rawResponse': string|array<array-key, mixed>} */
    public function jsonSerialize(): array
    {
        return [
            'externalId' => $this->externalId,
            'rawResponse' => $this->rawResponse,
        ];
    }
}
