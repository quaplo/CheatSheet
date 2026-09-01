<?php

declare(strict_types=1);

namespace Composition;

use Contexts\BillingContext;
use Contexts\SalesContext;
use Contexts\ShippingContext;

/**
 * ČTECÍ kompozice — ta bezpečná polovina tohohle patternu.
 *
 * Poskládá pohled ze tří kontextů. Nic nemění, takže při výpadku
 * jednoho zdroje nevzniká nekonzistence — jen chybí kus obrazovky.
 *
 * Klíčové rozhodnutí je tady: co je POVINNÉ a co jen doplňkové.
 * Bez objednávky nemá pohled smysl; bez sledovací zásilky ano.
 */
final readonly class OrderDetailComposer
{
    public function __construct(
        private SalesContext $sales,
        private BillingContext $billing,
        private ShippingContext $shipping,
    ) {
    }

    public function compose(string $orderId): OrderDetailView
    {
        $unavailable = [];

        // Povinná část — bez ní odpověď nedává smysl.
        $order = $this->sales->orderSummary($orderId);

        // Doplňkové části — výpadek degraduje pohled, neshodí ho.
        $invoice = $this->optional('Billing', fn (): array => $this->billing->invoiceFor($orderId), $unavailable);
        $tracking = $this->optional('Shipping', fn (): array => $this->shipping->trackingFor($orderId), $unavailable);

        return new OrderDetailView($order, $invoice, $tracking, $unavailable);
    }

    /**
     * @param callable(): array<string, mixed> $call
     * @param list<string>                     $unavailable
     *
     * @return array<string, mixed>|null
     */
    private function optional(string $context, callable $call, array &$unavailable): ?array
    {
        try {
            return $call();
        } catch (\RuntimeException) {
            $unavailable[] = $context;

            return null;
        }
    }
}
