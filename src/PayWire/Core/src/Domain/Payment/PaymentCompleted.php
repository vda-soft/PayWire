<?php

namespace PayWire\Core\Domain\Payment;

use PayWire\Core\Domain\Shared\Event\PublishedEvent;

final readonly class PaymentCompleted implements PublishedEvent
{
    public function __construct(
        public PaymentId $paymentId,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    /**
     * @return array{'paymentId': string, "occurredAt": string}
     */
    public function jsonSerialize(): array
    {
        return [
            'paymentId' => (string) $this->paymentId,
            'occurredAt' => $this->occurredAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
