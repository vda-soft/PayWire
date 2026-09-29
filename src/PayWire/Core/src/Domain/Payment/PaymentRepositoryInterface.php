<?php

namespace PayWire\Core\Domain\Payment;

interface PaymentRepositoryInterface
{
    public function save(Payment $payment): void;
}
