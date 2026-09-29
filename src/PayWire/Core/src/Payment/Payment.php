<?php

namespace PayWire\Core\Payment;

use PayWire\Core\Shared\Infrastructure\Event\PublishedEvent;
use PayWire\Core\Shared\Money;

class Payment
{
    public private(set) readonly PaymentId $paymentId;
    public private(set) readonly GatewayEnum $gateway;
    public private(set) PaymentStatus $status = PaymentStatus::NEW;
    public private(set) readonly Money $amount;
    // external order identifier
    public private(set) string $orderId;

    /** @var PublishedEvent[] */
    private array $recordedEvents = [];

    private function __construct(
        PaymentId $paymentId,
        GatewayEnum $gateway,
        Money $amount,
    ) {
        $this->paymentId = $paymentId;
        $this->gateway = $gateway;
        $this->amount = $amount;

        $this->recordThat(new PaymentInitialized($this->paymentId, $this->gateway));
    }

    public static function initialize(PaymentId $paymentId, Money $amount, GatewayEnum $gateway): self
    {
        return new self($paymentId, $gateway, $amount);
    }

    /**
     * TODO use optimistic lock, submitted is only for information
     */
    public function markSubmitted(string $orderId): void
    {
        $this->transitionTo(PaymentStatus::SUBMITTED);
        $this->orderId = $orderId;

        $this->recordThat(new PaymentSubmitted($this->paymentId, $this->orderId));
    }

    public function markCompleted(): void
    {
        $this->transitionTo(PaymentStatus::COMPLETED);

        $this->recordThat(new PaymentCompleted($this->paymentId));
    }

    public function markCanceled(): void
    {
        $this->transitionTo(PaymentStatus::CANCELED);
    }

    public function markFailed(): void
    {
        $this->transitionTo(PaymentStatus::FAILED);
    }

    /** @return \Generator<PublishedEvent> */
    public function releaseEvents(): \Generator
    {
        while ([] !== $this->recordedEvents) {
            yield \array_shift($this->recordedEvents);
        }
    }

    protected function recordThat(PublishedEvent $event): void
    {
        $this->recordedEvents[] = $event;
    }

    protected function transitionTo(PaymentStatus $status): void
    {
        $this->status = PaymentStateMachine::transition($this->status, $status);
    }
}
