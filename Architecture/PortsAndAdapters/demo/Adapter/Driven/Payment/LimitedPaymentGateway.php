<?php

declare(strict_types=1);

namespace Adapter\Driven\Payment;

use Core\Port\Driven\PaymentGateway;
use Core\Port\Driven\PaymentResult;

/**
 * Řízený adaptér zastupující skutečnou bránu s limitem.
 *
 * V reálu by tady bylo volání HTTP API, parsování odpovědi a překlad chyb.
 * Podstatné je, že cizí pojmy (HTTP kódy, error kódy poskytovatele) končí
 * tady a ven jde PaymentResult, kterému rozumí jádro.
 */
final class LimitedPaymentGateway implements PaymentGateway
{
    public function __construct(
        private readonly int $limitInCents,
    ) {
    }

    public function charge(string $orderNumber, int $amountInCents): PaymentResult
    {
        if ($amountInCents > $this->limitInCents) {
            // Sem by patřil překlad chyby poskytovatele do pojmů jádra.
            return PaymentResult::declined(sprintf(
                'Částka %s Kč překračuje limit karty %s Kč.',
                number_format($amountInCents / 100, 0, ',', ' '),
                number_format($this->limitInCents / 100, 0, ',', ' '),
            ));
        }

        return PaymentResult::approved('PAY-' . strtoupper(substr(md5($orderNumber), 0, 8)));
    }
}
