<?php

declare(strict_types=1);

namespace Step2_Prepared;

/**
 * Pravidlo „nad určitou částku zdarma" vytažené na jedno místo.
 *
 * Ve výchozím stavu bylo napsané uvnitř jedné větve. Teď je to
 * samostatná věc, kterou může použít kdokoli.
 */
trait FreeOverThreshold
{
    private const int FREE_FROM = 250000;

    private function isFree(int $orderValueInCents): bool
    {
        return $orderValueInCents >= self::FREE_FROM;
    }
}
