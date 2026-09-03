<?php

declare(strict_types=1);

namespace Before;

/**
 * ANTI-PŘÍKLAD: jedna služba na všechno.
 *
 * Začne to třemi metodami a je to rozumné. Po roce má osm závislostí
 * a čtrnáct metod, přičemž **žádná z nich nepotřebuje víc než tři**.
 *
 * Důsledky, které to má:
 *   · každý use-case dostane i to, co nepoužije
 *   · test jedné metody musí namockovat všechno
 *   · nikdo neví, které metody se ještě používají
 *   · při každé nové operaci se sahá do souboru, který má 600 řádků
 */
final class OrderService
{
    public function __construct(
        private object $orders,
        private object $customers,
        private object $credit,
        private object $mailer,
        private object $stock,
        private object $invoices,
        private object $events,
        private object $audit,
    ) {
    }

    public function place(string $customerId, int $total): string { return ''; }
    public function cancel(string $orderId, string $reason): void { }
    public function ship(string $orderId, string $tracking): void { }
    public function refund(string $orderId, int $amount): void { }
    public function addItem(string $orderId, string $sku, int $qty): void { }
    public function removeItem(string $orderId, string $sku): void { }
    public function applyDiscount(string $orderId, int $percent): void { }
    public function recalculate(string $orderId): void { }
    public function exportForAccounting(string $orderId): array { return []; }
}
