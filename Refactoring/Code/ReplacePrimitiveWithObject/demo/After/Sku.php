<?php

declare(strict_types=1);

namespace After;

/**
 * PO: SKU jako vlastní typ.
 *
 * Jedna definice toho, co je platné SKU, jedna normalizace a jedno
 * porovnání. Neplatné SKU nevznikne — a tím pádem se nikde dál
 * nemusí kontrolovat.
 */
final readonly class Sku
{
    private const string PATTERN = '/^[A-Z]{3}-\d{2}$/';

    private function __construct(public string $value)
    {
    }

    public static function fromString(string $value): self
    {
        // Normalizace na jednom místě: mezery pryč, velká písmena.
        $normalized = strtoupper(trim($value));

        if (preg_match(self::PATTERN, $normalized) !== 1) {
            throw new \InvalidArgumentException(
                sprintf('„%s" není platné SKU; očekává se tvar ABC-12.', $value),
            );
        }

        return new self($normalized);
    }

    public static function isValid(string $value): bool
    {
        try {
            self::fromString($value);

            return true;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /** Skupina produktu — první tři písmena. Chování, které řetězec neměl. */
    public function productGroup(): string
    {
        return substr($this->value, 0, 3);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
