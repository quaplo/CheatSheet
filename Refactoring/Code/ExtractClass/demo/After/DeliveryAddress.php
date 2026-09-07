<?php

declare(strict_types=1);

namespace After;

/**
 * PO: adresa jako vlastní třída.
 *
 * Všechny čtyři pole a všechny tři metody, které na ně sahaly,
 * se přestěhovaly sem. Objednávka o nich neví.
 */
final readonly class DeliveryAddress
{
    public function __construct(
        private string $street,
        private string $city,
        private string $postalCode,
        private string $countryCode = 'CZ',
    ) {
    }

    public function format(): string
    {
        return sprintf('%s, %s %s, %s', $this->street, $this->postalCode, $this->city, $this->countryCode);
    }

    public function isDomestic(): bool
    {
        return $this->countryCode === 'CZ';
    }

    public function postalCodeDigits(): string
    {
        return preg_replace('/\D/', '', $this->postalCode);
    }
}
