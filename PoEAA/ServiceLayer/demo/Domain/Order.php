<?php

declare(strict_types=1);

namespace Domain;

/**
 * Doména. Pravidlo o úvěrovém limitu je TADY.
 *
 * Proč zrovna tady a ne v use-case: protože sem se dostane každý,
 * kdo objednávku zakládá — HTTP, import z CSV, konzument fronty i test.
 * Pravidlo v use-case chrání jen tu jednu cestu.
 */
final class Order
{
    private string $status = 'nová';

    private function __construct(
        public readonly OrderId $id,
        public readonly string $customerId,
        public readonly int $totalInCents,
    ) {
    }

    public static function place(OrderId $id, string $customerId, int $totalInCents, int $creditLimitInCents): self
    {
        if ($totalInCents <= 0) {
            throw new \DomainException('Objednávka musí mít kladnou hodnotu.');
        }

        if ($totalInCents > $creditLimitInCents) {
            throw CreditLimitExceeded::by($totalInCents, $creditLimitInCents);
        }

        return new self($id, $customerId, $totalInCents);
    }

    public function cancel(): void
    {
        if ($this->status === 'odeslaná') {
            throw new \DomainException('Odeslanou objednávku nelze zrušit.');
        }

        $this->status = 'zrušená';
    }

    public function status(): string
    {
        return $this->status;
    }
}
