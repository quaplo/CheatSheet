<?php

declare(strict_types=1);

namespace After\Support;

use After\Core\Order;
use After\Core\OrderItem;

/**
 * Podpůrná role: formátování pro člověka.
 *
 * Zná jádro, ale jádro nezná ji. Závislost vede jedním směrem.
 */
final class OrderFormatter
{
    public function formatTotal(Order $order): string
    {
        return number_format($order->totalInCents() / 100, 2, ',', ' ') . ' Kč';
    }

    public function formatSummary(Order $order): string
    {
        $lines = [sprintf('Objednávka %s (%s)', $order->number, $order->status()->value)];

        foreach ($order->items() as $item) {
            $lines[] = sprintf('  %s × %d', $item->sku, $item->quantity);
        }

        $lines[] = 'Celkem: ' . $this->formatTotal($order);

        return implode("\n", $lines);
    }
}
