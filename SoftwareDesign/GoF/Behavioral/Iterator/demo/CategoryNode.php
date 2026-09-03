<?php

declare(strict_types=1);

/**
 * Strom kategorií — a jeho průchod.
 *
 * Ukazuje, proč Iterator a Composite chodí spolu: strom má tvar,
 * který volající nezná, a přesto ho potřebuje projít. Iterator
 * ten tvar schová.
 *
 * @implements IteratorAggregate<int, CategoryNode>
 */
final class CategoryNode implements IteratorAggregate
{
    /** @var list<CategoryNode> */
    private array $children = [];

    public function __construct(
        public readonly string $name,
    ) {
    }

    public function add(self $child): self
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * Průchod do hloubky, včetně sebe.
     *
     * `yield from` je rekurze v generátoru — a je to jeden z mála
     * míst, kde PHP nabízí něco elegantnějšího než většina jazyků.
     *
     * @return Generator<int, self>
     */
    public function getIterator(): Generator
    {
        yield $this;

        foreach ($this->children as $child) {
            yield from $child;
        }
    }
}
