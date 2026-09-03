<?php

declare(strict_types=1);

namespace Domain;

/**
 * DOMÉNOVÁ SLUŽBA.
 *
 * Převod bodů mezi dvěma zákazníky je doménová operace se svými
 * pravidly — minimum, poplatek, oba účty aktivní. Jenže nepatří
 * ani jednomu z těch zákazníků:
 *
 *   Customer::transferTo()   — proč zdroj a ne cíl? A hlavně: zdroj
 *                              by měnil cizí agregát.
 *   PointsService v aplikaci — pravidlo by chránilo jen tu jednu cestu.
 *   PointsUtils::transfer()  — statická funkce bez doménového jména.
 *
 * Tak dostane vlastní jméno a vlastní třídu. Je to pořád doména:
 * žádné repository, žádná transakce, žádná infrastruktura. Dovnitř
 * jdou doménové objekty, ven doménový výsledek.
 *
 * Bezstavová schválně — jedna instance obslouží libovolně mnoho
 * převodů a nic si mezi nimi nepamatuje.
 */
final readonly class LoyaltyPointsTransfer
{
    private const int MINIMUM_POINTS = 100;
    private const int FEE_PERCENT = 10;

    public function transfer(Customer $from, Customer $to, int $points): TransferReceipt
    {
        // Pravidla o DVOJICI — to je přesně to, co nemá kde jinde bydlet.
        if ($from->id->equals($to->id)) {
            throw new TransferRejected('Nelze převádět body sám sobě.');
        }

        if ($points < self::MINIMUM_POINTS) {
            throw new TransferRejected(sprintf(
                'Nejmenší převod je %d bodů, požadováno %d.',
                self::MINIMUM_POINTS,
                $points,
            ));
        }

        if ($from->isActive() === false || $to->isActive() === false) {
            throw new TransferRejected('Převod je možný jen mezi aktivními zákazníky.');
        }

        $fee = intdiv($points * self::FEE_PERCENT, 100);

        // Vlastní pravidla si pak hlídá každý agregát sám —
        // redeemPoints() odmítne převod, na který zdroj nemá.
        $from->redeemPoints($points);
        $to->earnPoints($points - $fee);

        return new TransferReceipt(debited: $points, fee: $fee, credited: $points - $fee);
    }
}
