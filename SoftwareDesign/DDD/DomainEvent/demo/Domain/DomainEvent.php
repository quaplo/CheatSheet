<?php

declare(strict_types=1);

namespace Domain;

/**
 * Doménová událost = FAKT, který se už stal.
 *
 * Rozdíl proti příkazu je celý v čase a v tom, jestli se dá odmítnout:
 *
 *   PlaceOrder  — rozkazovací způsob, žádost, může být zamítnuta
 *   OrderPlaced — minulý čas, konstatování, odmítnout nejde
 *
 * Proto je událost neměnná. Minulost se nepřepisuje.
 */
interface DomainEvent
{
    public function occurredAt(): \DateTimeImmutable;

    /** Identita agregátu, kterého se událost týká. */
    public function aggregateId(): string;
}
