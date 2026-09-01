<?php

declare(strict_types=1);

namespace Core\Port\Driven;

/**
 * Odpověď platební brány v pojmech jádra.
 *
 * Jádro nikdy nevidí HTTP status ani JSON od Stripe — adaptér mu to přeloží
 * do tohohle tvaru. Díky tomu jde brána vyměnit bez zásahu do use-case.
 */
final readonly class PaymentResult
{
    private function __construct(
        public bool $isApproved,
        public ?string $reference,
        public string $message,
    ) {
    }

    public static function approved(string $reference): self
    {
        return new self(true, $reference, 'Platba schválena.');
    }

    public static function declined(string $message): self
    {
        return new self(false, null, $message);
    }
}
