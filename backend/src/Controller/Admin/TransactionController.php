<?php

namespace App\Controller\Admin;

use App\Entity\Transaction;
use App\Repository\TransactionRepository;
use App\Service\RefundService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class TransactionController extends AbstractController
{
    /** Positive decimal with up to 2 fraction digits, e.g. "10" or "10.00". */
    private const AMOUNT_PATTERN = '/^\d{1,12}(\.\d{1,2})?$/';

    #[Route('/api/admin/transactions', name: 'admin_transactions_list', methods: ['GET'])]
    public function list(TransactionRepository $transactions): JsonResponse
    {
        $rows = array_map(
            fn (Transaction $tx): array => $this->serialize($tx),
            $transactions->findForListing(),
        );

        return new JsonResponse($rows);
    }

    #[Route('/api/admin/transactions/{id}/refund', name: 'admin_transactions_refund', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function refund(
        int $id,
        Request $request,
        TransactionRepository $transactions,
        RefundService $refundService,
    ): JsonResponse {
        // Idempotency-Key is the contract that lets the client safely retry a refund
        $idempotencyKey = trim((string) $request->headers->get('Idempotency-Key', ''));
        if ('' === $idempotencyKey) {
            return new JsonResponse(['error' => 'Missing Idempotency-Key header'], 400);
        }
        if (\strlen($idempotencyKey) > 64) {
            return new JsonResponse(['error' => 'Idempotency-Key must be at most 64 characters'], 400);
        }

        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return new JsonResponse(['error' => 'Invalid JSON body'], 400);
        }

        $amount = isset($data['amount']) ? trim((string) $data['amount']) : '';//TODO:
        if (1 !== preg_match(self::AMOUNT_PATTERN, $amount)) {
            return new JsonResponse(
                ['error' => 'Field "amount" must be a positive decimal string, e.g. "10.00"'],
                422,
            );
        }

        $reason = null;
        if (isset($data['reason']) && '' !== trim((string) $data['reason'])) {
            $reason = mb_substr(trim((string) $data['reason']), 0, 1000);
        }

        $tx = $transactions->find($id);
        if (null === $tx) {
            return new JsonResponse(['error' => 'Transaction not found'], 404);
        }

        try {
            $refund = $refundService->refund($tx, $amount, $reason, $idempotencyKey);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 409);
        }

        return new JsonResponse([
            'refundId' => $refund->getId(),
            'status' => 'ok',
            'transactionStatus' => $tx->getStatus(),
            'refundedAmount' => $tx->getRefundedAmount(),
            'providerRefundId' => $refund->getProviderRefundId(),
        ], 201);
    }

    private function serialize(Transaction $tx): array
    {
        return [
            'id' => $tx->getId(),
            'merchantId' => $tx->getMerchant()->getId(),
            'merchantName' => $tx->getMerchant()->getName(),
            'amount' => $tx->getAmount(),
            'currency' => $tx->getCurrency(),
            'feeDisplayed' => $tx->getFee(),
            'status' => $tx->getStatus(),
            'createdAt' => $tx->getCreatedAt()->format(\DATE_ATOM),
            'refundedAmount' => $tx->getRefundedAmount(),
        ];
    }
}