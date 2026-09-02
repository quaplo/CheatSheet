<?php

declare(strict_types=1);

/**
 * Společné rozhraní listu i uzlu.
 *
 * Tohle je celý pattern: klient nesmí poznat, jestli drží jeden
 * produkt, nebo kategorii se třemi sty produkty. Obojí umí totéž.
 *
 * Kdyby uzel měl metody navíc a klient je musel volat, rozpadlo by
 * se to na `if ($node instanceof Category)` — a přesně tomu se
 * pattern vyhýbá.
 */
interface CatalogNode
{
    public function name(): string;

    /** Kolik produktů je pod tímhle uzlem (včetně něj, je-li produktem). */
    public function productCount(): int;

    /** Nejnižší cena v haléřích, nebo null, když tu není co prodávat. */
    public function lowestPriceInCents(): ?int;

    /** Vypsání stromu pro člověka. */
    public function render(int $depth = 0): string;
}
