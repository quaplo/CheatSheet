<?php

declare(strict_types=1);

namespace Billing;

use Sales\Customer as SalesCustomer;

/**
 * PŘEKLAD NA HRANICI kontextů.
 *
 * Když obchod uzavře příležitost, musí z ní ve fakturaci vzniknout
 * plátce. Není to kopie — je to překlad: část údajů se přenese, část
 * zahodí a část musí dodat fakturace sama, protože ji obchod nezná.
 *
 * Podstatné je, KDE tenhle překlad žije. Patří do fakturace, protože
 * ta rozhoduje, co potřebuje. Kdyby ho vlastnil obchod, musel by znát
 * fakturační model — a hranice by přestala platit.
 *
 * Tomuhle se v Context Map říká antikorupční vrstva.
 */
final readonly class PayerFromWonDeal
{
    public function translate(
        SalesCustomer $wonDeal,
        string $vatId,
        string $billingAddress,
    ): Customer {
        return new Customer(
            id: $wonDeal->id,                       // přenese se identita
            legalName: $wonDeal->companyName,       // a jméno, byť s jiným významem
            vatId: $vatId,                          // tohle obchod nezná — dodá fakturace
            billingAddress: $billingAddress,
            paymentTermDays: 14,                    // výchozí pravidlo fakturace
            creditLimitInCents: $this->initialCreditLimit($wonDeal),
        );
    }

    /**
     * Pravidlo fakturace, ne obchodu. Používá vstup z obchodu, ale
     * rozhodnutí je zdejší.
     */
    private function initialCreditLimit(SalesCustomer $wonDeal): int
    {
        return min($wonDeal->dealValueInCents * 2, 5000000);
    }
}
