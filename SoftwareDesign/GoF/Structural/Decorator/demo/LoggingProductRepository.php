<?php

declare(strict_types=1);

/** Dekorátor: záznam volání. Vždycky pustí dál — jen si zapíše, že se to stalo. */
final class LoggingProductRepository implements ProductRepository
{
    /** @var list<string> */
    public array $log = [];

    public function __construct(
        private readonly ProductRepository $inner,
    ) {
    }

    public function find(string $sku): ?string
    {
        $this->log[] = $sku;

        return $this->inner->find($sku);
    }
}
