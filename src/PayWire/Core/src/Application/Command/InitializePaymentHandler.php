<?php

namespace PayWire\Core\Application\Command;

use PayWire\Core\Application\EventBusInterface;
use PayWire\Core\Domain\Payment\Payment;
use PayWire\Core\Domain\Payment\PaymentId;
use PayWire\Core\Domain\Payment\PaymentRepositoryInterface;
use PayWire\Core\Domain\Shared\Money;

class InitializePaymentHandler
{
    public function __construct(
        private PaymentRepositoryInterface $repository,
        private EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(InitializePayment $command): void
    {
        $payment = Payment::initialize(
            PaymentId::generate(),
            Money::create($command->amount, $command->currency),
            $command->gateway
        );

        $this->repository->save($payment);

        $this->eventBus->commitAll($payment->releaseEvents());
    }
}
