<?php

declare(strict_types=1);

namespace Wrong;

use Domain\Customer;

/**
 * ANTI-PŘÍKLAD: statická pomůcka.
 *
 * Dělá totéž co doménová služba, a přesto je to horší volba:
 *
 *   · Jméno nic neříká. „Utils“ není doménový pojem — o `PointsUtils`
 *     se s produkťákem nedomluvíš, o `LoyaltyPointsTransfer` ano.
 *   · Nejde vyměnit ani obalit. Statické volání se nedá nahradit
 *     v testu jinou implementací.
 *   · Přitahuje smetí. Do třídy s takovým jménem přibude za rok
 *     všechno, co se nikam nevešlo.
 *
 * Chování je stejné. Rozdíl je v tom, co ta třída sděluje.
 */
final class PointsUtils
{
    public static function transfer(Customer $from, Customer $to, int $points): void
    {
        $fee = intdiv($points * 10, 100);

        $from->redeemPoints($points);
        $to->earnPoints($points - $fee);
    }
}
