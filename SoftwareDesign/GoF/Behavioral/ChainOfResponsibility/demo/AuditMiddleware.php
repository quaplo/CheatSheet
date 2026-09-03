<?php

declare(strict_types=1);

/**
 * Článek, který obaluje zpracování z obou stran.
 *
 * Tohle klasický řetěz neumí — ten předá dál a skončí. Middleware se
 * dostane ke slovu i po návratu, takže umí měřit čas, logovat výsledek
 * nebo zabalit vše do transakce.
 */
final readonly class AuditMiddleware implements OrderMiddleware
{
    public function process(OrderRequest $request, callable $next): OrderResult
    {
        $startedAt = hrtime(true);

        $result = $next($request);

        $elapsedMs = (hrtime(true) - $startedAt) / 1e6;

        return $result->withLogEntry(sprintf(
            'audit: %s za %.2f ms',
            $result->isAccepted ? 'přijato' : 'zamítnuto',
            $elapsedMs,
        ));
    }
}
