<?php

declare(strict_types=1);

/**
 * Objednávka — kořen agregátu.
 *
 * Repository se zakládá právě a jen na kořeny agregátů. Kdyby měla vlastní
 * repository i položka objednávky, šlo by ji měnit mimo objednávku — a tím
 * by přestala platit pravidla, která objednávka hlídá.
 */
final readonly class Order
{
    private function __construct(
        public OrderId $id,
        public string $customerEmail,
        public int $totalInCents,
        public bool $isPaid,
        public DateTimeImmutable $placedAt,
    ) {
    }

    public static function place(
        OrderId $id,
        string $customerEmail,
        int $totalInCents,
        DateTimeImmutable $placedAt,
    ): self {
        if ($totalInCents <= 0) {
            throw new InvalidArgumentException('Objednávka musí mít kladnou hodnotu.');
        }

        return new self($id, $customerEmail, $totalInCents, isPaid: false, placedAt: $placedAt);
    }

    /** Rekonstrukce z úložiště — používá ji výhradně repository. */
    public static function reconstitute(
        OrderId $id,
        string $customerEmail,
        int $totalInCents,
        bool $isPaid,
        DateTimeImmutable $placedAt,
    ): self {
        return new self($id, $customerEmail, $totalInCents, $isPaid, $placedAt);
    }

    public function markPaid(): self
    {
        return new self($this->id, $this->customerEmail, $this->totalInCents, true, $this->placedAt);
    }
}
