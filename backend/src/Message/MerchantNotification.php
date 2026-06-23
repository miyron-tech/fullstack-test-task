<?php

namespace App\Message;

final class MerchantNotification
{
    public function __construct(
        public readonly int $merchantId,
        public readonly string $message,
    ) {
    }
}
