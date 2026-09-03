<?php

declare(strict_types=1);

namespace Saga;

/**
 * STAV PROCESU — to, co z orchestrátoru dělá Process Manager.
 *
 * Bez tohohle objektu je saga jen `try`/`catch`. S ním je to věc,
 * která ví, kde je, dá se uložit, obnovit po pádu procesu a zobrazit
 * na supportním dashboardu.
 *
 * Zároveň je to důvod, proč ten proces bývá vlastní bounded context:
 * má identitu, stav a životní cyklus. To jsou vlastnosti agregátu.
 */
final class SagaState
{
    /** @var array<string, string> výstupy dokončených kroků */
    private array $results = [];

    /** @var list<string> */
    public array $completedSteps = [];

    public string $status = 'běží';

    public function __construct(
        public readonly string $orderId,
        public readonly string $sku,
        public readonly int $quantity,
        public readonly int $totalInCents,
    ) {
    }

    public function remember(string $step, string $result): void
    {
        $this->results[$step] = $result;
        $this->completedSteps[] = $step;
    }

    public function resultOf(string $step): string
    {
        return $this->results[$step] ?? throw new \LogicException(sprintf('Krok %s neproběhl.', $step));
    }

    public function hasCompleted(string $step): bool
    {
        return isset($this->results[$step]);
    }
}
