<?php

declare(strict_types=1);

namespace Domain;

/**
 * Kořen agregátu, který zaznamenává, co se s ním stalo.
 *
 * Podívej se, co v téhle třídě NENÍ: žádný dispatcher, žádný mailer,
 * žádný sklad. Objednávka o nich neví a vědět nemá — jen konstatuje,
 * že byla založena.
 */
final class Order
{
    use RecordsEvents;

    /** @var list<array{sku: string, quantity: int}> */
    private array $items;

    private string $status = 'nová';

    /** @param list<array{sku: string, quantity: int}> $items */
    private function __construct(
        public readonly string $id,
        public readonly string $customerEmail,
        public readonly int $totalInCents,
        array $items,
    ) {
        $this->items = $items;
    }

    /** @param list<array{sku: string, quantity: int}> $items */
    public static function place(
        string $id,
        string $customerEmail,
        int $totalInCents,
        array $items,
        \DateTimeImmutable $now,
    ): self {
        if ($items === []) {
            throw new \DomainException('Objednávka musí mít alespoň jednu položku.');
        }

        $order = new self($id, $customerEmail, $totalInCents, $items);

        $order->recordThat(new OrderPlaced($id, $customerEmail, $totalInCents, $items, $now));

        return $order;
    }

    public function ship(string $trackingNumber, \DateTimeImmutable $now): void
    {
        if ($this->status !== 'nová') {
            throw new \DomainException(sprintf('Objednávku ve stavu „%s“ nelze odeslat.', $this->status));
        }

        $this->status = 'odeslaná';

        $this->recordThat(new OrderShipped($this->id, $trackingNumber, $now));
    }

    public function status(): string
    {
        return $this->status;
    }
}
