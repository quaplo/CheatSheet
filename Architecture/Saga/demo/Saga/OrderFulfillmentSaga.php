<?php

declare(strict_types=1);

namespace Saga;

/**
 * ORCHESTROVANÁ SÁGA.
 *
 * Jeden dirigent, který volá kroky v pořadí a při selhání spustí
 * kompenzace POZPÁTKU. Kroky o sobě navzájem nevědí a nevědí ani
 * o dirigentovi — mluví jen se svým kontextem.
 *
 * Alternativa je choreografie: žádný dirigent, každý kontext reaguje
 * na události ostatních. Rozdíl je rozebraný v README.
 */
final readonly class OrderFulfillmentSaga
{
    /** @param list<SagaStep> $steps */
    public function __construct(
        private array $steps,
    ) {
    }

    public function run(SagaState $state): SagaOutcome
    {
        $completed = [];

        foreach ($this->steps as $step) {
            try {
                $step->execute($state);
                $completed[] = $step;
            } catch (\RuntimeException $e) {
                // Za pivotním krokem už zpět nejde — jen dopředu.
                if ($this->passedPivot($completed)) {
                    $state->status = 'zaseknutá — nutný ruční zásah';

                    return SagaOutcome::stuck($step->name(), $e->getMessage());
                }

                $compensated = $this->compensate($completed, $state);
                $state->status = 'kompenzovaná';

                return SagaOutcome::compensated($step->name(), $e->getMessage(), $compensated);
            }
        }

        $state->status = 'dokončená';

        return SagaOutcome::completed();
    }

    /**
     * Kompenzace běží POZPÁTKU — poslední dokončený krok se ruší první.
     *
     * @param list<SagaStep> $completed
     *
     * @return list<string>
     */
    private function compensate(array $completed, SagaState $state): array
    {
        $names = [];

        foreach (array_reverse($completed) as $step) {
            $step->compensate($state);
            $names[] = $step->name();
        }

        return $names;
    }

    /** @param list<SagaStep> $completed */
    private function passedPivot(array $completed): bool
    {
        foreach ($completed as $step) {
            if ($step->isPivot()) {
                return true;
            }
        }

        return false;
    }
}
