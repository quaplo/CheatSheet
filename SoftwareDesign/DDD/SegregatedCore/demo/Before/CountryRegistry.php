<?php

declare(strict_types=1);

namespace Before;

final class CountryRegistry
{
    /** @var array<string, array{name: string, eu: bool}> */
    private array $countries = [
        'CZ' => ['name' => 'Česko', 'eu' => true],
        'SK' => ['name' => 'Slovensko', 'eu' => true],
        'US' => ['name' => 'Spojené státy', 'eu' => false],
    ];

    public function nameOf(string $code): string
    {
        return $this->countries[$code]['name'] ?? $code;
    }

    public function isEu(string $code): bool
    {
        return $this->countries[$code]['eu'] ?? false;
    }
}
