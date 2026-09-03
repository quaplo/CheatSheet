<?php

declare(strict_types=1);

/** Další kontrola. Pořadí článků je součástí chování, ne detail. */
final readonly class CheckStockMiddleware implements OrderMiddleware
{
    public function process(OrderRequest $request, callable $next): OrderResult
    {
        if ($request->inStock === false) {
            return OrderResult::rejected('Zboží není skladem.')
                ->withLogEntry('sklad: ZAMÍTNUTO');
        }

        return $next($request)->withLogEntry('sklad: ok');
    }
}
