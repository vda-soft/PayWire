<?php

declare(strict_types=1);

namespace PayWire\Bridge\Symfony\Infrastructure\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PayWire\Core\Domain\Payment\Payment;
use PayWire\Core\Domain\Payment\PaymentId;
use PayWire\Core\Domain\Payment\PaymentRepository;

final class DoctrinePaymentRepository implements PaymentRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(Payment $payment): void
    {
        $this->entityManager->persist($payment);
    }

    public function findById(PaymentId $paymentId): Payment
    {
        return $this->getEntityRepository()->find($paymentId)
            ?? throw new \RuntimeException(\sprintf('Payment "%s" was not found.', $paymentId));
    }

    /** @return EntityRepository<Payment> */
    private function getEntityRepository(): EntityRepository
    {
        return $this->entityManager->getRepository(Payment::class);
    }
}
