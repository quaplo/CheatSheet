<?php

declare(strict_types=1);

namespace Composition;

use Contexts\BillingContext;
use Contexts\SalesContext;
use Contexts\ShippingContext;

/**
 * ZÁPISOVÁ kompozice — a tady je ta past.
 *
 * Vypadá to stejně jako čtecí verze, jen se místo dotazů volají
 * operace, které něco mění. Rozdíl je zásadní: když třetí krok
 * selže, první dva už PROBĚHLY a nikdo je nevrátí.
 *
 * Tenhle kód není ukázka „jak na to“. Je to ukázka toho, proč
 * existuje Saga.
 */
final readonly class PlaceOrderComposer
{
    public function __construct(
        private SalesContext $sales,
        private BillingContext $billing,
        private ShippingContext $shipping,
    ) {
    }

    /** @return array{orderId: string, invoice: string, tracking: string} */
    public function place(string $customerId, int $totalInCents): array
    {
        $orderId = $this->sales->placeOrder($customerId, $totalInCents);   // 1. proběhne

        $invoice = $this->billing->issueInvoice($orderId, $totalInCents);  // 2. proběhne

        $tracking = $this->shipping->scheduleDelivery($orderId);           // 3. spadne

        // …a sem se už nedostaneme. Objednávka existuje, faktura je
        // vystavená, zásilka není naplánovaná — a nikdo o tom neví.

        return ['orderId' => $orderId, 'invoice' => $invoice, 'tracking' => $tracking];
    }
}
