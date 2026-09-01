<?php

declare(strict_types=1);

namespace Domain;

/** Výsledek převodu jako hodnota — co odešlo, co se strhlo, co přišlo. */
final readonly class TransferReceipt
{
    public function __construct(
        public int $debited,
        public int $fee,
        public int $credited,
    ) {
    }
}
