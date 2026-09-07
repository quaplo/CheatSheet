<?php

declare(strict_types=1);

namespace Before;

/**
 * PŘED: totéž SKU se validuje na třech místech — a pokaždé jinak.
 *
 * Každá z těch metod vznikla jindy a jiný člověk. Všechny tři jsou
 * „správné" podle toho, co tehdy autor potřeboval. Dohromady dávají
 * systém, ve kterém záleží na tom, kudy hodnota přišla.
 */
final class Validation
{
    /** Formulář v administraci — psaný jako první, nejvolnější. */
    public static function inAdminForm(string $sku): bool
    {
        return $sku !== '' && strlen($sku) <= 20;
    }

    /** Import z dodavatelského feedu — psaný jako druhý, přísnější. */
    public static function inImport(string $sku): bool
    {
        return preg_match('/^[A-Z]{3}-\d{2}$/', $sku) === 1;
    }

    /** Veřejné API — psané jako třetí, někde mezi. */
    public static function inApi(string $sku): bool
    {
        return preg_match('/^[A-Za-z]{2,4}-?\d+$/', $sku) === 1;
    }
}
