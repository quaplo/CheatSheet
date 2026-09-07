<?php

declare(strict_types=1);

namespace After;

/**
 * Období, ve kterém se něco stalo.
 *
 * Vznikl jako parametr, ale hned dostal dvě věci, které předtím
 * neměly kde být: kontrolu, že období dává smysl, a odpověď na
 * otázku „spadá tam tenhle okamžik?".
 */
final readonly class DateRange
{
    public function __construct(public int $fromTs, public int $toTs)
    {
        if ($fromTs > $toTs) {
            throw new \InvalidArgumentException(
                'Období začíná později, než končí: ' . $fromTs . ' > ' . $toTs,
            );
        }
    }

    public function includes(int $timestamp): bool
    {
        return $timestamp >= $this->fromTs && $timestamp <= $this->toTs;
    }

    public function days(): int
    {
        return (int) ceil(($this->toTs - $this->fromTs) / 86400);
    }
}
