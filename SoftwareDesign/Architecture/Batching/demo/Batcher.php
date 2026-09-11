<?php

declare(strict_types=1);

/**
 * Sběrač, který drží zprávy a vyprázdní se, když nastane
 * kterákoli ze dvou podmínek — naplní se dávka, nebo vyprší čas.
 *
 * Čas se předává zvenčí. Není to kvůli testovatelnosti jen tak:
 * bez toho by se chování téhle třídy nedalo ověřit jinak než
 * čekáním, a to není test, to je loterie.
 */
final class Batcher
{
    /** @var list<string> */
    private array $buffer = [];

    private ?float $firstAddedAt = null;

    /** @var list<array{size: int, reason: string, at: float, waited: float}> */
    private array $flushes = [];

    /** @param callable(list<string>): void $flush */
    public function __construct(
        private readonly int $maxSize,
        private readonly float $maxSeconds,
        private $flush,
    ) {
    }

    public function add(string $message, float $now): void
    {
        $this->firstAddedAt ??= $now;
        $this->buffer[] = $message;

        if (count($this->buffer) >= $this->maxSize) {
            $this->doFlush($now, 'velikost');
        }
    }

    /** Časová smyčka. Volá se pravidelně, ať se něco stalo, nebo ne. */
    public function tick(float $now): void
    {
        if ($this->buffer === [] || $this->firstAddedAt === null) {
            return;
        }

        if ($now - $this->firstAddedAt >= $this->maxSeconds) {
            $this->doFlush($now, 'čas');
        }
    }

    /** Vyprázdnění při ukončení. Bez tohohle volání se zbytek ztratí. */
    public function shutdown(float $now): void
    {
        if ($this->buffer !== []) {
            $this->doFlush($now, 'ukončení');
        }
    }

    public function pending(): int
    {
        return count($this->buffer);
    }

    /** @return list<array{size: int, reason: string, at: float, waited: float}> */
    public function flushes(): array
    {
        return $this->flushes;
    }

    private function doFlush(float $now, string $reason): void
    {
        ($this->flush)($this->buffer);

        $this->flushes[] = [
            'size' => count($this->buffer),
            'reason' => $reason,
            'at' => $now,
            'waited' => $now - ($this->firstAddedAt ?? $now),
        ];
        $this->buffer = [];
        $this->firstAddedAt = null;
    }
}
