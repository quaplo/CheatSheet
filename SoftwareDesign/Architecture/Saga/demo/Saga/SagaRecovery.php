<?php

declare(strict_types=1);

namespace Saga;

/**
 * Dokončení ság, které někdo nechal rozdělané.
 *
 * Běží jako cron nebo worker: najde ságy ve stavu „běží“, které tam
 * visí déle, než je zdrávo, a dojede jejich kompenzace.
 *
 * Bez téhle části je synchronní sága jen dobrý úmysl — funguje,
 * dokud proces doběhne.
 */
final readonly class SagaRecovery
{
    /** @param list<SagaStep> $steps */
    public function __construct(
        private array $steps,
        private SagaLog $log,
    ) {
    }

    /** @return list<string> id ság, které se podařilo uklidit */
    public function recover(): array
    {
        $recovered = [];

        foreach ($this->log->unfinished() as $state) {
            // Kompenzuj pozpátku jen ty kroky, které podle záznamu proběhly.
            foreach (array_reverse($this->steps) as $step) {
                if ($state->hasCompleted($step->name())) {
                    $step->compensate($state);
                }
            }

            $state->status = 'kompenzovaná při obnově';
            $this->log->save($state);
            $recovered[] = $state->orderId;
        }

        return $recovered;
    }
}
