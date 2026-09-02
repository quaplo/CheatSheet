<?php

declare(strict_types=1);

namespace Domain;

/**
 * Objednávka s mnoha volitelnými částmi.
 *
 * Konstruktor je záměrně nepříjemný — přesně takhle vypadá objekt,
 * u kterého se builder vyplatí. Devět parametrů, půlka volitelných,
 * a z volání nikdo nepozná, co je co.
 */
final readonly class Order
{
    /** @param list<OrderItem> $items */
    public function __construct(
        public string $number,
        public string $customerEmail,
        public array $items,
        public string $shippingMethod,
        public string $paymentMethod,
        public ?string $note,
        public ?string $couponCode,
        public bool $isGift,
        public \DateTimeImmutable $placedAt,
    ) {
        if ($items === []) {
            throw new \DomainException('Objednávka musí mít alespoň jednu položku.');
        }

        if ($isGift && $note === null) {
            throw new \DomainException('Dárková objednávka musí mít vzkaz.');
        }
    }

    public function totalInCents(): int
    {
        return array_sum(array_map(static fn (OrderItem $i): int => $i->total(), $this->items));
    }

    public function describe(): string
    {
        return sprintf(
            '%s · %s · %d pol. · %s Kč · %s/%s%s%s',
            $this->number,
            $this->customerEmail,
            count($this->items),
            number_format($this->totalInCents() / 100, 0, ',', ' '),
            $this->shippingMethod,
            $this->paymentMethod,
            $this->couponCode !== null ? ' · kupón ' . $this->couponCode : '',
            $this->isGift ? ' · DÁREK' : '',
        );
    }
}
