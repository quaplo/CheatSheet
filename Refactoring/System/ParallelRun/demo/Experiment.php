<?php

declare(strict_types=1);

/**
 * PARALLEL RUN.
 *
 * Pustí obě implementace, porovná výsledky a vrátí ten z KONTROLY.
 * Kandidát nikdy neovlivní odpověď — jen se o něm sbírají data.
 *
 * Terminologie je z GitHub Scientist:
 *   control   … ověřený kód, jehož výsledek se vrací
 *   candidate … nový kód, který se zkouší
 *   publish   … co se stane s pozorováním
 *
 * Tři vlastnosti, na kterých všechno stojí:
 *   1. vrací se VŽDY kontrola
 *   2. výjimka v kandidátovi se zachytí, nikam nepropadne
 *   3. pořadí běhu se střídá, aby se odhalily časové závislosti
 */
final class Experiment
{
    /** @var list<array{order: Order, control: Observation, candidate: Observation}> */
    public array $mismatches = [];

    public int $runs = 0;
    public int $candidateRuns = 0;
    public int $candidateErrors = 0;
    public float $controlTotalMs = 0.0;
    public float $candidateTotalMs = 0.0;

    public function __construct(
        private readonly DiscountCalculator $control,
        private readonly DiscountCalculator $candidate,
        /** Na kolika procentech provozu se kandidát vůbec spouští. */
        private readonly int $samplePercent = 100,
        /** Rozdíly, které se nepočítají jako neshoda (haléřové zaokrouhlení). */
        private readonly int $toleranceInCents = 0,
    ) {
    }

    public function run(Order $order): int
    {
        ++$this->runs;

        if (!$this->shouldSample($order)) {
            return $this->control->discountInCents($order);
        }

        ++$this->candidateRuns;

        // Střídání pořadí: kdyby jedna větev zahřívala cache pro druhou,
        // vyšla by ta druhá vždycky rychlejší a nikdo by to nepoznal.
        $controlFirst = $this->candidateRuns % 2 === 1;

        if ($controlFirst) {
            $controlObs = $this->observe('kontrola', $this->control, $order);
            $candidateObs = $this->observe('kandidát', $this->candidate, $order);
        } else {
            $candidateObs = $this->observe('kandidát', $this->candidate, $order);
            $controlObs = $this->observe('kontrola', $this->control, $order);
        }

        $this->controlTotalMs += $controlObs->durationMs;
        $this->candidateTotalMs += $candidateObs->durationMs;

        if ($candidateObs->failed()) {
            ++$this->candidateErrors;
        }

        if (!$this->matches($controlObs, $candidateObs)) {
            $this->mismatches[] = [
                'order' => $order,
                'control' => $controlObs,
                'candidate' => $candidateObs,
            ];
        }

        // VŽDY kontrola. Ať se s kandidátem stalo cokoli.
        if ($controlObs->failed()) {
            throw $controlObs->error;
        }

        return $controlObs->value;
    }

    private function observe(string $name, DiscountCalculator $calculator, Order $order): Observation
    {
        $start = hrtime(true);

        try {
            $value = $calculator->discountInCents($order);
            $error = null;
        } catch (Throwable $e) {
            $value = null;
            $error = $e;
        }

        return new Observation($name, $value, $error, (hrtime(true) - $start) / 1_000_000);
    }

    private function matches(Observation $control, Observation $candidate): bool
    {
        if ($control->failed() || $candidate->failed()) {
            return $control->failed() && $candidate->failed();
        }

        return abs($control->value - $candidate->value) <= $this->toleranceInCents;
    }

    /** Deterministické vzorkování — tatáž objednávka se chová stejně. */
    private function shouldSample(Order $order): bool
    {
        if ($this->samplePercent >= 100) {
            return true;
        }

        if ($this->samplePercent <= 0) {
            return false;
        }

        return crc32($order->number) % 100 < $this->samplePercent;
    }
}
