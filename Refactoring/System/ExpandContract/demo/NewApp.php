<?php

declare(strict_types=1);

/**
 * NOVÁ verze aplikace — pracuje se sloupcem `status`.
 *
 * Ve fázi expand ale musí zapisovat do OBOU sloupců, aby stará verze,
 * která běží vedle ní, viděla správná data. Tomu se říká dvojí zápis.
 */
final class NewApp
{
    public function __construct(
        private readonly Database $db,
        /** Ve fázi expand a migrate se zapisuje i do starého sloupce. */
        private readonly bool $dualWrite = true,
    ) {
    }

    public function place(string $number): void
    {
        if ($this->dualWrite) {
            $this->db->pdo
                ->prepare('INSERT INTO orders (number, status, shipped) VALUES (?, ?, 0)')
                ->execute([$number, 'nová']);

            return;
        }

        $this->db->pdo
            ->prepare('INSERT INTO orders (number, status) VALUES (?, ?)')
            ->execute([$number, 'nová']);
    }

    public function markShipped(string $number): void
    {
        if ($this->dualWrite) {
            $this->db->pdo
                ->prepare('UPDATE orders SET status = ?, shipped = 1 WHERE number = ?')
                ->execute(['expedovaná', $number]);

            return;
        }

        $this->db->pdo
            ->prepare('UPDATE orders SET status = ? WHERE number = ?')
            ->execute(['expedovaná', $number]);
    }

    public function statusOf(string $number): string
    {
        $stmt = $this->db->pdo->prepare('SELECT status FROM orders WHERE number = ?');
        $stmt->execute([$number]);

        return (string) $stmt->fetchColumn();
    }

    /** Nová verze umí i to, co stará neuměla — proto se migruje. */
    public function cancel(string $number): void
    {
        $column = $this->dualWrite ? 'status = ?, shipped = 0' : 'status = ?';

        $this->db->pdo
            ->prepare('UPDATE orders SET ' . $column . ' WHERE number = ?')
            ->execute(['zrušená', $number]);
    }
}
