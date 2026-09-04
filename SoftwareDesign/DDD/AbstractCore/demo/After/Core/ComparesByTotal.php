<?php

declare(strict_types=1);

namespace After\Core;

/**
 * Sdílená implementace porovnání.
 *
 * Interakce mezi typy objednávek je popsaná JEDNOU, v jádru —
 * místo aby ji každý modul opakoval pro každý jiný typ.
 */
trait ComparesByTotal
{
    public function isLargerThan(Order $other): bool
    {
        return $this->totalInCents() > $other->totalInCents();
    }
}
