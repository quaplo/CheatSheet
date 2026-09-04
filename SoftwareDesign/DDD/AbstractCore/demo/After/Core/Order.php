<?php

declare(strict_types=1);

namespace After\Core;

/**
 * ABSTRAKTNÍ JÁDRO.
 *
 * Evans: „Identify the most fundamental differentiating concepts in
 * the model and factor them into distinct classes, abstract classes,
 * or interfaces. Design this abstract model so that it expresses
 * MOST OF THE INTERACTION between significant components."
 *
 * Tohle rozhraní je celý model objednávky, pokud jde o to, jak spolu
 * typy objednávek mluví. Detaily každého typu zůstávají v jeho modulu.
 */
interface Order
{
    public function number(): string;

    public function totalInCents(): int;

    public function isLargerThan(self $other): bool;
}
