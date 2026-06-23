<?php

namespace App\MessageHandler;

use App\Message\MerchantNotification;
use App\Repository\MerchantRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

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
        try {
            $merchant = $this->merchants->find($message->merchantId);
            $this->send($merchant, $message->message);
        } catch (\Throwable $e) {
        }
    }

    private function send(?object $merchant, string $text): void
    {
        if (null === $merchant) {
            throw new \RuntimeException('merchant not found');
        }

        $this->logger->info(sprintf('notify merchant %d: %s', $merchant->getId(), $text));
    }
}
