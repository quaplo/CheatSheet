<?php

declare(strict_types=1);

namespace Packing;

use BoxSize;
use PackableItem;

/**
 * Implementace mechanismu: heuristika „first fit decreasing".
 *
 * Tohle je ta „mechanistická" část, kterou Evans popisuje jako
 * zaplavující doménu. Je jí hodně, je netriviální a s obchodními
 * pravidly nemá nic společného — proto žije zvlášť.
 *
 * Že je to heuristika a ne optimum, je vlastnost algoritmu.
 * Bin packing je NP-těžký; pro e-shop stačí dobré řešení hned.
 */
final class FirstFitDecreasingPacker implements Packer
{
    public function pack(array $items, array $availableBoxes): PackingPlan
    {
        if ($items === []) {
            return new PackingPlan([]);
        }

        if ($availableBoxes === []) {
            throw new \InvalidArgumentException('Není k dispozici žádná velikost krabice.');
        }

        // Od největší položky — menší se pak lépe dosypou do zbytků.
        usort(
            $items,
            static fn (PackableItem $a, PackableItem $b): int
                => $b->volumeInMillilitres <=> $a->volumeInMillilitres,
        );

        // Krabice od nejmenší, ať se nesahá po velké zbytečně.
        usort(
            $availableBoxes,
            static fn (BoxSize $a, BoxSize $b): int
                => $a->capacityInMillilitres <=> $b->capacityInMillilitres,
        );

        $largest = $availableBoxes[count($availableBoxes) - 1];

        /** @var list<array{size: BoxSize, items: list<PackableItem>, free: int}> $open */
        $open = [];

        foreach ($items as $item) {
            if ($item->volumeInMillilitres > $largest->capacityInMillilitres) {
                throw new \InvalidArgumentException(
                    sprintf('Položka %s se nevejde ani do největší krabice.', $item->sku),
                );
            }

            $placed = false;

            // First fit: první otevřená krabice, kam se to vejde.
            foreach ($open as $index => $box) {
                if ($box['free'] >= $item->volumeInMillilitres) {
                    $open[$index]['items'][] = $item;
                    $open[$index]['free'] -= $item->volumeInMillilitres;
                    $placed = true;
                    break;
                }
            }

            if ($placed) {
                continue;
            }

            // Nová krabice — nejmenší, do které se položka vejde.
            $chosen = null;

            foreach ($availableBoxes as $candidate) {
                if ($candidate->capacityInMillilitres >= $item->volumeInMillilitres) {
                    $chosen = $candidate;
                    break;
                }
            }

            $open[] = [
                'size' => $chosen,
                'items' => [$item],
                'free' => $chosen->capacityInMillilitres - $item->volumeInMillilitres,
            ];
        }

        return new PackingPlan(array_map(
            static fn (array $box): PackedBox => new PackedBox($box['size'], $box['items']),
            $open,
        ));
    }
}
