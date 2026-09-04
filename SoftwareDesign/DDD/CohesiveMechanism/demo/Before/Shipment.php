<?php

declare(strict_types=1);

namespace Before;

use BoxSize;
use PackableItem;

/**
 * PŘED: doména s algoritmem uvnitř.
 *
 * Přesně to, co Evans popisuje: „The conceptual 'what' is swamped
 * by the mechanistic 'how'." Metody, které vyjadřují problém, se
 * ztrácejí mezi metodami, které řeší algoritmus.
 *
 * Zkus v téhle třídě najít odpověď na otázku „smí se zásilka odeslat?".
 */
final class Shipment
{
    /** @var list<PackableItem> */
    private array $items = [];

    /** @var list<BoxSize> */
    private array $availableBoxes = [];

    private bool $dispatched = false;

    public function __construct(public readonly string $number)
    {
    }

    // --- doména ------------------------------------------------------------

    public function addItem(PackableItem $item): void
    {
        if ($this->dispatched) {
            throw new \DomainException('Odeslanou zásilku už nelze měnit.');
        }

        $this->items[] = $item;
    }

    public function offerBox(BoxSize $box): void
    {
        $this->availableBoxes[] = $box;
    }

    public function canBeDispatched(): bool
    {
        return !$this->dispatched && $this->items !== [];
    }

    public function dispatch(): void
    {
        if (!$this->canBeDispatched()) {
            throw new \DomainException('Zásilku nelze odeslat.');
        }

        $this->dispatched = true;
    }

    // --- algoritmus ---------------------------------------------------------

    /** @return list<array{size: BoxSize, items: list<PackableItem>}> */
    public function packIntoBoxes(): array
    {
        $items = $this->sortItemsByVolumeDescending();
        $boxes = $this->sortBoxesByCapacityAscending();
        $largest = $this->largestBox($boxes);
        $open = [];

        foreach ($items as $item) {
            $this->assertFits($item, $largest);

            $index = $this->findOpenBoxWithSpace($open, $item);

            if ($index !== null) {
                $open[$index]['items'][] = $item;
                $open[$index]['free'] -= $item->volumeInMillilitres;
                continue;
            }

            $chosen = $this->smallestBoxFitting($boxes, $item);

            $open[] = [
                'size' => $chosen,
                'items' => [$item],
                'free' => $chosen->capacityInMillilitres - $item->volumeInMillilitres,
            ];
        }

        return array_map(
            static fn (array $b): array => ['size' => $b['size'], 'items' => $b['items']],
            $open,
        );
    }

    /** @return list<PackableItem> */
    private function sortItemsByVolumeDescending(): array
    {
        $items = $this->items;
        usort($items, static fn (PackableItem $a, PackableItem $b): int
            => $b->volumeInMillilitres <=> $a->volumeInMillilitres);

        return $items;
    }

    /** @return list<BoxSize> */
    private function sortBoxesByCapacityAscending(): array
    {
        $boxes = $this->availableBoxes;
        usort($boxes, static fn (BoxSize $a, BoxSize $b): int
            => $a->capacityInMillilitres <=> $b->capacityInMillilitres);

        return $boxes;
    }

    /** @param list<BoxSize> $boxes */
    private function largestBox(array $boxes): BoxSize
    {
        if ($boxes === []) {
            throw new \InvalidArgumentException('Není k dispozici žádná velikost krabice.');
        }

        return $boxes[count($boxes) - 1];
    }

    private function assertFits(PackableItem $item, BoxSize $largest): void
    {
        if ($item->volumeInMillilitres > $largest->capacityInMillilitres) {
            throw new \InvalidArgumentException(
                sprintf('Položka %s se nevejde ani do největší krabice.', $item->sku),
            );
        }
    }

    /** @param list<array{size: BoxSize, items: list<PackableItem>, free: int}> $open */
    private function findOpenBoxWithSpace(array $open, PackableItem $item): ?int
    {
        foreach ($open as $index => $box) {
            if ($box['free'] >= $item->volumeInMillilitres) {
                return $index;
            }
        }

        return null;
    }

    /** @param list<BoxSize> $boxes */
    private function smallestBoxFitting(array $boxes, PackableItem $item): BoxSize
    {
        foreach ($boxes as $box) {
            if ($box->capacityInMillilitres >= $item->volumeInMillilitres) {
                return $box;
            }
        }

        throw new \InvalidArgumentException('Žádná krabice není dost velká.');
    }
}
