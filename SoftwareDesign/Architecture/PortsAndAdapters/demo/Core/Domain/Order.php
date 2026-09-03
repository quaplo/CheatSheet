<?php

declare(strict_types=1);

namespace Core\Domain;

/**
 * Objednávka — čistý doménový objekt.
 *
 * Všimni si, co tady NENÍ: žádná anotace Doctrine, žádný JsonSerializable,
 * žádný odkaz na HTTP ani na framework. Jádro o okolním světě neví.
 */
final readonly class Order
{
    private function __construct(
        public string $number,
        public string $customerEmail,
        public int $totalInCents,
        public ?string $paymentReference,
    ) {
    }

    public static function place(string $number, string $customerEmail, int $totalInCents): self
    {
        if ($totalInCents <= 0) {
            throw new \InvalidArgumentException('Objednávka musí mít kladnou hodnotu.');
        }

        return new self($number, $customerEmail, $totalInCents, paymentReference: null);
    }

    public function paidWith(string $paymentReference): self
    {
        return new self($this->number, $this->customerEmail, $this->totalInCents, $paymentReference);
    }

    public function isPaid(): bool
    {
        return $this->paymentReference !== null;
    }
}
