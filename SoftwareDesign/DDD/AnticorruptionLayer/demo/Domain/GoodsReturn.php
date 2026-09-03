<?php

declare(strict_types=1);

namespace Domain;

/**
 * Vratka.
 *
 * V ERP je to táž věta se záporným množstvím. U nás je to **jiný pojem**
 * s jiným chováním — a právě tohle je rozdíl mezi mapperem a antikorupční
 * vrstvou. Mapper by převedl `-15` na `-15`. Překlad pozná, že jde
 * o něco jiného.
 */
final readonly class GoodsReturn
{
    public function __construct(
        public string $number,
        public SupplierId $supplierId,
        public string $supplierName,
        public int $quantity,
        public int $creditedInCents,
        public \DateTimeImmutable $returnedOn,
    ) {
    }
}
