<?php

namespace PayWire\Core\Payment;

interface PaymentRepositoryInterface
{
    public function save(Payment $payment): void;
}
