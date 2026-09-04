<?php

declare(strict_types=1);

namespace Before;

/**
 * PŘED: kód mluví jinak než doména.
 *
 * Evans: „The terminology of day-to-day discussions is disconnected
 * from the terminology embedded in the code."
 *
 * Doména zná „storno". Kód pro totéž používá tři různá slova
 * a ani jedno z nich není to, které řekne doménový expert.
 */
final class OrderService
{
    public function abortOrder(string $id): void
    {
    }

    public function revokeOrder(string $id): void
    {
    }

    public function setStatusToInactive(string $id): void
    {
    }

    /** Expedice se v kódu jmenuje „processing" — pojem, který doména nezná. */
    public function processOrder(string $id): void
    {
    }

    /** Reklamace se schovala za obecné „issue". */
    public function createIssue(string $id, string $reason): void
    {
    }

    /** Dobropis vůbec nemá jméno — je to „negative invoice". */
    public function createNegativeInvoice(string $id): void
    {
    }

    /** Rezervace se jmenuje „lock" — technický pojem místo doménového. */
    public function lockStock(string $sku, int $quantity): void
    {
    }
}
