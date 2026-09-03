<?php

declare(strict_types=1);

namespace Core\Port\Driven;

/**
 * Řízený (secondary) port — jádro si ho definuje a volá ho.
 *
 * Kontrakt vlastní jádro, ne infrastruktura. Proto je tenhle soubor tady
 * a ne u adaptéru: jádro říká, co potřebuje, a okolní svět se přizpůsobí.
 */
interface PaymentGateway
{
    public function charge(string $orderNumber, int $amountInCents): PaymentResult;
}
