<?php

declare(strict_types=1);

/**
 * Pravidlo poskládané z konfigurace — „pravidla jako data“.
 *
 * Tohle je ten krok, po kterém všichni touží: pravidlo se dá změnit bez
 * deploye. Přečti si ale, co za to platíš:
 *
 *   · Slovník je omezený. Cokoli, co v něm není, se musí doprogramovat.
 *   · Typová kontrola končí. Překlep v názvu pole se pozná až za běhu.
 *   · IDE nenajde použití. Refactoring pole `orderTotalInCents` tenhle
 *     soubor tiše mine.
 *
 * Právě proto je slovník záměrně titěrný. Jakmile začne růst, píšeš si
 * vlastní jazyk — a to je úplně jiný projekt.
 */
final readonly class ConfiguredRule implements DiscountRule
{
    /**
     * @param array{field: string, op: string, value: int|bool} $when
     * @param array{percent?: int, amount?: int}                $then
     */
    private function __construct(
        private string $name,
        private int $priority,
        private array $when,
        private array $then,
    ) {
    }

    /**
     * @param array{name: string, priority: int, when: array{field: string, op: string, value: int|bool}, then: array{percent?: int, amount?: int}} $definition
     */
    public static function fromArray(array $definition): self
    {
        return new self(
            $definition['name'],
            $definition['priority'],
            $definition['when'],
            $definition['then'],
        );
    }

    public function name(): string
    {
        return $this->name;
    }

    public function priority(): int
    {
        return $this->priority;
    }

    public function appliesTo(DiscountContext $context): bool
    {
        $field = $this->when['field'];

        if (property_exists($context, $field) === false) {
            // Chyba v konfiguraci se pozná až tady, za běhu. Kdyby bylo
            // pravidlo napsané v PHP, nepustil by to ani PHPStan.
            throw new InvalidArgumentException(sprintf('Pravidlo „%s“ se ptá na neznámý údaj „%s“.', $this->name, $field));
        }

        $actual = $context->{$field};

        return match ($this->when['op']) {
            '>=' => $actual >= $this->when['value'],
            '<=' => $actual <= $this->when['value'],
            '==' => $actual === $this->when['value'],
            default => throw new InvalidArgumentException(sprintf('Neznámý operátor „%s“.', $this->when['op'])),
        };
    }

    public function discountFor(DiscountContext $context): int
    {
        if (isset($this->then['percent'])) {
            return intdiv($context->orderTotalInCents * $this->then['percent'], 100);
        }

        return $this->then['amount'] ?? 0;
    }
}
