<?php

declare(strict_types=1);

namespace Before;

/**
 * PŘED: jádro zamotané s podpůrnými hráči.
 *
 * Evans: „Elements in the model may partially serve the core domain
 * and partially play supporting roles. […] All this clutter and
 * entanglement chokes the core."
 *
 * Objednávka tady ví o měně, formátování, e-mailech, exportu do CSV
 * i o číselníku zemí. Pravidlo, kvůli kterému třída existuje —
 * kdy se smí objednávka zrušit — se v tom ztrácí.
 */
final class Order
{
    /** @var list<array{sku: string, priceInCents: int, quantity: int}> */
    private array $items = [];

    private string $status = 'nová';

    public function __construct(
        public readonly string $number,
        private readonly string $customerEmail,
        private readonly string $countryCode,
        private readonly CurrencyConverter $converter,
        private readonly Mailer $mailer,
        private readonly CountryRegistry $countries,
    ) {
    }

    // --- jádro: pravidla objednávky ----------------------------------------

    public function addItem(string $sku, int $priceInCents, int $quantity): void
    {
        if ($this->status !== 'nová') {
            throw new \DomainException('Do potvrzené objednávky nelze přidávat.');
        }

        $this->items[] = ['sku' => $sku, 'priceInCents' => $priceInCents, 'quantity' => $quantity];
    }

    public function totalInCents(): int
    {
        return array_sum(array_map(
            static fn (array $i): int => $i['priceInCents'] * $i['quantity'],
            $this->items,
        ));
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['nová', 'potvrzená'], true);
    }

    public function cancel(): void
    {
        if (!$this->canBeCancelled()) {
            throw new \DomainException('Objednávku v tomto stavu nelze zrušit.');
        }

        $this->status = 'zrušená';
        $this->mailer->send($this->customerEmail, 'Objednávka zrušena', $this->formatSummary());
    }

    public function confirm(): void
    {
        if ($this->status !== 'nová') {
            throw new \DomainException('Objednávku nelze potvrdit.');
        }

        $this->status = 'potvrzená';
        $this->mailer->send($this->customerEmail, 'Objednávka přijata', $this->formatSummary());
    }

    public function status(): string
    {
        return $this->status;
    }

    // --- podpůrné role, které se sem nastěhovaly ---------------------------

    public function formatTotal(): string
    {
        return number_format($this->totalInCents() / 100, 2, ',', ' ') . ' Kč';
    }

    public function totalInEur(): float
    {
        return $this->converter->toEur($this->totalInCents());
    }

    public function formatSummary(): string
    {
        $lines = [sprintf('Objednávka %s (%s)', $this->number, $this->status)];

        foreach ($this->items as $item) {
            $lines[] = sprintf('  %s × %d', $item['sku'], $item['quantity']);
        }

        $lines[] = 'Celkem: ' . $this->formatTotal();

        return implode("\n", $lines);
    }

    public function toCsvRow(): string
    {
        return implode(';', [
            $this->number,
            $this->status,
            (string) $this->totalInCents(),
            $this->countryName(),
        ]);
    }

    public function countryName(): string
    {
        return $this->countries->nameOf($this->countryCode);
    }

    public function isEuCountry(): bool
    {
        return $this->countries->isEu($this->countryCode);
    }

    public function vatRate(): float
    {
        return $this->isEuCountry() ? 0.21 : 0.0;
    }
}
