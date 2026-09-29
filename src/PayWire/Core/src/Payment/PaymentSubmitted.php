<?php

namespace PayWire\Core\Payment;

use PayWire\Core\Shared\Infrastructure\Event\PublishedEvent;

final readonly class PaymentSubmitted implements PublishedEvent
{
    public function __construct(
        public PaymentId $paymentId,
        public string $orderId,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    /**
     * @return array{'paymentId': string, "orderId": string, "occurredAt": string}
     */
    public function jsonSerialize(): array
    {
        return [
            'paymentId' => (string) $this->paymentId,
            'orderId' => $this->orderId,
            'occurredAt' => $this->occurredAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
