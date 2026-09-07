<?php

declare(strict_types=1);

/**
 * Jednotlivé kroky migrace jako samostatné, nasaditelné změny.
 *
 * Každá metoda je jedno nasazení. Mezi nimi systém běží a obě verze
 * aplikace fungují — kromě posledního kroku, který starou verzi
 * definitivně vyřadí.
 */
final class Migration
{
    public function __construct(private readonly Database $db)
    {
    }

    /** EXPAND: přidat nový sloupec. Nic se nemaže, nic se nerozbije. */
    public function expand(): void
    {
        $this->db->pdo->exec("ALTER TABLE orders ADD COLUMN status TEXT NOT NULL DEFAULT ''");
    }

    /**
     * BACKFILL: doplnit historická data.
     *
     * Po dávkách, ne jedním UPDATE — u milionu řádků by jeden příkaz
     * zamkl tabulku na minuty. Mezi dávkami se pouští ostatní provoz.
     *
     * @return int kolik řádků se doplnilo
     */
    public function backfill(int $batchSize = 100): int
    {
        $migrated = 0;

        while (true) {
            $stmt = $this->db->pdo->prepare(
                "SELECT number, shipped FROM orders WHERE status = '' LIMIT ?",
            );
            $stmt->execute([$batchSize]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($rows === []) {
                break;
            }

            $update = $this->db->pdo->prepare('UPDATE orders SET status = ? WHERE number = ?');

            foreach ($rows as $row) {
                $update->execute([
                    ((int) $row['shipped']) === 1 ? 'expedovaná' : 'nová',
                    $row['number'],
                ]);
                ++$migrated;
            }

            // V produkci se sem dá krátká pauza, aby se databáze nezahltila.
        }

        return $migrated;
    }

    /** CONTRACT: odstranit starý sloupec. Až úplně nakonec. */
    public function contract(): void
    {
        $this->db->pdo->exec('ALTER TABLE orders DROP COLUMN shipped');
    }
}
