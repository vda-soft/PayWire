<?php

declare(strict_types=1);

namespace PayWire\Core\Domain\Payment;

use PayWire\Core\Domain\Shared\Event\PublishedEvent;
use PayWire\Core\Domain\Shared\Money;

class Payment
{
    public private(set) readonly PaymentId $paymentId;
    public private(set) ?string $externalId = null;
    public private(set) readonly GatewayEnum $gateway;
    public private(set) readonly ?string $posId;
    public private(set) PaymentStatus $status = PaymentStatus::NEW;
    public private(set) readonly Money $total;
    public private(set) readonly OrderReference $order;
    public private(set) readonly CustomerReference $customer;
    public private(set) readonly string $description;
    public private(set) readonly string $publicToken;

    public private(set) ?Details $details = null;
    /** @var \JsonSerializable[] */
    public private(set) array $eventLog = [];

    public private(set) \DateTimeImmutable $createdAt;
    public private(set) \DateTimeImmutable $updatedAt;

    /** @var PublishedEvent[] */
    private array $recordedEvents = [];

    private function __construct(
        PaymentId $paymentId,
        GatewayEnum $gateway,
        Money $total,
        OrderReference $order,
        CustomerReference $customer,
        string $description,
        ?string $posId = null,
    ) {
        $this->paymentId = $paymentId;
        $this->gateway = $gateway;
        $this->total = $total;
        $this->order = $order;
        $this->customer = $customer;
        $this->description = $description;
        $this->publicToken = UrlTokenGenerator::generate();
        $this->posId = $posId;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordThat(new PaymentInitialized($this->paymentId, $this->gateway));
    }

    public static function initialize(PaymentId $paymentId, GatewayEnum $gateway, Money $amount, OrderReference $order, CustomerReference $customer, string $description, ?string $posId = null): self
    {
        return new self($paymentId, $gateway, $amount, $order, $customer, $description, $posId);
    }

    public function canSubmit(): bool
    {
        return $this->canTransition(PaymentStatus::SUBMITTED);
    }

    public function markSubmitted(string $externalId): void
    {
        if (null !== $this->externalId) {
            return;
        }

        $this->transitionTo(PaymentStatus::SUBMITTED);
        $this->externalId = $externalId;

        $this->recordThat(new PaymentSubmitted($this->paymentId, $this->externalId));
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
        $this->eventLog[$this->now()->getTimestamp()] = $event;
    }

    private function canTransition(PaymentStatus $status): bool
    {
        return PaymentStateMachine::canTransition($this->status, $status);
    }

    protected function transitionTo(PaymentStatus $status): void
    {
        if ($this->status !== $status) {
            $this->updatedAt = $this->now();
        }

        $this->status = PaymentStateMachine::transition($this->status, $status);
    }

    private function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
