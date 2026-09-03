<?php

declare(strict_types=1);

/**
 * Kontext. Umí spočítat dopravu, ale sám nezná ani jeden způsob výpočtu —
 * jen si drží mapu dostupných strategií a vybere z ní podle kódu.
 *
 * Přidání dalšího dopravce znamená novou třídu a jeden řádek v registraci.
 * Tenhle soubor se nemění (Open/Closed principle).
 */
final class ShippingCalculator
{
    /** @var array<string, ShippingCost> */
    private array $strategies = [];

    /**
     * @param list<ShippingCost> $strategies
     */
    public function __construct(array $strategies)
    {
        foreach ($strategies as $strategy) {
            $this->strategies[$strategy->code()] = $strategy;
        }
    }

    public function calculate(Order $order, string $shippingCode): int
    {
        $strategy = $this->strategies[$shippingCode]
            ?? throw new InvalidArgumentException(
                sprintf('Neznámý způsob dopravy "%s".', $shippingCode),
            );

        return $strategy->calculate($order);
    }

    /**
     * Nabídka pro zákazníka: všechny způsoby dopravy s cenou pro danou objednávku.
     *
     * @return array<string, int>
     */
    public function availableOptions(Order $order): array
    {
        return array_map(
            static fn (ShippingCost $strategy): int => $strategy->calculate($order),
            $this->strategies,
        );
    }
}
