<?php

declare(strict_types=1);

namespace PayWire\Core\Domain\Payment;

interface PaymentRepository
{
    public function save(Payment $payment): void;

    public function findById(PaymentId $paymentId): Payment;
}
