<?php

declare(strict_types=1);

namespace PayWire\Core\Application\Command;

use PayWire\Core\Application\EventBusInterface;
use PayWire\Core\Domain\Payment\Payment;
use PayWire\Core\Domain\Payment\PaymentRepository;

class InitializePaymentHandler
{
    public function __construct(
        private PaymentRepository $paymentRepository,
        private EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(InitializePayment $command): void
    {
        $payment = Payment::initialize(
            $command->paymentId,
            $command->gateway,
            $command->total,
            $command->order,
            $command->customer,
            $command->description,
            $command->posId
        );

        $this->paymentRepository->save($payment);
        $this->eventBus->commitAll($payment->releaseEvents());
    }
}
