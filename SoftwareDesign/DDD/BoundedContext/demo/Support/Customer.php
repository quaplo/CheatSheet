<?php

declare(strict_types=1);

namespace Support;

use Shared\CustomerId;

/**
 * Zákazník očima PODPORY.
 *
 * Tady „zákazník“ znamená ten, kdo píše ticket. Zajímá nás, jak ho
 * oslovit, kam odpovědět a jakou má úroveň podpory.
 *
 * Úvěrový limit? Pravděpodobnost obchodu? Podpora by je ignorovala,
 * i kdyby je měla.
 */
final readonly class Customer
{
    public function __construct(
        public CustomerId $id,
        public string $displayName,
        public string $email,
        public string $supportTier,
        public int $openTickets,
    ) {
    }

    /** Lhůta na odpověď — pojem, který existuje jen tady. */
    public function responseDeadlineHours(): int
    {
        return match ($this->supportTier) {
            'platinum' => 1,
            'gold' => 4,
            default => 24,
        };
    }
}
