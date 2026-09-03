<?php

declare(strict_types=1);

/** PDO, které počítá dotazy — kvůli měření v demu, ne kvůli patternu. */
final class CountingPdo extends PDO
{
    public int $queries = 0;

    public function reset(): void
    {
        $this->queries = 0;
    }

    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        $this->queries++;

        return parent::prepare($query, $options);
    }

    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false
    {
        $this->queries++;

        return parent::query($query, $fetchMode, ...$fetchModeArgs);
    }
}
