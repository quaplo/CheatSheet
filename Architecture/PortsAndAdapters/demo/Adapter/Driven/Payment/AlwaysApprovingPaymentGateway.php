<?php

declare(strict_types=1);

namespace Adapter\Driven\Payment;

use Core\Port\Driven\PaymentGateway;
use Core\Port\Driven\PaymentResult;

/** Řízený adaptér pro testy — platba projde vždy. */
final class AlwaysApprovingPaymentGateway implements PaymentGateway
{
    public function charge(string $orderNumber, int $amountInCents): PaymentResult
    {
        return PaymentResult::approved('TEST-' . $orderNumber);
    }
}
