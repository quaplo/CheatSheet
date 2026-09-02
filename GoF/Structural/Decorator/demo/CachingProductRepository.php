<?php

declare(strict_types=1);

/**
 * Dekorátor: cache.
 *
 * Drží obalený objekt, implementuje totéž rozhraní a **rozhoduje se,
 * jestli volání pustí dál**. To je celý pattern.
 *
 * Všimni si, co tu NENÍ: žádné `extends SqliteProductRepository`.
 * Dekorátor o konkrétní implementaci nic neví — obalí cokoli, co
 * splňuje ProductRepository, včetně jiného dekorátoru.
 */
final class CachingProductRepository implements ProductRepository
{
    /** @var array<string, ?string> */
    private array $cache = [];

    public int $hits = 0;
    public int $misses = 0;

    public function __construct(
        private readonly ProductRepository $inner,
    ) {
    }

    public function find(string $sku): ?string
    {
        if (array_key_exists($sku, $this->cache)) {
            $this->hits++;

            return $this->cache[$sku];
        }

        $this->misses++;

        return $this->cache[$sku] = $this->inner->find($sku);
    }
}
