<?php

declare(strict_types=1);

namespace Core\Entities;

/**
 * Entita: pravidla, která platí, ať tenhle software existuje, nebo ne.
 *
 * „Objednávku nelze stornovat po odeslání" platí i v účetnictví,
 * i na papíře, i v jiné aplikaci téhle firmy.
 */
final class Order
{
    /** @param list<OrderLine> $lines */
    public function __construct(
        public readonly string $id,
        private array $lines,
        private string $status,
    ) {
    }

    public function totalInCents(): int
    {
        return array_sum(array_map(
            static fn (OrderLine $line): int => $line->priceInCents * $line->quantity,
            $this->lines,
        ));
    }

    public function cancel(): void
    {
        if ($this->status === 'shipped') {
            throw new \DomainException('Odeslanou objednávku nelze stornovat, jen vrátit.');
        }

        $this->status = 'cancelled';
    }

    public function status(): string
    {
        return $this->status;
    }

    public function lineCount(): int
    {
        return count($this->lines);
    }
}
