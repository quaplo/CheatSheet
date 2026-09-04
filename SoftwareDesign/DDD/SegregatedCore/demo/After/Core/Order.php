<?php

declare(strict_types=1);

namespace After\Core;

/**
 * PO: oddělené jádro.
 *
 * Evans: „Refactor the model to separate the core concepts from
 * supporting players […] and strengthen the cohesion of the core
 * while reducing its coupling to other code."
 *
 * Zůstalo jen to, kvůli čemu objednávka existuje: co obsahuje,
 * v jakém je stavu a kdy se smí zrušit nebo potvrdit.
 *
 * Všimni si konstruktoru — nic se do něj nepředává. Žádný mailer,
 * žádný převodník měn, žádný číselník zemí. Jádro nezná nikoho.
 */
final class Order
{
    /** @var list<OrderItem> */
    private array $items = [];

    private OrderStatus $status = OrderStatus::New;

    public function __construct(public readonly string $number)
    {
    }

    public function addItem(OrderItem $item): void
    {
        if ($this->status !== OrderStatus::New) {
            throw new \DomainException('Do potvrzené objednávky nelze přidávat.');
        }

        $this->items[] = $item;
    }

    public function totalInCents(): int
    {
        return array_sum(array_map(
            static fn (OrderItem $i): int => $i->subtotalInCents(),
            $this->items,
        ));
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [OrderStatus::New, OrderStatus::Confirmed], true);
    }

    public function cancel(): void
    {
        if (!$this->canBeCancelled()) {
            throw new \DomainException('Objednávku v tomto stavu nelze zrušit.');
        }

        $this->status = OrderStatus::Cancelled;
    }

    public function confirm(): void
    {
        if ($this->status !== OrderStatus::New) {
            throw new \DomainException('Objednávku nelze potvrdit.');
        }

        $this->status = OrderStatus::Confirmed;
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
