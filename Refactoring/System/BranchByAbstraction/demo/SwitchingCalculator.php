<?php

declare(strict_types=1);

/**
 * KROK 4: PŘEPÍNAČ.
 *
 * Sám je implementací abstrakce a rozhoduje, která z obou verzí
 * se použije. Volající o jeho existenci neví — pro něj je to
 * pořád jen ShippingCalculator.
 *
 * Tohle je místo, kde technika získává svou hlavní vlastnost:
 * návrat zpět je změna jednoho čísla, ne nasazení.
 */
final class SwitchingCalculator implements ShippingCalculator
{
    /** @var list<string> co se během provozu rozešlo */
    public array $mismatches = [];

    /** Kolik dotazů obsloužila nová implementace. */
    public int $servedByNew = 0;

    public function __construct(
        private readonly ShippingCalculator $old,
        private readonly ShippingCalculator $new,
        /** Kolik procent provozu jde na novou implementaci. */
        private int $newSharePercent = 0,
        /** Pustit obě a porovnat výsledky, i když se použije jen jeden. */
        private readonly bool $compare = false,
    ) {
    }

    public function setNewShare(int $percent): void
    {
        $this->newSharePercent = max(0, min(100, $percent));
    }

    public function quoteFor(Shipment $shipment): ShippingQuote
    {
        $useNew = $this->pickNew($shipment);

        if ($useNew) {
            ++$this->servedByNew;
        }

        if ($this->compare) {
            $oldQuote = $this->old->quoteFor($shipment);
            $newQuote = $this->new->quoteFor($shipment);

            if (!$oldQuote->equals($newQuote)) {
                $this->mismatches[] = sprintf(
                    '%s %d g / %s → stará %s · nová %s',
                    $shipment->countryCode,
                    $shipment->weightInGrams,
                    number_format($shipment->orderValueInCents / 100, 0, ',', ' ') . ' Kč',
                    $oldQuote->format(),
                    $newQuote->format(),
                );
            }

            return $useNew ? $newQuote : $oldQuote;
        }

        return $useNew
            ? $this->new->quoteFor($shipment)
            : $this->old->quoteFor($shipment);
    }

    /**
     * Deterministické rozdělení provozu.
     *
     * Záměrně ne náhodné: tatáž zásilka musí dostat tutéž odpověď,
     * jinak zákazník při obnovení stránky vidí jinou cenu.
     */
    private function pickNew(Shipment $shipment): bool
    {
        if ($this->newSharePercent <= 0) {
            return false;
        }

        if ($this->newSharePercent >= 100) {
            return true;
        }

        $bucket = crc32($shipment->countryCode . ':' . $shipment->weightInGrams) % 100;

        return $bucket < $this->newSharePercent;
    }
}
