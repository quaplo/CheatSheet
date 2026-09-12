<?php

declare(strict_types=1);

/**
 * Jistič.
 *
 * Tři stavy: zavřeno (volá se), otevřeno (nevolá se vůbec)
 * a napůl otevřeno (pustí se jediný pokus).
 */
final class CircuitBreaker
{
    public const string CLOSED = 'zavřeno';
    public const string OPEN = 'otevřeno';
    public const string HALF_OPEN = 'napůl';

    private string $state = self::CLOSED;

    private int $failures = 0;

    private float $openedAt = 0.0;

    /** @var list<array{at: float, from: string, to: string}> */
    private array $transitions = [];

    public function __construct(
        private readonly int $failureThreshold,
        private readonly float $openSeconds,
    ) {
    }

    /**
     * @param callable(float): array{ok: bool, took: float} $call
     * @return array{ok: bool, took: float, skipped: bool}
     */
    public function call(callable $call, float $now): array
    {
        if ($this->state === self::OPEN) {
            if ($now - $this->openedAt < $this->openSeconds) {
                // Rychlé selhání — cizí služba se vůbec nevolá.
                return ['ok' => false, 'took' => 0.0, 'skipped' => true];
            }

            $this->moveTo(self::HALF_OPEN, $now);
        }

        $result = $call($now);

        if ($result['ok']) {
            $this->failures = 0;

            if ($this->state !== self::CLOSED) {
                $this->moveTo(self::CLOSED, $now);
            }
        } else {
            ++$this->failures;

            if ($this->state === self::HALF_OPEN || $this->failures >= $this->failureThreshold) {
                $this->openedAt = $now;
                $this->moveTo(self::OPEN, $now);
            }
        }

        return [...$result, 'skipped' => false];
    }

    public function state(): string
    {
        return $this->state;
    }

    /** @return list<array{at: float, from: string, to: string}> */
    public function transitions(): array
    {
        return $this->transitions;
    }

    private function moveTo(string $state, float $now): void
    {
        // Fowler: „any change in breaker state should be logged."
        $this->transitions[] = ['at' => $now, 'from' => $this->state, 'to' => $state];
        $this->state = $state;

        if ($state === self::CLOSED) {
            $this->failures = 0;
        }
    }
}
