<?php

declare(strict_types=1);

/**
 * Článek pipeline — druhá podoba téhož patternu.
 *
 * Rozdíl proti klasickému řetězu: článek dostane `$next` a sám se
 * rozhoduje, KDY ho zavolá. Může tedy pracovat před zpracováním,
 * po něm, kolem něj — nebo ho nezavolat vůbec a řetěz utnout.
 *
 * Přesně takhle fungují PSR-15 middleware.
 */
interface OrderMiddleware
{
    /**
     * @param callable(OrderRequest): OrderResult $next
     */
    public function process(OrderRequest $request, callable $next): OrderResult;
}
