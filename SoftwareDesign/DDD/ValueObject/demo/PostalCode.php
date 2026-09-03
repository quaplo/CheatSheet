<?php

declare(strict_types=1);

/**
 * PSČ.
 *
 * Ukazuje value object, jehož platnost závisí na jiné hodnotě: „12000“ je
 * platné PSČ jen ve vztahu k nějaké zemi. Proto si zemi nese s sebou —
 * bez ní by nešlo rozhodnout, jestli je hodnota platná.
 */
final readonly class PostalCode
{
    private function __construct(
        public string $value,
        public Country $country,
    ) {
    }

    public static function fromString(string $value, Country $country): self
    {
        // Normalizace: „120 00“ i „12000“ je tatáž hodnota.
        $normalized = preg_replace('/\s+/', '', trim($value)) ?? '';

        if (preg_match($country->postalCodePattern(), $normalized) !== 1) {
            throw new InvalidArgumentException(sprintf(
                '„%s“ není platné PSČ pro zemi %s.',
                $value,
                $country->value,
            ));
        }

        return new self($normalized, $country);
    }

    public function format(): string
    {
        return substr($this->value, 0, 3) . ' ' . substr($this->value, 3);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value
            && $this->country === $other->country;
    }
}
