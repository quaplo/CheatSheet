<?php

declare(strict_types=1);

/**
 * UZEL — obsahuje další uzly, kterými můžou být produkty i kategorie.
 *
 * Klíčové je, že drží `CatalogNode`, ne `Product`. Díky tomu se
 * strom může větvit do libovolné hloubky, aniž by o tom kategorie
 * musela vědět.
 *
 * A všimni si, jak vypadají operace: **rekurze bez jediné podmínky
 * o typu**. Kategorie se neptá, jestli je potomek list nebo uzel —
 * zeptá se ho na totéž, co by se zeptal kdokoli jiný.
 */
final class Category implements CatalogNode
{
    /** @var list<CatalogNode> */
    private array $children = [];

    public function __construct(
        private readonly string $name,
    ) {
    }

    public function add(CatalogNode $child): self
    {
        $this->children[] = $child;

        return $this;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function productCount(): int
    {
        return array_sum(array_map(
            static fn (CatalogNode $child): int => $child->productCount(),
            $this->children,
        ));
    }

    public function lowestPriceInCents(): ?int
    {
        $prices = array_filter(array_map(
            static fn (CatalogNode $child): ?int => $child->lowestPriceInCents(),
            $this->children,
        ), static fn (?int $price): bool => $price !== null);

        return $prices === [] ? null : min($prices);
    }

    public function render(int $depth = 0): string
    {
        $count = $this->productCount();

        $out = sprintf(
            "%s%s  (%d %s)\n",
            str_repeat('    ', $depth),
            mb_strtoupper($this->name),
            $count,
            match (true) {
                $count === 1 => 'produkt',
                $count < 5 => 'produkty',
                default => 'produktů',
            },
        );

        foreach ($this->children as $child) {
            $out .= $child->render($depth + 1);
        }

        return $out;
    }

    /** @return list<CatalogNode> */
    public function children(): array
    {
        return $this->children;
    }
}
