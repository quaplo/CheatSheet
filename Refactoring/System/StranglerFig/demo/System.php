<?php

declare(strict_types=1);

/**
 * Rozhraní, které splňuje starý i nový systém.
 *
 * Fasáda mluví jen s ním. Kdyby ho nebylo, musela by znát obojí —
 * a přesunout schopnost by znamenalo sáhnout do fasády i do klienta.
 */
interface System
{
    public function handle(Request $request): Response;

    /** @return list<string> co tenhle systém umí */
    public function capabilities(): array;
}
