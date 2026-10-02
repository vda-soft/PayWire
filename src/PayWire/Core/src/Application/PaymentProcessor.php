<?php

declare(strict_types=1);

namespace PayWire\Core\Application;

use PayWire\Core\Application\Command\InitializePayment;
use PayWire\Core\Application\Command\InitializePaymentHandler;
use PayWire\Core\Application\Command\SubmitPayment;
use PayWire\Core\Application\Command\SubmitPaymentHandler;
use PayWire\Core\Domain\Payment\Payment;
use PayWire\Core\Domain\Payment\PaymentId;
use PayWire\Core\Domain\Payment\PaymentRepository;

class PaymentProcessor
{
    public function __construct(
        private PaymentRepository $paymentRepository,
        private InitializePaymentHandler $initializeHandler,
        private SubmitPaymentHandler $submitHandler,
    ) {
    }

    public function prepareAndSubmit(InitializePayment $command): Payment
    {
        $payment = $this->initialize($command);

        return $this->submit($payment->paymentId);
    }

    public function initialize(InitializePayment $command): Payment
    {
        ($this->initializeHandler)($command);

        return $this->paymentRepository->findById($command->paymentId);
    }

    public function submit(PaymentId $paymentId): Payment
    {
        ($this->submitHandler)(new SubmitPayment($paymentId));

        return $this->paymentRepository->findById($paymentId);
    }
}
