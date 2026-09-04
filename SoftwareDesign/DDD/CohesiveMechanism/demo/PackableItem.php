<?php

declare(strict_types=1);

/**
 * Položka, kterou je potřeba zabalit.
 */
final readonly class PackableItem
{
    public function __construct(
        public string $sku,
        public int $volumeInMillilitres,
    ) {
    }
}
