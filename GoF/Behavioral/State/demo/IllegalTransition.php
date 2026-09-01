<?php

declare(strict_types=1);

/**
 * Neplatný přechod je chyba, ne tiché nic.
 *
 * Hláška schválně obsahuje stav i operaci — u stavového automatu je
 * nejčastější otázka „proč to nešlo?“ a odpověď musí být ve výjimce,
 * ne v debuggeru.
 */
final class IllegalTransition extends LogicException
{
    public static function from(OrderState $state, string $operation): self
    {
        return new self(sprintf(
            'Objednávku ve stavu „%s“ nelze %s. Možné operace: %s.',
            $state->name(),
            $operation,
            implode(', ', $state->allowedOperations()) ?: 'žádné, je to koncový stav',
        ));
    }
}
