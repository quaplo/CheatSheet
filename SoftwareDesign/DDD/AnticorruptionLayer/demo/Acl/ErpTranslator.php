<?php

declare(strict_types=1);

namespace Acl;

use Domain\Delivery;
use Domain\DeliveryStatus;
use Domain\GoodsReturn;
use Domain\SupplierId;

/**
 * PŘEKLADAČ — druhý díl antikorupční vrstvy.
 *
 * Tady je soustředěná veškerá špína. Každá zvláštnost cizího systému
 * má v téhle třídě přesně jedno místo, a nikde jinde v aplikaci se
 * neobjeví.
 *
 * Rozdíl proti obyčejnému mapperu: nepřevádí jen tvary dat, ale
 * **pojmy**. Řádek se záporným množstvím není dodávka se záporným
 * číslem — je to vratka, což je u nás jiná věc.
 */
final readonly class ErpTranslator
{
    /** @param array<string, string> $row */
    public function isReturn(array $row): bool
    {
        return $this->quantity($row) < 0;
    }

    /** @param array<string, string> $row */
    public function toDelivery(array $row): Delivery
    {
        return new Delivery(
            number: $row['DOD_CIS'],
            supplierId: SupplierId::fromString('ERP-' . $row['PARTNER_CIS']),
            supplierName: $row['PARTNER_NAZ'],
            quantity: $this->quantity($row),
            valueInCents: $this->amountInCents($row['DOD_CENA']),
            deliveredOn: $this->date($row['DOD_DAT']),
            status: $this->status($row['DOD_STAV']),
        );
    }

    /** @param array<string, string> $row */
    public function toReturn(array $row): GoodsReturn
    {
        return new GoodsReturn(
            number: $row['DOD_CIS'],
            supplierId: SupplierId::fromString('ERP-' . $row['PARTNER_CIS']),
            supplierName: $row['PARTNER_NAZ'],
            quantity: abs($this->quantity($row)),
            creditedInCents: $this->amountInCents($row['DOD_CENA']),
            returnedOn: $this->date($row['DOD_DAT']),
        );
    }

    /** @param array<string, string> $row */
    private function quantity(array $row): int
    {
        return (int) $row['DOD_MNOZ'];
    }

    /** „1 234,50“ → 123450. Formát cizího systému nesmí ven. */
    private function amountInCents(string $amount): int
    {
        $normalized = str_replace([' ', "\u{00A0}", ','], ['', '', '.'], $amount);

        return (int) round((float) $normalized * 100);
    }

    /** „20260901“ → DateTimeImmutable. */
    private function date(string $raw): \DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat('!Ymd', $raw);

        if ($date === false) {
            throw new \InvalidArgumentException(sprintf('ERP poslalo nečitelné datum „%s“.', $raw));
        }

        return $date;
    }

    /**
     * Číselné kódy → naše stavy.
     *
     * Neznámý kód je chyba, ne tichý default. Kdyby se z něj stal
     * „ohlášená“, protekl by cizí nesmysl do domény bez povšimnutí.
     */
    private function status(string $code): DeliveryStatus
    {
        return match ($code) {
            '01' => DeliveryStatus::Announced,
            '03' => DeliveryStatus::InTransit,
            '07' => DeliveryStatus::Received,
            '99' => DeliveryStatus::Cancelled,
            default => throw new \InvalidArgumentException(sprintf('Neznámý stav ERP „%s“.', $code)),
        };
    }
}
