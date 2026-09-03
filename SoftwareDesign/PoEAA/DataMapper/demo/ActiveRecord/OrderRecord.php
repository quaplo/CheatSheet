<?php

declare(strict_types=1);

namespace ActiveRecord;

/**
 * PROTIPÓL: Active Record.
 *
 * Objekt si umí uložit sám. Je to kratší, rychlejší na napsání
 * a pro spoustu aplikací naprosto v pořádku — Laravel Eloquent
 * stojí přesně na tomhle.
 *
 * Cena je v tom, co se tím spojilo dohromady:
 *
 *   · objekt ZNÁ jméno tabulky i sloupců
 *   · objekt POTŘEBUJE databázi, aby vůbec vznikl smysluplně
 *   · změna schématu = změna doménového objektu
 *   · test byznys pravidla se neobejde bez databáze
 *   · struktura objektu se řídí tím, jak vypadá tabulka
 *
 * Poslední bod je ten nejzákeřnější: u Active Recordu se doménový
 * model tvaruje podle databáze, ne podle domény.
 */
final class OrderRecord
{
    public string $number = '';
    public string $customerEmail = '';
    public int $totalCents = 0;
    public string $status = 'nová';

    public function __construct(
        private readonly \PDO $connection,   // ← doménový objekt drží spojení do databáze
    ) {
    }

    public static function createSchema(\PDO $connection): void
    {
        $connection->exec(
            'CREATE TABLE ar_orders (
                number         TEXT PRIMARY KEY,
                customer_email TEXT    NOT NULL,
                total_cents    INTEGER NOT NULL,
                status         TEXT    NOT NULL
            )',
        );
    }

    public function save(): void
    {
        $this->connection
            ->prepare(
                'INSERT INTO ar_orders (number, customer_email, total_cents, status)
                 VALUES (:n, :e, :t, :s)
                 ON CONFLICT(number) DO UPDATE SET status = excluded.status',
            )
            ->execute([
                'n' => $this->number,
                'e' => $this->customerEmail,
                't' => $this->totalCents,
                's' => $this->status,
            ]);
    }

    public static function find(\PDO $connection, string $number): ?self
    {
        $statement = $connection->prepare('SELECT * FROM ar_orders WHERE number = :n');
        $statement->execute(['n' => $number]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        $record = new self($connection);
        $record->number = $row['number'];
        $record->customerEmail = $row['customer_email'];
        $record->totalCents = (int) $row['total_cents'];
        $record->status = $row['status'];

        return $record;
    }

    public function markPaid(): void
    {
        $this->status = 'zaplacená';
        $this->save();          // ← byznys operace rovnou zapisuje do databáze
    }
}
