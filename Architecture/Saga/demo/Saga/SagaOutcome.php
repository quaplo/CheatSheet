<?php

declare(strict_types=1);

namespace Saga;

final readonly class SagaOutcome
{
    /** @param list<string> $compensated */
    private function __construct(
        public bool $isSuccess,
        public ?string $failedStep,
        public ?string $reason,
        public array $compensated,
    ) {
    }

    public static function completed(): self
    {
        return new self(true, null, null, []);
    }

    /** @param list<string> $compensated */
    public static function compensated(string $failedStep, string $reason, array $compensated): self
    {
        return new self(false, $failedStep, $reason, $compensated);
    }

    public static function stuck(string $failedStep, string $reason): self
    {
        return new self(false, $failedStep, $reason, []);
    }
}
