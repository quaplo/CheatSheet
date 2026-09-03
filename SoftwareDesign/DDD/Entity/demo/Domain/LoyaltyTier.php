<?php

declare(strict_types=1);

namespace Domain;

/** Úroveň věrnostního programu. */
enum LoyaltyTier: string
{
    case Bronze = 'bronz';
    case Silver = 'stříbro';
    case Gold = 'zlato';

    public static function forPoints(int $points): self
    {
        return match (true) {
            $points >= 5000 => self::Gold,
            $points >= 1000 => self::Silver,
            default => self::Bronze,
        };
    }

    public function discountPercent(): int
    {
        return match ($this) {
            self::Bronze => 0,
            self::Silver => 5,
            self::Gold => 10,
        };
    }
}
