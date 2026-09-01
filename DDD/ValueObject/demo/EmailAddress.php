<?php

declare(strict_types=1);

/**
 * E-mailová adresa.
 *
 * Ukazuje dvě věci, které value object umí a `string` ne:
 *
 *  1. Neplatná instance nemůže vzniknout — validace je v konstruktoru.
 *  2. Normalizace na jednom místě, takže „ Alice@Example.COM “
 *     a „alice@example.com“ jsou tatáž hodnota.
 */
final readonly class EmailAddress
{
    private function __construct(
        public string $value,
    ) {
    }

    public static function fromString(string $value): self
    {
        $normalized = mb_strtolower(trim($value));

        if (filter_var($normalized, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException(sprintf('„%s“ není platná e-mailová adresa.', $value));
        }

        return new self($normalized);
    }

    public function domain(): string
    {
        return substr($this->value, strpos($this->value, '@') + 1);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
