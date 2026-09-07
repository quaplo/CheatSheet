<?php

declare(strict_types=1);

/**
 * STARÝ systém.
 *
 * Umí všechno, nikdo mu nerozumí a nikdo se ho nechce dotknout.
 * Schopnosti z něj postupně ubývají — ne tím, že by se mazaly,
 * ale tím, že je fasáda přestane směrovat sem.
 */
final class LegacyMonolith implements System
{
    /** @var list<string> */
    private array $capabilities;

    /** @param list<string> $capabilities */
    public function __construct(array $capabilities)
    {
        $this->capabilities = $capabilities;
    }

    public function handle(Request $request): Response
    {
        return new Response(
            sprintf('[legacy] %s', $request->path),
            'legacy',
        );
    }

    public function capabilities(): array
    {
        return $this->capabilities;
    }

    /** Schopnost se ze starého systému odstraní až úplně nakonec. */
    public function retire(string $capability): void
    {
        $this->capabilities = array_values(
            array_filter($this->capabilities, static fn (string $c): bool => $c !== $capability),
        );
    }
}
