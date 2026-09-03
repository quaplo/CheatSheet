<?php

declare(strict_types=1);

/**
 * Doménové pravidlo jako objekt.
 *
 * Základ je jediná metoda isSatisfiedBy(). Všechno ostatní — skládání
 * přes and/or/not a vysvětlení, proč pravidlo neprošlo — staví na ní.
 *
 * Proč abstraktní třída a ne rozhraní: rozhraní v PHP neumí nést
 * implementaci, takže by si každá specifikace musela and/or/not napsat
 * sama. Alternativa je rozhraní + trait; abstraktní třída je jednodušší.
 */
abstract class OrderSpecification
{
    abstract public function isSatisfiedBy(Order $order): bool;

    /** Lidský popis pravidla, používá se ve vysvětlení. */
    abstract public function describe(): string;

    /**
     * Potřebuje tahle specifikace závorky, když se objeví uvnitř jiné?
     * Složené ano, jednoduché ne — bez toho by popis „neplatí, že A
     * a zároveň B“ šlo číst dvěma způsoby.
     */
    protected function needsParentheses(): bool
    {
        return false;
    }

    /** Popis operandu, v případě potřeby v závorkách. */
    protected function describeOperand(self $operand): string
    {
        return $operand->needsParentheses()
            ? '(' . $operand->describe() . ')'
            : $operand->describe();
    }

    public function and(self $other): self
    {
        return new AndSpecification($this, $other);
    }

    public function or(self $other): self
    {
        return new OrSpecification($this, $other);
    }

    public function not(): self
    {
        return new NotSpecification($this);
    }

    /**
     * Důvody, proč objednávka pravidlu nevyhověla. Prázdné pole znamená,
     * že vyhověla.
     *
     * Tohle je to, co inline podmínka `if ($a && $b && $c)` nikdy nedá:
     * neřekne ti, která ze tří částí selhala.
     *
     * @return list<string>
     */
    public function reasonsForFailure(Order $order): array
    {
        return $this->isSatisfiedBy($order) ? [] : [$this->describe()];
    }
}
