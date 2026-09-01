<?php

declare(strict_types=1);

/**
 * Země. Kromě identifikace nese i pravidlo pro formát PSČ — znalost, která
 * k zemi patří a nikde jinde by se opakovat neměla.
 */
enum Country: string
{
    case CZ = 'CZ';
    case SK = 'SK';

    public function label(): string
    {
        return match ($this) {
            self::CZ => 'Česká republika',
            self::SK => 'Slovensko',
        };
    }

    /** Regulární výraz pro PSČ bez mezer. */
    public function postalCodePattern(): string
    {
        return match ($this) {
            self::CZ, self::SK => '/^\d{5}$/',
        };
    }
}
