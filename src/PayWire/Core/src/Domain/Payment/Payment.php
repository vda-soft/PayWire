<?php

declare(strict_types=1);

namespace PayWire\Core\Domain\Payment;

use PayWire\Core\Domain\Shared\Clock\ClockRegistry;
use PayWire\Core\Domain\Shared\Event\PublishedEvent;
use PayWire\Core\Domain\Shared\Money;
use PayWire\Core\Domain\Shared\SubmissionResult;

class Payment
{
    private const int MAX_LOGGED_EVENT_DATA_BYTES = 4096;

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
    /** @var list<array{type: class-string<\JsonSerializable>, timestamp: int, data: mixed}> */
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

        $occurredAt = ClockRegistry::get()->now();
        $this->createdAt = $occurredAt;
        $this->updatedAt = $occurredAt;

        $this->recordThat(new PaymentInitialized($this->paymentId, $this->gateway, $occurredAt));
    }

    public static function initialize(PaymentId $paymentId, GatewayEnum $gateway, Money $amount, OrderReference $order, CustomerReference $customer, string $description, ?string $posId = null): self
    {
        return new self($paymentId, $gateway, $amount, $order, $customer, $description, $posId);
    }

    public function canSubmit(): bool
    {
        return $this->canTransition(PaymentStatus::SUBMITTED);
    }

    public function markSubmitted(SubmissionResult $result): void
    {
        if (null !== $this->externalId) {
            return;
        }

        $this->transitionTo(PaymentStatus::SUBMITTED);
        $this->externalId = $result->externalId;

        $this->recordThat(new PaymentSubmitted($this->paymentId, $result->externalId, ClockRegistry::get()->now()));
        $this->logEvent($result);
    }

    public function markCompleted(): void
    {
        $this->transitionTo(PaymentStatus::COMPLETED);

        $this->recordThat(new PaymentCompleted($this->paymentId, ClockRegistry::get()->now()));
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
        $this->logEvent($event);
    }

    protected function transitionTo(PaymentStatus $status): void
    {
        if ($this->status !== $status) {
            $this->updatedAt = ClockRegistry::get()->now();
        }

        $this->status = PaymentStateMachine::transition($this->status, $status);
    }

    private function logEvent(\JsonSerializable $event): void
    {
        $eventData = $event->jsonSerialize();
        $serializedData = \json_encode($eventData, \JSON_THROW_ON_ERROR | \JSON_INVALID_UTF8_SUBSTITUTE);

        $isTruncated = \strlen($serializedData) > self::MAX_LOGGED_EVENT_DATA_BYTES;
        $data = $isTruncated ? ['preview' => \mb_strcut($serializedData, 0, self::MAX_LOGGED_EVENT_DATA_BYTES)] : $eventData;

        $this->eventLog[] = [
            'type' => $event::class,
            'timestamp' => ClockRegistry::get()->now()->getTimestamp(),
            'data' => $data,
            'truncated' => $isTruncated,
        ];
    }

    private function canTransition(PaymentStatus $status): bool
    {
        return PaymentStateMachine::canTransition($this->status, $status);
    }
}
