<?php

declare(strict_types=1);

namespace Billing;

use Shared\CustomerId;

/**
 * Zákazník očima FAKTURACE.
 *
 * Tady „zákazník“ znamená plátce: právnická osoba s DIČ, splatností
 * a úvěrovým limitem. Kontaktní osoba ani pravděpodobnost obchodu
 * nedávají smysl — obchod už je uzavřený.
 *
 * Tohle je totéž slovo, jiný pojem. A je to správně.
 */
final readonly class Customer
{
    public function __construct(
        public CustomerId $id,
        public string $legalName,
        public string $vatId,
        public string $billingAddress,
        public int $paymentTermDays,
        public int $creditLimitInCents,
    ) {
    }

    public function canOrderFor(int $amountInCents, int $currentlyUnpaidInCents): bool
    {
        return $currentlyUnpaidInCents + $amountInCents <= $this->creditLimitInCents;
    }

    public function dueDateFor(\DateTimeImmutable $issuedAt): \DateTimeImmutable
    {
        return $issuedAt->modify(sprintf('+%d days', $this->paymentTermDays));
    }
}
