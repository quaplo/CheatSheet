<?php

declare(strict_types=1);

/**
 * ZÁPISOVÝ MODEL — kořen agregátu.
 *
 * Je navržený tak, aby uhlídal pravidla: nesmí být prázdný, nesmí
 * překročit limit položek, změna stavu má svá pravidla. Je hluboký
 * a úzký — přesně to, co potřebuje zápis.
 *
 * A přesně to, co je pro výpis tabulky nepoužitelné.
 */
final readonly class Order
{
    private const int MAX_ITEMS = 50;

    /** @param list<OrderItem> $items */
    private function __construct(
        public string $id,
        public string $customerEmail,
        public string $status,
        public DateTimeImmutable $placedAt,
        public array $items,
    ) {
    }

    /** @param list<OrderItem> $items */
    public static function place(
        string $id,
        string $customerEmail,
        array $items,
        DateTimeImmutable $placedAt,
    ): self {
        if ($items === []) {
            throw new InvalidArgumentException('Objednávka musí mít alespoň jednu položku.');
        }

        if (count($items) > self::MAX_ITEMS) {
            throw new InvalidArgumentException('Objednávka má příliš mnoho položek.');
        }

        return new self($id, $customerEmail, 'nová', $placedAt, $items);
    }

    /** @param list<OrderItem> $items */
    public static function reconstitute(
        string $id,
        string $customerEmail,
        string $status,
        DateTimeImmutable $placedAt,
        array $items,
    ): self {
        return new self($id, $customerEmail, $status, $placedAt, $items);
    }

    public function total(): int
    {
        return array_sum(array_map(static fn (OrderItem $i): int => $i->total(), $this->items));
    }
}
