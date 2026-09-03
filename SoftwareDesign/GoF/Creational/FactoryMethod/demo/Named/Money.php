<?php

declare(strict_types=1);

namespace Named;

/**
 * POJMENOVANÉ KONSTRUKTORY — to, co v tomhle katalogu vidíš všude.
 *
 * Není to GoF Factory Method (o tom je druhá půlka dema), ale je to
 * nejužitečnější podoba té myšlenky v PHP: **vytvoření objektu má
 * jméno**.
 *
 * Řeší tři věci, které konstruktor sám neumí:
 *   1. Konstruktor je jen jeden a nedá se přetížit.
 *   2. `new Money(129000)` neříká, jestli jsou to koruny nebo haléře.
 *   3. Neplatnou instanci nejde vytvořit, když je konstruktor privátní.
 */
final readonly class Money
{
    private function __construct(
        public int $amountInCents,
        public string $currency,
    ) {
    }

    public static function fromCents(int $amountInCents, string $currency = 'CZK'): self
    {
        return new self($amountInCents, $currency);
    }

    public static function fromCrowns(float $crowns, string $currency = 'CZK'): self
    {
        return new self((int) round($crowns * 100), $currency);
    }

    /** „1 234,50 Kč“ → Money. Parsování patří do továrny, ne do konstruktoru. */
    public static function fromString(string $formatted): self
    {
        $normalized = str_replace([' ', "\u{00A0}", ','], ['', '', '.'], trim($formatted));
        $normalized = preg_replace('/[^\d.\-]/', '', $normalized) ?? '';

        if ($normalized === '' || is_numeric($normalized) === false) {
            throw new \InvalidArgumentException(sprintf('„%s“ není částka.', $formatted));
        }

        return self::fromCrowns((float) $normalized);
    }

    public static function zero(string $currency = 'CZK'): self
    {
        return new self(0, $currency);
    }

    public function format(): string
    {
        return number_format($this->amountInCents / 100, 2, ',', ' ') . ' ' . $this->currency;
    }
}
