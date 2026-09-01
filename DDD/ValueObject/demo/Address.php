<?php

declare(strict_types=1);

/**
 * Doručovací adresa — kompozitní value object.
 *
 * Skládá se z jiných hodnot (PostalCode, Country) a z prostých řetězců.
 * Zajímavé je na ní to, co dělá konstruktor: kromě vlastní validace složek
 * hlídá i invariant, který **žádná složka sama uhlídat nemůže** — že PSČ
 * patří do téže země jako adresa.
 *
 * PostalCode je platné. Country je platná. A přesto může být jejich
 * kombinace nesmysl. Přesně proto má složený value object vlastní
 * konstruktor a nespoléhá na to, že si to pohlídají jeho části.
 */
final readonly class Address
{
    private function __construct(
        public string $street,
        public string $city,
        public PostalCode $postalCode,
        public Country $country,
    ) {
    }

    public static function create(
        string $street,
        string $city,
        PostalCode $postalCode,
        Country $country,
    ): self {
        $street = trim($street);
        $city = trim($city);

        if ($street === '') {
            throw new InvalidArgumentException('Ulice nesmí být prázdná.');
        }

        if ($city === '') {
            throw new InvalidArgumentException('Město nesmí být prázdné.');
        }

        // Invariant napříč složkami.
        if ($postalCode->country !== $country) {
            throw new InvalidArgumentException(sprintf(
                'PSČ %s patří do země %s, ale adresa je v zemi %s.',
                $postalCode->format(),
                $postalCode->country->value,
                $country->value,
            ));
        }

        return new self($street, $city, $postalCode, $country);
    }

    /**
     * Rovnost složeného value objectu se skládá z rovnosti jeho částí —
     * u vnořených hodnot deleguje na jejich vlastní equals().
     */
    public function equals(self $other): bool
    {
        return $this->street === $other->street
            && $this->city === $other->city
            && $this->postalCode->equals($other->postalCode)
            && $this->country === $other->country;
    }

    public function format(): string
    {
        return sprintf(
            "%s\n%s %s\n%s",
            $this->street,
            $this->postalCode->format(),
            $this->city,
            $this->country->label(),
        );
    }
}
