<?php

declare(strict_types=1);

/**
 * Cizí služba, která spadne a po chvíli se vrátí.
 *
 * Podstatné je, jak selhává: ne odmítnutím, ale TICHEM. Volání
 * doběhne až na timeout, takže selhání není rychlé — je pomalé,
 * a to je celý problém.
 */
final class FlakyGateway
{
    public const float TIMEOUT_SECONDS = 5.0;

    private int $calls = 0;

    private float $timeSpent = 0.0;

    public function __construct(
        private readonly float $brokenFrom,
        private readonly float $brokenUntil,
    ) {
    }

    /** @return array{ok: bool, took: float} */
    public function charge(float $now): array
    {
        ++$this->calls;

        if ($now >= $this->brokenFrom && $now < $this->brokenUntil) {
            $this->timeSpent += self::TIMEOUT_SECONDS;

            return ['ok' => false, 'took' => self::TIMEOUT_SECONDS];
        }

        $this->timeSpent += 0.05;

        return ['ok' => true, 'took' => 0.05];
    }

    public function calls(): int
    {
        return $this->calls;
    }

    public function timeSpent(): float
    {
        return $this->timeSpent;
    }
}
