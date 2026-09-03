<?php

declare(strict_types=1);

namespace Domain;

/**
 * PORT, který si definuje naše doména.
 *
 * Řekne, co potřebuje: dodávky a vratky od dodavatelů. Nic o tom,
 * odkud se berou. Antikorupční vrstva je implementací tohohle portu.
 */
interface DeliveryFeed
{
    /**
     * @return list<Delivery>
     *
     * @throws DeliveryFeedUnavailable
     */
    public function deliveries(): array;

    /**
     * @return list<GoodsReturn>
     *
     * @throws DeliveryFeedUnavailable
     */
    public function returns(): array;
}
