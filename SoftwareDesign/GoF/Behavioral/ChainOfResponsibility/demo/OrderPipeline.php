<?php

declare(strict_types=1);

/**
 * Poskládá middleware do jedné vnořené funkce.
 *
 * Prochází se odzadu: poslední článek dostane jako `$next` cílovou
 * akci, předposlední dostane ten poslední a tak dál. Výsledkem je
 * cibule, do které se vstoupí zvenčí.
 */
final readonly class OrderPipeline
{
    /** @param list<OrderMiddleware> $middleware */
    public function __construct(
        private array $middleware,
    ) {
    }

    public function process(OrderRequest $request): OrderResult
    {
        // Jádro cibule: co se stane, když všechny články pustí dál.
        $next = static fn (OrderRequest $r): OrderResult => OrderResult::accepted($r->orderNumber);

        foreach (array_reverse($this->middleware) as $layer) {
            $inner = $next;
            $next = static fn (OrderRequest $r): OrderResult => $layer->process($r, $inner);
        }

        return $next($request);
    }
}
