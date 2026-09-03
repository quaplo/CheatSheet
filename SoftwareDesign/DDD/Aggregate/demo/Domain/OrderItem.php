<?php

declare(strict_types=1);

namespace Domain;

/**
 * VNITŘNÍ ENTITA agregátu.
 *
 * Má identitu (uvnitř objednávky), mění se v čase — je to tedy entita.
 * Ale **nemá vlastní repository** a zvenčí se k ní nedostaneš jinak než
 * přes kořen. Její životní cyklus je svázaný s objednávkou.
 *
 * Konstruktor je proto `internal` v duchu, ne technicky: volá ho jen
 * Order. PHP na to nemá modifikátor, takže to hlídá konvence a review.
 */
final class OrderItem
{
    public function __construct(
        public readonly string $sku,
        public readonly string $productName,
        public readonly int $unitPriceInCents,
        private int $quantity,
    ) {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('Množství musí být alespoň 1.');
        }
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function total(): int
    {
        return $this->unitPriceInCents * $this->quantity;
    }

    /** Volá jen kořen agregátu — ten hlídá pravidla celku. */
    public function changeQuantity(int $quantity): void
    {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('Množství musí být alespoň 1.');
        }

        $this->quantity = $quantity;
    }
}
