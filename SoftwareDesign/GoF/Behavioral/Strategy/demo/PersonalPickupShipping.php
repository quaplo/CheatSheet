<?php

declare(strict_types=1);

/**
 * Osobní odběr na prodejně — vždy zdarma.
 *
 * Všimni si, že i triviální varianta má vlastní třídu. Právě to je smysl
 * patternu: kontext se nikde nemusí ptát „a není tohle náhodou ten případ,
 * kdy se nic neplatí?“.
 */
final readonly class PersonalPickupShipping implements ShippingCost
{
    public function code(): string
    {
        return 'personal_pickup';
    }

    public function calculate(Order $order): int
    {
        return 0;
    }
}
