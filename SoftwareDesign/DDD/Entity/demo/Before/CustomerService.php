<?php

declare(strict_types=1);

namespace Before;

/**
 * …a logika, která k té entitě patří, ale bydlí jinde.
 *
 * Problém není v tom, že tahle třída existuje. Problém je, že tatáž
 * pravidla musí znát a zopakovat každý, kdo se zákazníkem pracuje —
 * a dřív nebo později je někdo zopakuje jinak, nebo na ně zapomene.
 */
final class CustomerService
{
    public function addPoints(AnemicCustomer $customer, int $points): void
    {
        $customer->setLoyaltyPoints($customer->getLoyaltyPoints() + $points);

        // Přepočet úrovně — kdo na něj zapomene, rozejde se s body.
        if ($customer->getLoyaltyPoints() >= 5000) {
            $customer->setTier('zlato');
        } elseif ($customer->getLoyaltyPoints() >= 1000) {
            $customer->setTier('stříbro');
        }
    }

    public function discountFor(AnemicCustomer $customer): int
    {
        // Ptáme se na stav a rozhodujeme za zákazníka — a totéž se dělá
        // ještě v košíku, v ceníku a v exportu do BI.
        if ($customer->isActive() === false) {
            return 0;
        }

        return match ($customer->getTier()) {
            'zlato' => 10,
            'stříbro' => 5,
            default => 0,
        };
    }
}
