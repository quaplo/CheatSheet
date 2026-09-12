<?php

declare(strict_types=1);

/**
 * Tři způsoby, jak spočítat prodlevu před dalším pokusem.
 *
 * Náhoda je zaseknutá na pevné semínko, aby demo dávalo pokaždé
 * totéž. V produkci samozřejmě náhodná být musí — o to tu jde.
 */
final class Strategies
{
    public const float BASE_SECONDS = 1.0;
    public const float CAP_SECONDS = 30.0;

    /** Pevná prodleva. Nejjednodušší a nejhorší. */
    public static function fixed(int $attempt, callable $rand): float
    {
        return self::BASE_SECONDS;
    }

    /** Exponenciální. Lepší, ale pořád všichni naráz. */
    public static function exponential(int $attempt, callable $rand): float
    {
        return min(self::CAP_SECONDS, self::BASE_SECONDS * 2 ** ($attempt - 1));
    }

    /** Full jitter podle Marca Brookera: náhodně kdekoli mezi nulou a stropem. */
    public static function fullJitter(int $attempt, callable $rand): float
    {
        return $rand() * self::exponential($attempt, $rand);
    }
}
