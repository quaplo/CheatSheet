<?php

declare(strict_types=1);

namespace Subclasses;

/**
 * Povýšení zákazníka na prémiového.
 *
 * S podtřídami to jinak nejde: PHP neumí objektu změnit třídu,
 * takže se musí postavit nový a stav se do něj přenést ručně.
 */
final class Upgrade
{
    public static function toPremium(Customer $customer): PremiumCustomer
    {
        $upgraded = new PremiumCustomer($customer->id, $customer->name);
        $upgraded->addPurchase($customer->lifetimeValueInCents());

        return $upgraded;
    }
}
