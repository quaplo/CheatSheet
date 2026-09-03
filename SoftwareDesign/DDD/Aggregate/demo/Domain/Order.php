<?php

declare(strict_types=1);

namespace Domain;

/**
 * KOŘEN AGREGÁTU.
 *
 * Agregát je Order + jeho OrderItemy. Kořen je jediná cesta dovnitř
 * a jediné místo, kde se hlídají pravidla platná pro CELEK:
 *
 *   · nejvýš 20 položek
 *   · celková hodnota nepřesáhne schválený limit
 *   · odeslanou objednávku už nikdo nemění
 *
 * Ani jedno z nich neumí uhlídat samotná položka — protože žádná
 * položka nevidí ostatní. To je celý důvod, proč hranice existuje.
 */
final class Order
{
    private const int MAX_ITEMS = 20;

    /** @var list<OrderItem> */
    private array $items = [];

    private function __construct(
        public readonly OrderId $id,
        public readonly CustomerId $customerId,   // odkaz identitou, ne objektem
        private readonly int $approvedLimitInCents,
        private string $status,
    ) {
    }

    public static function place(OrderId $id, CustomerId $customerId, int $approvedLimitInCents): self
    {
        return new self($id, $customerId, $approvedLimitInCents, status: 'nová');
    }

    // --- Jediná cesta dovnitř ----------------------------------------------

    public function addItem(string $sku, string $productName, int $unitPriceInCents, int $quantity): void
    {
        $this->assertModifiable();

        $item = new OrderItem($sku, $productName, $unitPriceInCents, $quantity);

        $this->assertWouldStayValid([...$this->items, $item]);

        $this->items[] = $item;
    }

    public function changeQuantity(string $sku, int $quantity): void
    {
        $this->assertModifiable();

        $item = $this->itemBySku($sku);
        $originalQuantity = $item->quantity();

        $item->changeQuantity($quantity);

        try {
            $this->assertWouldStayValid($this->items);
        } catch (\DomainException $e) {
            $item->changeQuantity($originalQuantity);   // vrať zpět, invariant vyhrává

            throw $e;
        }
    }

    public function removeItem(string $sku): void
    {
        $this->assertModifiable();

        $this->items = array_values(array_filter(
            $this->items,
            static fn (OrderItem $item): bool => $item->sku !== $sku,
        ));
    }

    public function ship(): void
    {
        if ($this->items === []) {
            throw new \DomainException('Prázdnou objednávku nelze odeslat.');
        }

        $this->status = 'odeslaná';
    }

    // --- Dotazy ------------------------------------------------------------

    public function total(): int
    {
        return array_sum(array_map(static fn (OrderItem $i): int => $i->total(), $this->items));
    }

    public function itemCount(): int
    {
        return count($this->items);
    }

    public function status(): string
    {
        return $this->status;
    }

    public function remainingLimit(): int
    {
        return $this->approvedLimitInCents - $this->total();
    }

    /**
     * Ven jde jen kopie seznamu, a i tak nerada.
     *
     * Kdyby se vracelo pole s referencemi, mohl by volající zavolat
     * $items[0]->changeQuantity() mimo kořen — a obejít tím všechna
     * pravidla. V produkci by tu byl spíš čtecí DTO.
     *
     * @return list<array{sku: string, name: string, quantity: int, total: int}>
     */
    public function itemSummary(): array
    {
        return array_map(
            static fn (OrderItem $i): array => [
                'sku' => $i->sku,
                'name' => $i->productName,
                'quantity' => $i->quantity(),
                'total' => $i->total(),
            ],
            $this->items,
        );
    }

    // --- Invarianty celku --------------------------------------------------

    /** @param list<OrderItem> $items */
    private function assertWouldStayValid(array $items): void
    {
        if (count($items) > self::MAX_ITEMS) {
            throw new \DomainException(sprintf('Objednávka smí mít nejvýš %d položek.', self::MAX_ITEMS));
        }

        $total = array_sum(array_map(static fn (OrderItem $i): int => $i->total(), $items));

        if ($total > $this->approvedLimitInCents) {
            throw new \DomainException(sprintf(
                'Hodnota %s Kč přesahuje schválený limit %s Kč.',
                number_format($total / 100, 0, ',', ' '),
                number_format($this->approvedLimitInCents / 100, 0, ',', ' '),
            ));
        }
    }

    private function assertModifiable(): void
    {
        if ($this->status !== 'nová') {
            throw new \DomainException(sprintf('Objednávku ve stavu „%s“ už nelze měnit.', $this->status));
        }
    }

    private function itemBySku(string $sku): OrderItem
    {
        foreach ($this->items as $item) {
            if ($item->sku === $sku) {
                return $item;
            }
        }

        throw new \DomainException(sprintf('Objednávka neobsahuje položku %s.', $sku));
    }
}
