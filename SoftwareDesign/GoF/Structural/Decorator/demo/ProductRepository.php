<?php

declare(strict_types=1);

/**
 * Rozhraní, které sdílí základ i všechny dekorátory.
 *
 * Tohle je podmínka celého patternu: dekorátor musí být z pohledu
 * volajícího nerozeznatelný od toho, co obaluje. Kdyby přidal metodu
 * navíc, přestane být zaměnitelný a stane se z něj něco jiného.
 */
interface ProductRepository
{
    public function find(string $sku): ?string;
}
