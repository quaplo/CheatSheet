<?php

declare(strict_types=1);

/**
 * ČTECÍ strana. Doménu úplně obchází.
 *
 * Žádné repository, žádný agregát, žádné mapování na entity — jeden
 * dotaz, který vrátí přesně to, co obrazovka potřebuje. Řazení,
 * stránkování i agregace dělá databáze, protože v tom je dobrá.
 *
 * Pravidla, která hlídá Order, tady nikoho nezajímají: čtení nic
 * nemění, takže nemá co porušit. To je celé odůvodnění, proč je
 * v pořádku sáhnout na SQL napřímo.
 */
final readonly class OrderListQuery
{
    public function __construct(
        private PDO $connection,
    ) {
    }

    /** @return list<OrderListItem> */
    public function recent(int $limit, int $offset = 0): array
    {
        $statement = $this->connection->prepare(
            'SELECT
                 o.id,
                 o.customer_email,
                 o.status,
                 o.placed_at,
                 COUNT(i.id)                                        AS item_count,
                 COALESCE(SUM(i.unit_price_cents * i.quantity), 0)  AS total_cents
             FROM orders o
             LEFT JOIN order_items i ON i.order_id = o.id
             GROUP BY o.id
             ORDER BY o.placed_at DESC
             LIMIT :limit OFFSET :offset',
        );

        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return array_map(
            static fn (array $row): OrderListItem => new OrderListItem(
                $row['id'],
                $row['customer_email'],
                $row['status'],
                $row['placed_at'],
                (int) $row['item_count'],
                (int) $row['total_cents'],
            ),
            $statement->fetchAll(PDO::FETCH_ASSOC),
        );
    }

    /** Souhrn pro dashboard — přes agregáty by to znamenalo načíst všechno. */
    public function totalRevenue(): int
    {
        return (int) $this->connection
            ->query('SELECT COALESCE(SUM(unit_price_cents * quantity), 0) FROM order_items')
            ->fetchColumn();
    }
}
