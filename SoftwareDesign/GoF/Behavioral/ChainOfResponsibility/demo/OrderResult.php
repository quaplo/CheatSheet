<?php

declare(strict_types=1);

/** Výsledek zpracování. */
final readonly class OrderResult
{
    /** @param list<string> $log */
    private function __construct(
        public bool $isAccepted,
        public string $message,
        public array $log,
    ) {
    }

    public static function accepted(string $orderNumber): self
    {
        return new self(true, sprintf('Objednávka %s přijata.', $orderNumber), []);
    }

    public static function rejected(string $reason): self
    {
        return new self(false, $reason, []);
    }

    public function withLogEntry(string $entry): self
    {
        return new self($this->isAccepted, $this->message, [$entry, ...$this->log]);
    }
}
