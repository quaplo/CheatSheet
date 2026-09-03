<?php

declare(strict_types=1);

/**
 * Dekorátor: měření času.
 *
 * Ukazuje, že dekorátor může pracovat i PO návratu z obaleného
 * objektu — na rozdíl od klasického řetězu odpovědnosti, který
 * předá dál a skončí.
 */
final class TimingProductRepository implements ProductRepository
{
    /** @var list<float> */
    public array $durations = [];

    public function __construct(
        private readonly ProductRepository $inner,
    ) {
    }

    public function find(string $sku): ?string
    {
        $startedAt = hrtime(true);

        $result = $this->inner->find($sku);

        $this->durations[] = (hrtime(true) - $startedAt) / 1e6;

        return $result;
    }
}
