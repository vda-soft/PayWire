<?php

declare(strict_types=1);

namespace PayWire\Core\Application\Command;

use PayWire\Core\Application\EventBusInterface;
use PayWire\Core\Application\GatewayResolver;
use PayWire\Core\Domain\Payment\PaymentRepository;

final class SubmitPaymentHandler
{
    public function __construct(
        private PaymentRepository $paymentRepository,
        private EventBusInterface $eventBus,
        private GatewayResolver $gatewayResolver,
    ) {
    }

    public function __invoke(SubmitPayment $command): void
    {
        $payment = $this->paymentRepository->findById($command->paymentId);
        if (!$payment->canSubmit()) {
            throw new \LogicException('Payment cannot be submitted.');
        }

        $response = $this->gatewayResolver->resolve($payment->gateway)
            ->submit($payment);

        $payment->markSubmitted($response->orderId);

        $this->paymentRepository->save($payment);
        $this->eventBus->commitAll($payment->releaseEvents());
    }
}
