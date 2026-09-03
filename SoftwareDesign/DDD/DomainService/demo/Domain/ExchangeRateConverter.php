<?php

declare(strict_types=1);

namespace Domain;

/**
 * DOMÉNOVÁ SLUŽBA — nesporná podoba.
 *
 * Převod částky na jinou měnu je doménová operace se svým pravidlem
 * (jak se zaokrouhluje a ve prospěch koho). Zkus ji někam pověsit:
 *
 *   Money::convertTo()      — musela by znát kurzy a pravidla o nich
 *   ExchangeRate::apply()   — kurz by musel znát zaokrouhlovací politiku
 *   Currency                — enum, nic z toho tam nepatří
 *
 * Ani jeden z těch objektů není přirozeným vlastníkem. Operace je
 * mezi nimi — a proto dostane vlastní jméno.
 *
 * Všimni si, že tady NENÍ nic ze sporných věcí: žádný agregát, žádná
 * změna stavu, žádný cizí kontext. Jen hodnoty dovnitř a hodnota ven.
 * Tohle je ta rodina doménových služeb, o které se nikdo nehádá.
 */
final readonly class ExchangeRateConverter
{
    public function convert(Money $amount, ExchangeRate $rate, Rounding $rounding): Money
    {
        if ($amount->currency !== $rate->from) {
            throw new \InvalidArgumentException(sprintf(
                'Kurz je z %s, ale částka je v %s.',
                $rate->from->value,
                $amount->currency->value,
            ));
        }

        $exact = $amount->amountInCents * $rate->rateInTenThousandths;

        // Tady bydlí to doménové rozhodnutí.
        $converted = match ($rounding) {
            Rounding::InFavourOfCustomer => intdiv($exact, 10000),
            Rounding::InFavourOfCompany => intdiv($exact + 9999, 10000),
        };

        return Money::fromCents($converted, $rate->to);
    }
}
