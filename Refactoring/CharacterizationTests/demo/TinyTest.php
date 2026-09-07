<?php

declare(strict_types=1);

/**
 * Minimální testovací nástroj, aby demo běželo bez závislostí.
 *
 * V reálném projektu je to PHPUnit; princip je stejný.
 */
final class TinyTest
{
    private int $passed = 0;

    /** @var list<string> */
    private array $failures = [];

    public function assertSame(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected === $actual) {
            ++$this->passed;

            return;
        }

        $this->failures[] = sprintf(
            '%s — očekáváno %s, dostal jsem %s',
            $message,
            var_export($expected, true),
            var_export($actual, true),
        );
    }

    public function passed(): int
    {
        return $this->passed;
    }

    /** @return list<string> */
    public function failures(): array
    {
        return $this->failures;
    }

    public function reset(): void
    {
        $this->passed = 0;
        $this->failures = [];
    }
}
