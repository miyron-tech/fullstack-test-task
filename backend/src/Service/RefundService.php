<?php

namespace App\Service;

use App\Entity\Refund;
use App\Entity\Transaction;
use App\Message\MerchantNotification;
use App\Repository\RefundRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class RefundService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ProviderClient $provider,
        private readonly MessageBusInterface $bus,
        private readonly RefundRepository $refunds,
    ) {
    }

    public function refund(
        Transaction $tx,
        string $amount,
        ?string $reason,
        string $idempotencyKey,
    ): Refund {
        $existing = $this->refunds->findOneBy(['idempotencyKey' => $idempotencyKey]);
        if (null !== $existing) {
            return $existing;
        }

        $refund = null;

        try {
            $this->em->wrapInTransaction(function () use ($tx, $amount, $reason, $idempotencyKey, &$refund): void {
                $tx = $this->em->find(Transaction::class, $tx->getId(), LockMode::PESSIMISTIC_WRITE);

                $this->assertRefundable($tx, $amount);

                $externalId = $tx->getExternalId() ?? (string) $tx->getId();
                $result = $this->provider->refund($externalId, $amount, $tx->getCurrency());

                $refund = new Refund();
                $refund->setTransaction($tx);
                $refund->setAmount($amount);
                $refund->setReason($reason);
                $refund->setIdempotencyKey($idempotencyKey);
                $refund->setProviderRefundId($result['providerRefundId'] ?? null);

                $newRefunded = bcadd($tx->getRefundedAmount(), $amount, 2);
                $tx->setRefundedAmount($newRefunded);

                if (bccomp($newRefunded, $tx->getAmount(), 2) >= 0) {
                    $tx->setStatus('refunded');
                } else {
                    $tx->setStatus('partial_refund');
                }

                $this->em->persist($refund);
                $this->em->flush();
            });
        } catch (UniqueConstraintViolationException) {
            $existing = $this->refunds->findOneBy(['idempotencyKey' => $idempotencyKey]);
            if (null !== $existing) {
                return $existing;
            }
            throw new \RuntimeException('Duplicate idempotency key, but refund record not found. Please retry.');
        }

        $this->bus->dispatch(new MerchantNotification(
            $tx->getMerchant()->getId(),
            sprintf(
                'Refund of %s %s has been processed for transaction #%d (provider refund: %s)',
                $amount,
                $tx->getCurrency(),
                $tx->getId(),
                $refund->getProviderRefundId() ?? 'n/a',
            ),
        ));

        return $refund;
    }

    private function assertRefundable(Transaction $tx, string $amount): void
    {
        $allowedStatuses = ['paid', 'settled', 'partial_refund'];
        if (!\in_array($tx->getStatus(), $allowedStatuses, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot refund a transaction with status "%s".',
                $tx->getStatus(),
            ));
        }

        if (bccomp($amount, '0.00', 2) <= 0) {
            throw new \InvalidArgumentException('Refund amount must be greater than zero.');
        }

        $refundable = bcsub($tx->getAmount(), $tx->getRefundedAmount(), 2);
        if (bccomp($amount, $refundable, 2) > 0) {
            throw new \InvalidArgumentException(sprintf(
                'Refund amount %s exceeds the refundable amount %s.',
                $amount,
                $refundable,
            ));
        }
    }
}