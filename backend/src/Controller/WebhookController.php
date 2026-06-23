<?php

namespace App\Controller;

use App\Message\MerchantNotification;
use App\Repository\TransactionRepository;
use App\Service\BalanceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

class WebhookController extends AbstractController
{
    #[Route('/api/webhook/payment', name: 'webhook_payment', methods: ['POST'])]
    public function payment(
        Request $request,
        TransactionRepository $transactions,
        BalanceService $balance,
        MessageBusInterface $bus,
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true) ?? [];

        $tx = $transactions->find($payload['transactionId'] ?? 0);
        if (null === $tx) {
            return new JsonResponse(['error' => 'unknown transaction'], 404);
        }

        $balance->applyPayment($tx);

        $bus->dispatch(new MerchantNotification(
            $tx->getMerchant()->getId(),
            sprintf('Payment settled for transaction %d', $tx->getId()),
        ));

        return new JsonResponse(['ok' => true]);
    }
}
