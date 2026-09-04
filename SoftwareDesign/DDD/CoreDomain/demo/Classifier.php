<?php

declare(strict_types=1);

/**
 * Klasifikace podoblasti podle Evansových kritérií.
 *
 * Pořadí otázek je podstatné: nejdřív se ptáme, jestli nás to odlišuje.
 * Když ano, je to jádro bez ohledu na to, že by se něco podobného
 * dalo koupit — kdo si koupí to, čím se má lišit, přestane se lišit.
 */
final class Classifier
{
    public function classify(Subdomain $subdomain): Classification
    {
        if ($subdomain->differentiates) {
            return Classification::Core;
        }

        if ($subdomain->sameEverywhere || $subdomain->availableOffTheShelf) {
            return Classification::Generic;
        }

        return Classification::Supporting;
    }
}
