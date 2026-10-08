<?php

declare(strict_types=1);

namespace PayWire\Core\Domain\Payment;

use PayWire\Core\Domain\Shared\Event\PublishedEvent;

final readonly class PaymentSubmitted implements PublishedEvent
{
    public function __construct(
        public PaymentId $paymentId,
        public string $externalId,
        public \DateTimeImmutable $occurredAt,
    ) {
    }

    /**
     * @return array{'paymentId': string, "externalId": string, "occurredAt": string}
     */
    public function jsonSerialize(): array
    {
        return [
            'paymentId' => (string) $this->paymentId,
            'externalId' => $this->externalId,
            'occurredAt' => $this->occurredAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
