<?php

namespace App\Controller\Admin;

use App\Repository\TransactionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class TransactionController extends AbstractController
{
    #[Route('/api/admin/transactions', name: 'admin_transactions_list', methods: ['GET'])]
    public function list(TransactionRepository $transactions): JsonResponse
    {
        $rows = [];
        foreach ($transactions->findForListing() as $tx) {
            $feeDisplayed = number_format(floor((float) $tx->getAmount() * (float) $tx->getFeeRate()), 2, '.', '');

            $rows[] = [
                'id' => $tx->getId(),
                'merchantId' => $tx->getMerchant()->getId(),
                'merchantName' => $tx->getMerchant()->getName(),
                'amount' => $tx->getAmount(),
                'currency' => $tx->getCurrency(),
                'feeDisplayed' => $feeDisplayed,
                'status' => $tx->getStatus(),
                'createdAt' => $tx->getCreatedAt()->format(\DATE_ATOM),
                'refundedAmount' => $tx->getRefundedAmount(),
            ];
        }

        return new JsonResponse($rows);
    }
}
