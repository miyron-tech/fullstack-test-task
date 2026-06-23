<?php

namespace App\MessageHandler;

use App\Message\MerchantNotification;
use App\Repository\MerchantRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

#[AsMessageHandler]
final class MerchantNotificationHandler
{
    public function __construct(
        private readonly MerchantRepository $merchants,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(MerchantNotification $message): void
    {
        $merchant = $this->merchants->find($message->merchantId);

        if (null === $merchant) {
            throw new UnrecoverableMessageHandlingException(sprintf(
                'Merchant %d not found; notification cannot be delivered.',
                $message->merchantId,
            ));
        }

        $this->logger->info(sprintf(
            'notify merchant %d: %s',
            $merchant->getId(),
            $message->message,
        ));
    }
}