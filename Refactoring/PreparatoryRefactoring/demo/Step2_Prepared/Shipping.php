<?php

declare(strict_types=1);

namespace Step2_Prepared;

/**
 * PO PŘÍPRAVNÉM REFAKTORINGU — a PŘED přidáním funkce.
 *
 * Chování je totožné s výchozím stavem. Balíkovna tu není.
 * Změnila se jen struktura: každý dopravce je vlastní třída
 * a pravidlo o dopravě zdarma je vytažené.
 *
 * Tohle je ten krok, který Kent Beck popisuje jako „make the change
 * easy (warning: this may be hard)". Sám o sobě nepřidal nic —
 * jen připravil místo.
 */
final class Shipping
{
    /** @var array<string, ShippingMethod> */
    private array $methods = [];

    public function __construct(ShippingMethod ...$methods)
    {
        foreach ($methods as $method) {
            $this->methods[$method->code()] = $method;
        }
    }

    public static function default(): self
    {
        return new self(new Ppl(), new Dhl(), new Pickup());
    }

    public function priceInCents(string $carrier, int $weightInGrams, int $orderValueInCents): int
    {
        $method = $this->methods[$carrier]
            ?? throw new \InvalidArgumentException('Neznámý dopravce: ' . $carrier);

        return $method->priceInCents($weightInGrams, $orderValueInCents);
    }
}
