<?php

declare(strict_types=1);

namespace After;

/**
 * Jediné místo, kde po refaktoringu zůstane rozhodování podle řetězce.
 *
 * Fowler tomu říká továrna a je to nutné zlo na hranici: někde se
 * z dat z databáze nebo z formuláře musí stát objekt. Podstatné je,
 * že takové místo je JEDNO — ne čtyři.
 */
final class ShippingMethods
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

    public function byCode(string $code): ShippingMethod
    {
        return $this->methods[$code]
            ?? throw new \InvalidArgumentException('Neznámý dopravce: ' . $code);
    }

    /** @return list<ShippingMethod> */
    public function all(): array
    {
        return array_values($this->methods);
    }
}
