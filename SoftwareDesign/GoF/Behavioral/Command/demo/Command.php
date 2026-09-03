<?php

declare(strict_types=1);

/**
 * Operace zabalená do objektu.
 *
 * Klíčové je, že rozhraní nemá parametry — všechno, co operace
 * potřebuje, si nese uvnitř. Proto jde předat dál, uložit do fronty
 * nebo zařadit do historie.
 */
interface Command
{
    public function execute(): void;

    /** Krátký popis do historie a do logu. */
    public function describe(): string;
}
