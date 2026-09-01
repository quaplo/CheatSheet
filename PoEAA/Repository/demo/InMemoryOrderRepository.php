<?php

declare(strict_types=1);

/**
 * Implementace v paměti.
 *
 * Není to hračka pro ukázku — tohle je ta implementace, kterou pouštíš
 * v testech. Kvůli ní jde otestovat use-case bez databáze v milisekundách.
 * Aby k něčemu byla, musí se chovat stejně jako ta ostrá; proto na obě
 * pouštěj tytéž testy (viz sekce Časté chyby).
 */
final class InMemoryOrderRepository implements OrderRepository
{
    /** @var array<string, Order> */
    private array $orders = [];

    public function nextIdentity(): OrderId
    {
        return OrderId::generate();
    }

    public function save(Order $order): void
    {
        $this->orders[$order->id->value] = $order;
    }

    public function remove(OrderId $id): void
    {
        unset($this->orders[$id->value]);
    }

    public function get(OrderId $id): Order
    {
        return $this->find($id) ?? throw OrderNotFound::withId($id);
    }

    public function find(OrderId $id): ?Order
    {
        return $this->orders[$id->value] ?? null;
    }

    public function unpaidPlacedBefore(DateTimeImmutable $moment): array
    {
        $matching = array_values(array_filter(
            $this->orders,
            static fn (Order $order): bool => $order->isPaid === false && $order->placedAt < $moment,
        ));

        // Řazení tu není kosmetika. Ostrá implementace má ORDER BY placed_at,
        // takže bez tohohle by obě implementace vracely jiné pořadí — a testy
        // proti paměti by procházely, zatímco produkce by se chovala jinak.
        usort(
            $matching,
            static fn (Order $a, Order $b): int => $a->placedAt <=> $b->placedAt,
        );

        return $matching;
    }

    public function countUnpaid(): int
    {
        return count(array_filter(
            $this->orders,
            static fn (Order $order): bool => $order->isPaid === false,
        ));
    }
}
