<?php

declare(strict_types=1);

/**
 * Pozorovatel. Chce vědět, že se něco změnilo.
 *
 * Klíčové je, co tu NENÍ: pozorovatel nezná ostatní pozorovatele
 * a subjekt nezná žádného z nich konkrétně. Zná jen tohle rozhraní.
 */
interface StockObserver
{
    public function stockChanged(StockItem $item, int $previousQuantity): void;
}
