<?php

declare(strict_types=1);

namespace PayWire\Core\Domain\Payment;

use PayWire\Core\Domain\Shared\Event\PublishedEvent;

final readonly class PaymentInitialized implements PublishedEvent
{
    public function __construct(
        public PaymentId $paymentId,
        public GatewayEnum $gateway,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    /**
     * @return array{'paymentId': string, "gateway": string, "occurredAt": string}
     */
    public function jsonSerialize(): array
    {
        return [
            'paymentId' => (string) $this->paymentId,
            'gateway' => $this->gateway->value,
            'occurredAt' => $this->occurredAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
