<?php

declare(strict_types=1);

/** Kontrola před zpracováním — když neprojde, `$next` se nezavolá. */
final readonly class ValidateOrderMiddleware implements OrderMiddleware
{
    public function process(OrderRequest $request, callable $next): OrderResult
    {
        if ($request->itemCount < 1) {
            return OrderResult::rejected('Objednávka neobsahuje žádné položky.')
                ->withLogEntry('validace: ZAMÍTNUTO');
        }

        if ($request->totalInCents <= 0) {
            return OrderResult::rejected('Objednávka má nulovou hodnotu.')
                ->withLogEntry('validace: ZAMÍTNUTO');
        }

        return $next($request)->withLogEntry('validace: ok');
    }
}
