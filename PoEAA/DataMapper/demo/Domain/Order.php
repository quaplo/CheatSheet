<?php

declare(strict_types=1);

namespace Domain;

/**
 * Doménový objekt. Zkus v něm najít cokoli o databázi.
 *
 * Není tu `save()`, není tu `find()`, není tu jméno jediné tabulky ani
 * sloupce. Nezná ani mapper, který ho ukládá — a to je celý pattern.
 *
 * Důsledek: tenhle soubor jde otestovat bez databáze, bez ORM
 * a bez konfigurace. A když se změní schéma, nezmění se ani řádek.
 */
final class Order
{
    /** @var list<OrderItem> */
    private array $items;

    /** @param list<OrderItem> $items */
    private function __construct(
        public readonly string $number,
        public readonly string $customerEmail,
        private OrderStatus $status,
        public readonly \DateTimeImmutable $placedAt,
        array $items,
    ) {
        $this->items = $items;
    }

    /** @param list<OrderItem> $items */
    public static function place(
        string $number,
        string $customerEmail,
        array $items,
        \DateTimeImmutable $placedAt,
    ): self {
        if ($items === []) {
            throw new \DomainException('Objednávka musí mít alespoň jednu položku.');
        }

        return new self($number, $customerEmail, OrderStatus::New, $placedAt, $items);
    }

    /**
     * Rekonstrukce z úložiště.
     *
     * Existuje kvůli mapperu a jen kvůli němu: obchází zakládací
     * pravidla, protože ta data už jednou platná byla. Bez téhle
     * cesty by mapper musel sahat na privátní vlastnosti reflexí —
     * což je přesně to, co pod kapotou dělá Doctrine.
     *
     * @param list<OrderItem> $items
     */
    public static function reconstitute(
        string $number,
        string $customerEmail,
        OrderStatus $status,
        \DateTimeImmutable $placedAt,
        array $items,
    ): self {
        return new self($number, $customerEmail, $status, $placedAt, $items);
    }

    public function markPaid(): void
    {
        $this->status = OrderStatus::Paid;
    }

    public function total(): Money
    {
        $total = Money::fromCents(0);

        foreach ($this->items as $item) {
            $total = $total->add($item->total());
        }

        return $total;
    }

    public function status(): OrderStatus
    {
        return $this->status;
    }

    /** @return list<OrderItem> */
    public function items(): array
    {
        return $this->items;
    }
}
