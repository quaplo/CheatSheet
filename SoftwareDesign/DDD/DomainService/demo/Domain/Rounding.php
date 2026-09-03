<?php

declare(strict_types=1);

namespace Domain;

/**
 * Zaokrouhlení jako DOMÉNOVÉ rozhodnutí, ne technický detail.
 *
 * „Ve prospěch koho se zaokrouhluje“ je otázka na byznys, ne na
 * programátora — a je to přesně ten kus znalosti, kvůli kterému
 * převodník existuje.
 */
enum Rounding
{
    /** Ve prospěch zákazníka — u výplat a dobropisů. */
    case InFavourOfCustomer;

    /** Ve prospěch firmy — u plateb. */
    case InFavourOfCompany;
}
