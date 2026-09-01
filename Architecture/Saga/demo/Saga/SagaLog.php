<?php

declare(strict_types=1);

namespace Saga;

/**
 * Uložený stav ság. Zastupuje databázovou tabulku.
 *
 * Tohle je ten rozdíl mezi „synchronní kompenzací“ a ságou, na kterou
 * se dá spolehnout: bez uloženého stavu žije informace o rozdělané
 * práci jen v paměti běžícího procesu. Když ten proces zemře, zemře
 * s ním.
 */
final class SagaLog
{
    /** @var array<string, SagaState> */
    private array $records = [];

    public function save(SagaState $state): void
    {
        $this->records[$state->orderId] = $state;
    }

    /** @return list<SagaState> ságy, které nikdo nedokončil */
    public function unfinished(): array
    {
        return array_values(array_filter(
            $this->records,
            static fn (SagaState $s): bool => $s->status === 'běží',
        ));
    }

    /** @return list<SagaState> */
    public function all(): array
    {
        return array_values($this->records);
    }
}
