<?php

declare(strict_types=1);

namespace Composition;

/**
 * Složený pohled — tvar obrazovky, ne doménový objekt.
 *
 * Nese kus ze tří kontextů, a hlavně informaci o tom, které části
 * chybí. To je u čtecí kompozice zásadní: obrazovka se má umět
 * ukázat i tehdy, když jeden zdroj mlčí.
 */
final readonly class OrderDetailView
{
    /**
     * @param array{orderId: string, customer: string, totalInCents: int, placedAt: string}|null $order
     * @param array{invoiceNumber: string, dueDate: string, isPaid: bool}|null                   $invoice
     * @param array{trackingNumber: string, carrier: string, estimatedAt: string}|null           $tracking
     * @param list<string>                                                                       $unavailable
     */
    public function __construct(
        public ?array $order,
        public ?array $invoice,
        public ?array $tracking,
        public array $unavailable,
    ) {
    }

    public function isComplete(): bool
    {
        return $this->unavailable === [];
    }
}
