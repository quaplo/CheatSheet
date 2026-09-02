<?php

declare(strict_types=1);

namespace Mapper;

use Domain\Money;
use Domain\Order;
use Domain\OrderItem;
use Domain\OrderStatus;

/**
 * Mapper pro STARÉ schéma.
 *
 *   objednavky(cislo, zakaznik, castka_kc DECIMAL, mena,
 *              stav_kod INT, dt_vytvoreni)
 *
 * Nesoulad, který musí spolknout:
 *   · české názvy sloupců        → anglická doména
 *   · částka jako DECIMAL text   → Money v haléřích
 *   · stav jako číselný kód      → enum
 *   · datum ve formátu d.m.Y     → DateTimeImmutable
 *   · celková částka JE uložená  → doména si ji počítá z položek
 *
 * Ten poslední bod je nejzajímavější: schéma drží denormalizovaný
 * součet, doména ne. Mapper to při zápisu dopočítá a při čtení
 * ignoruje. Doména o tom neví.
 */
final readonly class LegacyOrderMapper
{
    private const array STATUS_CODES = [1 => 'nová', 2 => 'zaplacená', 3 => 'odeslaná'];

    public function __construct(
        private \PDO $connection,
    ) {
    }

    public static function createSchema(\PDO $connection): void
    {
        $connection->exec(
            'CREATE TABLE objednavky (
                cislo         TEXT PRIMARY KEY,
                zakaznik      TEXT    NOT NULL,
                castka_kc     TEXT    NOT NULL,
                mena          TEXT    NOT NULL,
                stav_kod      INTEGER NOT NULL,
                dt_vytvoreni  TEXT    NOT NULL
            )',
        );
        $connection->exec(
            'CREATE TABLE objednavky_polozky (
                id        INTEGER PRIMARY KEY AUTOINCREMENT,
                objednavka TEXT   NOT NULL,
                kod       TEXT    NOT NULL,
                nazev     TEXT    NOT NULL,
                cena_kc   TEXT    NOT NULL,
                pocet     INTEGER NOT NULL
            )',
        );
    }

    public function insert(Order $order): void
    {
        $head = $this->connection->prepare(
            'INSERT INTO objednavky (cislo, zakaznik, castka_kc, mena, stav_kod, dt_vytvoreni)
             VALUES (:cislo, :zakaznik, :castka, :mena, :stav, :dt)',
        );

        $head->execute([
            'cislo' => $order->number,
            'zakaznik' => $order->customerEmail,
            // Denormalizovaný součet — schéma ho chce, doména ho neukládá.
            'castka' => number_format($order->total()->amountInCents / 100, 2, '.', ''),
            'mena' => $order->total()->currency,
            'stav' => array_search($order->status()->value, self::STATUS_CODES, strict: true),
            'dt' => $order->placedAt->format('d.m.Y'),
        ]);

        $item = $this->connection->prepare(
            'INSERT INTO objednavky_polozky (objednavka, kod, nazev, cena_kc, pocet)
             VALUES (:obj, :kod, :nazev, :cena, :pocet)',
        );

        foreach ($order->items() as $line) {
            $item->execute([
                'obj' => $order->number,
                'kod' => $line->sku,
                'nazev' => $line->productName,
                'cena' => number_format($line->unitPrice->amountInCents / 100, 2, '.', ''),
                'pocet' => $line->quantity,
            ]);
        }
    }

    public function find(string $number): ?Order
    {
        $head = $this->connection->prepare('SELECT * FROM objednavky WHERE cislo = :c');
        $head->execute(['c' => $number]);
        $row = $head->fetch(\PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        $lines = $this->connection->prepare('SELECT * FROM objednavky_polozky WHERE objednavka = :c');
        $lines->execute(['c' => $number]);

        $items = array_map(
            static fn (array $r): OrderItem => new OrderItem(
                $r['kod'],
                $r['nazev'],
                Money::fromCents((int) round((float) $r['cena_kc'] * 100), 'CZK'),
                (int) $r['pocet'],
            ),
            $lines->fetchAll(\PDO::FETCH_ASSOC),
        );

        return Order::reconstitute(
            $row['cislo'],
            $row['zakaznik'],
            OrderStatus::from(self::STATUS_CODES[(int) $row['stav_kod']]),
            \DateTimeImmutable::createFromFormat('!d.m.Y', $row['dt_vytvoreni']),
            $items,
        );
    }
}
