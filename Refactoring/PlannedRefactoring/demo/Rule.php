<?php

declare(strict_types=1);

/**
 * Kolik míst v kódu zná pravidlo pro slevu.
 *
 * Jako otisk pravidla bereme jeho strop — číslo 12. Není to důkaz,
 * je to indicie: kdo zná strop, ten to pravidlo počítá.
 */
final class Rule
{
    /** @return list<string> soubory, ve kterých se pravidlo objevuje */
    public static function placesIn(string $dir): array
    {
        $found = [];

        foreach (glob($dir . '/*.php') as $file) {
            foreach (token_get_all(file_get_contents($file)) as $token) {
                if (is_array($token) && $token[0] === T_LNUMBER && $token[1] === '12') {
                    $found[] = basename($file);
                    continue 2;
                }
            }
        }

        sort($found);

        return $found;
    }
}
