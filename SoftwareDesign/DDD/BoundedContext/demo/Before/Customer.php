<?php

declare(strict_types=1);

namespace Before;

/**
 * ANTI-PŘÍKLAD: jeden model pro celou firmu.
 *
 * Takhle to dopadne, když se odmítne, že „zákazník“ může znamenat víc
 * věcí. Každé oddělení si prosadí svoje pole, a protože pro ostatní
 * nedávají smysl, musí být nullable.
 *
 * Výsledek: třída, ve které v žádném konkrétním okamžiku nedávají
 * smysl všechna pole — a přesto ji musí znát a udržovat všichni.
 * Změna kvůli fakturaci rozbije podporu.
 */
final class Customer
{
    public function __construct(
        public string $id,
        public string $name,

        // Obchod
        public ?string $contactPerson = null,
        public ?int $dealValueInCents = null,
        public ?int $probabilityPercent = null,
        public ?string $accountManager = null,

        // Fakturace
        public ?string $legalName = null,
        public ?string $vatId = null,
        public ?string $billingAddress = null,
        public ?int $paymentTermDays = null,
        public ?int $creditLimitInCents = null,

        // Podpora
        public ?string $email = null,
        public ?string $supportTier = null,
        public ?int $openTickets = null,

        // A tohle už nikdo neví, odkud se vzalo
        public ?string $type = null,
        public ?bool $isActive = null,
    ) {
    }
}
