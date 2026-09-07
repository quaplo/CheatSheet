<?php

declare(strict_types=1);

/**
 * STARÁ verze aplikace — zná jen sloupec `shipped`.
 *
 * Během postupného nasazování běží vedle nové. Kdyby migrace
 * proběhla naráz, tahle verze by v tu chvíli spadla — a to je
 * přesně důvod, proč se dělí na fáze.
 */
final class OldApp
{
    public function __construct(private readonly Database $db)
    {
    }

    public function place(string $number): void
    {
        $this->db->pdo
            ->prepare('INSERT INTO orders (number, shipped) VALUES (?, 0)')
            ->execute([$number]);
    }

    public function markShipped(string $number): void
    {
        $this->db->pdo
            ->prepare('UPDATE orders SET shipped = 1 WHERE number = ?')
            ->execute([$number]);
    }

    public function isShipped(string $number): bool
    {
        $stmt = $this->db->pdo->prepare('SELECT shipped FROM orders WHERE number = ?');
        $stmt->execute([$number]);

        return (bool) $stmt->fetchColumn();
    }
}
