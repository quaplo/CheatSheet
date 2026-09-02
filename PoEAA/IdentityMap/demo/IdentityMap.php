<?php

declare(strict_types=1);

/**
 * Identity Map — jedna instance na jeden záznam.
 *
 * Celý pattern je asociativní pole. Těžké na něm není napsání,
 * ale rozhodnutí, jak dlouho má mapa žít a kdy se vyprazdňuje.
 */
final class IdentityMap
{
    /** @var array<string, array<string, object>> třída => id => objekt */
    private array $objects = [];

    public function get(string $class, string $id): ?object
    {
        return $this->objects[$class][$id] ?? null;
    }

    public function add(string $class, string $id, object $object): void
    {
        $this->objects[$class][$id] = $object;
    }

    public function remove(string $class, string $id): void
    {
        unset($this->objects[$class][$id]);
    }

    /** Vyprázdnění — nutné v dávkách, jinak mapa roste, dokud nedojde paměť. */
    public function clear(): void
    {
        $this->objects = [];
    }

    public function count(): int
    {
        return array_sum(array_map('count', $this->objects));
    }
}
