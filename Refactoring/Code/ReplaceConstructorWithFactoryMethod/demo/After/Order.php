<?php

declare(strict_types=1);

namespace After;

/**
 * PO: tři pojmenované cesty, jeden soukromý konstruktor.
 *
 * Každá továrna odpovídá jedné situaci, má jen parametry, které
 * ta situace potřebuje, a hlídá pravidla, která pro ni platí.
 *
 * V PHP je to navíc jediný způsob, jak mít víc konstruktorů —
 * jazyk nezná přetěžování.
 */
final class Order
{
    /** @param list<string> $items */
    private function __construct(
        public readonly string $number,
        public readonly array $items,
        public readonly ?string $customerId,
        public readonly ?string $partnerCode,
        public readonly bool $isPaid,
        public readonly bool $skipStockCheck,
    ) {
    }

    /** Zákazník objednal v e-shopu. Platí se až potom, sklad se kontroluje. */
    public static function placedByCustomer(string $number, array $items, string $customerId): self
    {
        if ($items === []) {
            throw new \DomainException('Objednávka musí mít aspoň jednu položku.');
        }

        return new self($number, $items, $customerId, null, isPaid: false, skipStockCheck: false);
    }

    /** Přišlo importem od partnera. Už je zaplaceno, sklad neřešíme. */
    public static function importedFromPartner(string $number, array $items, string $partnerCode): self
    {
        if ($items === []) {
            throw new \DomainException('Objednávka musí mít aspoň jednu položku.');
        }

        return new self($number, $items, null, $partnerCode, isPaid: true, skipStockCheck: true);
    }

    /**
     * Načteno z databáze — stav, který kdysi platný byl.
     *
     * Schválně bez kontrol: kdyby se pravidla mezitím změnila,
     * nešlo by načíst staré objednávky.
     *
     * @param array{number: string, items: list<string>, customer_id: ?string, partner_code: ?string, is_paid: bool} $row
     */
    public static function reconstitute(array $row): self
    {
        return new self(
            $row['number'],
            $row['items'],
            $row['customer_id'],
            $row['partner_code'],
            $row['is_paid'],
            skipStockCheck: false,
        );
    }
}
