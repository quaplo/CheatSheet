<?php

declare(strict_types=1);

/**
 * Slovník domény — jazyk, kterým mluví doménoví experti.
 *
 * Tenhle seznam nevzniká u vývojářů. Vzniká z rozhovorů s lidmi,
 * kteří doméně rozumí, a je to ta „backbone of a language",
 * o které mluví Evans.
 *
 * @return array<string, string> pojem => co znamená
 */
function domainGlossary(): array
{
    return [
        'Objednávka'  => 'Závazný požadavek zákazníka na dodání zboží',
        'Storno'      => 'Zrušení objednávky ze strany zákazníka před expedicí',
        'Expedice'    => 'Předání zásilky dopravci',
        'Reklamace'   => 'Uplatnění vady zboží po dodání',
        'Dobropis'    => 'Doklad o vrácení peněz zákazníkovi',
        'Rezervace'   => 'Blokace skladové zásoby pro konkrétní objednávku',
    ];
}

/**
 * Jak se pojmy překládají do kódu.
 *
 * Tenhle převod je jediné místo, kde překlad smí být — a i tady
 * jen proto, že kód se píše anglicky. Uvnitř kódu už se překládat nesmí.
 *
 * @return array<string, string> pojem => anglický tvar v kódu
 */
function codeTerms(): array
{
    return [
        'Objednávka'  => 'Order',
        'Storno'      => 'Cancellation',
        'Expedice'    => 'Dispatch',
        'Reklamace'   => 'Complaint',
        'Dobropis'    => 'CreditNote',
        'Rezervace'   => 'Reservation',
    ];
}
