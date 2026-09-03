<?php

declare(strict_types=1);

/**
 * Zdroj velkého množství dat — dvěma způsoby.
 *
 * Rozdíl mezi nimi je celý smysl generátorů: první způsob musí mít
 * všechno v paměti naráz, druhý drží vždycky jen jednu položku.
 */
final class LargeCatalog
{
    /**
     * Všechno naráz do pole.
     *
     * @return list<array{sku: string, price: int}>
     */
    public static function asArray(int $count): array
    {
        $items = [];

        for ($i = 0; $i < $count; $i++) {
            $items[] = ['sku' => 'SKU-' . $i, 'price' => 10000 + $i];
        }

        return $items;
    }

    /**
     * Po jedné, na požádání.
     *
     * @return Generator<int, array{sku: string, price: int}>
     */
    public static function asGenerator(int $count): Generator
    {
        for ($i = 0; $i < $count; $i++) {
            yield ['sku' => 'SKU-' . $i, 'price' => 10000 + $i];
        }
    }

    /**
     * Nekonečná řada. Bez líného vyhodnocení by tohle nešlo napsat.
     *
     * @return Generator<int, int>
     */
    public static function infinitePrices(): Generator
    {
        $price = 10000;

        while (true) {
            yield $price;
            $price += 500;
        }
    }
}
