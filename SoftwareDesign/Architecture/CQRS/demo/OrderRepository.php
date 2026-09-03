<?php

declare(strict_types=1);

/**
 * Repository ZÁPISOVÉ strany.
 *
 * Všimni si, co tu není: žádná metoda pro výpis, žádné stránkování,
 * žádné řazení. Repository slouží k načtení agregátu kvůli změně —
 * ne k plnění tabulky. Právě tenhle seznam metod je to, co v běžném
 * projektu naroste do čtyřiceti a rozbije se.
 */
interface OrderRepository
{
    public function nextIdentity(): string;

    public function save(Order $order): void;

    public function get(string $id): Order;
}
