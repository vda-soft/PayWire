<?php

declare(strict_types=1);

namespace PayWire\Core\Domain\Payment;

use PayWire\Core\Domain\Shared\Event\PublishedEvent;
use PayWire\Core\Domain\Shared\Money;

class Payment
{
    public private(set) readonly PaymentId $paymentId;
    public private(set) readonly GatewayEnum $gateway;
    public private(set) readonly ?string $posId;
    public private(set) PaymentStatus $status = PaymentStatus::NEW;
    public private(set) readonly Money $amount;
    // external order identifier
    public private(set) ?string $orderId = null;
    public private(set) readonly string $publicToken;

    /** @var PublishedEvent[] */
    private array $recordedEvents = [];

    private function __construct(
        PaymentId $paymentId,
        GatewayEnum $gateway,
        Money $amount,
        ?string $posId = null,
    ) {
        $this->paymentId = $paymentId;
        $this->gateway = $gateway;
        $this->amount = $amount;
        $this->publicToken = UrlTokenGenerator::get();
        $this->posId = $posId;

        $this->recordThat(new PaymentInitialized($this->paymentId, $this->gateway));
    }

    public static function initialize(PaymentId $paymentId, GatewayEnum $gateway, Money $amount): self
    {
        return new self($paymentId, $gateway, $amount);
    }

    public function canSubmit(): bool
    {
        return $this->canTransition(PaymentStatus::SUBMITTED);
    }

    public function markSubmitted(string $orderId): void
    {
        if (null !== $this->orderId) {
            return;
        }

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

    private function canTransition(PaymentStatus $status): bool
    {
        return PaymentStateMachine::canTransition($this->status, $status);
    }

    protected function transitionTo(PaymentStatus $status): void
    {
        $this->status = PaymentStateMachine::transition($this->status, $status);
    }
}
