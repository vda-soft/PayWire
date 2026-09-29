<?php

namespace PayWire\Bridge\Symfony\Infrastructure\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use PayWire\Core\Domain\Payment\Payment;
use PayWire\Core\Domain\Payment\PaymentRepositoryInterface;

final class DoctrinePaymentRepository implements PaymentRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(Payment $payment): void
    {
        $this->entityManager->persist($payment);
    }
}
