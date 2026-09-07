<?php

declare(strict_types=1);

/**
 * Jeden běh jedné větve — výsledek, doba a případná výjimka.
 */
final readonly class Observation
{
    public function __construct(
        public string $name,
        public ?int $value,
        public ?Throwable $error,
        public float $durationMs,
    ) {
    }

    public function failed(): bool
    {
        return $this->error !== null;
    }

    public function describe(): string
    {
        return $this->failed()
            ? 'výjimka: ' . $this->error->getMessage()
            : number_format(($this->value ?? 0) / 100, 2, ',', ' ') . ' Kč';
    }
}
