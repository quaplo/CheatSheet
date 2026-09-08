<?php

declare(strict_types=1);

/**
 * Snímek objednávky.
 *
 * Vzniká i zaniká uvnitř Order. Pole jsou veřejná a readonly —
 * PHP nemá „friend" ani package-private, takže zapouzdření tu
 * nedrží modifikátor, ale typ, kterým snímek putuje ven (Memento).
 */
final readonly class OrderMemento implements Memento
{
    /** @param list<OrderItem> $items */
    public function __construct(
        public array $items,
        public string $status,
        public int $discountPercent,
    ) {
    }
}
