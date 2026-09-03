<?php

declare(strict_types=1);

namespace Broken;

use Domain\OrderItem;

/**
 * ANTI-PŘÍKLAD: repository pro vnitřní entitu agregátu.
 *
 * Vypadá neškodně — „jen ať jde upravit množství, nebudeme kvůli tomu
 * načítat celou objednávku“. Jenže tím vznikla druhá cesta dovnitř,
 * která **obchází kořen a s ním všechna pravidla celku**.
 *
 * Tohle je nejčastější způsob, jak se agregát rozpadne. Ne velkým
 * rozhodnutím, ale jednou drobnou optimalizací.
 */
final class OrderItemRepository
{
    /** @var array<string, OrderItem> */
    private array $items = [];

    public function remember(string $sku, OrderItem $item): void
    {
        $this->items[$sku] = $item;
    }

    public function get(string $sku): OrderItem
    {
        return $this->items[$sku];
    }

    /** Uloží položku samostatně. Kořen o tom neví a nemá jak zasáhnout. */
    public function updateQuantity(string $sku, int $quantity): void
    {
        $this->items[$sku]->changeQuantity($quantity);
    }
}
