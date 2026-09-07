<?php

declare(strict_types=1);

/**
 * NOVÝ systém.
 *
 * Začíná prázdný a schopnosti mu přibývají po jedné. Právě tohle je
 * ta „fíkovnice": roste kolem starého systému, dokud ho nenahradí.
 */
final class NewSystem implements System
{
    /** @var list<string> */
    private array $capabilities = [];

    public function handle(Request $request): Response
    {
        return new Response(
            sprintf('[nový] %s', $request->path),
            'nový',
        );
    }

    public function capabilities(): array
    {
        return $this->capabilities;
    }

    public function implement(string $capability): void
    {
        if (!in_array($capability, $this->capabilities, true)) {
            $this->capabilities[] = $capability;
        }
    }
}
