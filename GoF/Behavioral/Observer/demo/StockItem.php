<?php

declare(strict_types=1);

/**
 * SUBJEKT — objekt, který se mění a dává o tom vědět.
 *
 * Drží seznam pozorovatelů a po každé změně je obejde. Nezajímá ho,
 * kdo to je, kolik jich je ani co dělají — jen že chtěli vědět.
 *
 * Tohle je rozdíl proti běžnému volání: kdyby sklad volal mailer
 * a doplňování a statistiky přímo, musel by je všechny znát.
 * Takhle nezná ani jednoho.
 */
final class StockItem
{
    /** @var list<StockObserver> */
    private array $observers = [];

    public function __construct(
        public readonly string $sku,
        private int $quantity,
    ) {
    }

    public function subscribe(StockObserver $observer): void
    {
        $this->observers[] = $observer;
    }

    public function unsubscribe(StockObserver $observer): void
    {
        $this->observers = array_values(array_filter(
            $this->observers,
            static fn (StockObserver $o): bool => $o !== $observer,
        ));
    }

    public function changeQuantity(int $quantity): void
    {
        if ($quantity < 0) {
            throw new InvalidArgumentException('Množství nemůže být záporné.');
        }

        if ($quantity === $this->quantity) {
            return;   // nic se nezměnilo, není co oznamovat
        }

        $previous = $this->quantity;
        $this->quantity = $quantity;

        $this->notify($previous);
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function observerCount(): int
    {
        return count($this->observers);
    }

    private function notify(int $previousQuantity): void
    {
        foreach ($this->observers as $observer) {
            $observer->stockChanged($this, $previousQuantity);
        }
    }
}
