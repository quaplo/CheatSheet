<?php

declare(strict_types=1);

namespace LegacyErp;

/**
 * CIZÍ SYSTÉM. Není náš, nezměníme ho, a takhle prostě odpovídá.
 *
 * Všechno na něm je špatně z pohledu naší domény — a přesně proto
 * potřebujeme antikorupční vrstvu:
 *
 *   · klíče v majuskulích a zkratkách        PARTNER_CIS, DOD_MNOZ
 *   · stavy jako číselné kódy                '01', '03', '07', '99'
 *   · datum jako řetězec YYYYMMDD            '20260901'
 *   · částka jako text s čárkou a mezerou    '1 234,50'
 *   · prázdno jako '0' nebo ''               ne null
 *   · JEDEN pojem „partner“ pro dodavatele i odběratele
 *   · ZÁPORNÉ množství znamená vratku        — jiný pojem, ne jiné číslo
 *   · chyby jako řádek s ERR                 ne výjimka
 */
final class ErpClient
{
    /** @return list<array<string, string>> */
    public function volejFunkci(string $funkce, array $parametry = []): array
    {
        if ($funkce === 'DOD_SEZNAM') {
            return [
                ['PARTNER_CIS' => '4711', 'PARTNER_TYP' => 'D', 'PARTNER_NAZ' => 'Mlýny Brno a.s.',
                 'DOD_CIS' => 'DL-2026-0031', 'DOD_DAT' => '20260901', 'DOD_MNOZ' => '120',
                 'DOD_CENA' => '48 500,00', 'DOD_STAV' => '03'],

                ['PARTNER_CIS' => '4711', 'PARTNER_TYP' => 'D', 'PARTNER_NAZ' => 'Mlýny Brno a.s.',
                 'DOD_CIS' => 'DL-2026-0032', 'DOD_DAT' => '20260903', 'DOD_MNOZ' => '-15',
                 'DOD_CENA' => '6 062,50', 'DOD_STAV' => '07'],

                ['PARTNER_CIS' => '5090', 'PARTNER_TYP' => 'O', 'PARTNER_NAZ' => 'Pekárna Novák s.r.o.',
                 'DOD_CIS' => 'OB-2026-8812', 'DOD_DAT' => '20260902', 'DOD_MNOZ' => '40',
                 'DOD_CENA' => '12 000,00', 'DOD_STAV' => '01'],

                ['PARTNER_CIS' => '4822', 'PARTNER_TYP' => 'D', 'PARTNER_NAZ' => 'Obaly CZ',
                 'DOD_CIS' => 'DL-2026-0033', 'DOD_DAT' => '20260904', 'DOD_MNOZ' => '500',
                 'DOD_CENA' => '9 750,00', 'DOD_STAV' => '99'],
            ];
        }

        if ($funkce === 'DOD_SEZNAM_VYPADEK') {
            return [['ERR' => 'X07', 'MSG' => 'SPOJENI S DB NEDOSTUPNE']];
        }

        return [];
    }
}
