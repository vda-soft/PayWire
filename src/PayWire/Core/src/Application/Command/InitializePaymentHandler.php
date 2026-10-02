<?php

declare(strict_types=1);

namespace PayWire\Core\Application\Command;

use PayWire\Core\Application\EventBusInterface;
use PayWire\Core\Domain\Payment\Payment;
use PayWire\Core\Domain\Payment\PaymentRepository;
use PayWire\Core\Domain\Shared\Money;

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
            Money::create($command->amount, $command->currency),
        );

        $this->paymentRepository->save($payment);
        $this->eventBus->commitAll($payment->releaseEvents());
    }
}
