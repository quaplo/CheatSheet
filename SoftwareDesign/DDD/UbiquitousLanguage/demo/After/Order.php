<?php

declare(strict_types=1);

namespace After;

/**
 * PO: kód mluví jazykem domény.
 *
 * Evans: „Use the model as the backbone of a language."
 *
 * Každá metoda odpovídá pojmu, který doménový expert použije
 * v rozhovoru. Překlad probíhá jednou — čeština → angličtina —
 * a nikde jinde.
 */
final class Order
{
    public function cancel(Cancellation $cancellation): void
    {
    }

    public function dispatch(Dispatch $dispatch): void
    {
    }
}
