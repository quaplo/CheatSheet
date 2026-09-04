<?php

declare(strict_types=1);

/**
 * Agregát objednávky s invarianty.
 *
 * Konstruktor je private — objednávka nesmí vzniknout jinak než
 * přes továrnu, protože jinak by šlo obejít pravidla níž.
 *
 * Invarianty:
 *   1. objednávka má aspoň jednu položku
 *   2. součet položek se rovná celkové ceně
 *   3. při platbě předem je limit na hodnotu objednávky
 *   4. každé SKU je v objednávce nejvýš jednou
 */
final class Order
{
    public const int PREPAID_LIMIT_IN_CENTS = 5_000_00;

    /** @param list<OrderLine> $lines */
    private function __construct(
        public readonly string $number,
        private array $lines,
        private readonly Money $total,
        private readonly string $paymentMethod,
    ) {
    }

    /**
     * TOVÁRNA: vytvoří celý agregát najednou a vynutí invarianty.
     *
     * Evans: „Create an entire aggregate as a piece, enforcing its invariants."
     *
     * @param list<OrderLine> $lines
     */
    public static function place(string $number, array $lines, string $paymentMethod): self
    {
        if ($lines === []) {
            throw new DomainException('Objednávka musí mít aspoň jednu položku.');
        }

        $seen = [];

        foreach ($lines as $line) {
            if (isset($seen[$line->sku])) {
                throw new DomainException(
                    sprintf('SKU %s je v objednávce dvakrát; slučte položky.', $line->sku),
                );
            }

            $seen[$line->sku] = true;
        }

        $total = Money::fromCents(0);

        foreach ($lines as $line) {
            $total = $total->add($line->subtotal());
        }

        if ($paymentMethod === 'předem' && $total->isGreaterThan(Money::fromCents(self::PREPAID_LIMIT_IN_CENTS))) {
            throw new DomainException(
                sprintf(
                    'Platba předem je možná do %s; tahle objednávka je za %s.',
                    Money::fromCents(self::PREPAID_LIMIT_IN_CENTS)->format(),
                    $total->format(),
                ),
            );
        }

        return new self($number, $lines, $total, $paymentMethod);
    }

    /**
     * REKONSTRUKCE: objednávka načtená z databáze.
     *
     * Záměrně NEkontroluje invarianty. Tenhle stav už jednou platný
     * byl — a kdyby se pravidla mezitím změnila, načtení staré
     * objednávky by spadlo.
     *
     * @param list<OrderLine> $lines
     */
    public static function reconstitute(
        string $number,
        array $lines,
        Money $total,
        string $paymentMethod,
    ): self {
        return new self($number, $lines, $total, $paymentMethod);
    }

    public function total(): Money
    {
        return $this->total;
    }

    /** @return list<OrderLine> */
    public function lines(): array
    {
        return $this->lines;
    }

    public function paymentMethod(): string
    {
        return $this->paymentMethod;
    }
}
