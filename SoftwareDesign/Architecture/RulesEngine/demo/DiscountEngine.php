<?php

declare(strict_types=1);

/**
 * Engine. Záměrně hloupý — projde pravidla, sesbírá ta, která sedla,
 * a podle zvolené strategie rozhodne, co se uplatní.
 *
 * Co tady NENÍ a co je potřeba si hlídat: žádné řetězení. Pravidla se
 * vyhodnotí jednou nad původními fakty a nemůžou spustit jiná pravidla.
 * Tahle hranice odděluje „pravidla jako data“ od plnohodnotného
 * inferenčního systému — a překročit ji stojí řádově víc, než se zdá.
 */
final readonly class DiscountEngine
{
    /** @var list<DiscountRule> */
    private array $rules;

    /** @param list<DiscountRule> $rules */
    public function __construct(
        array $rules,
        private ConflictResolution $strategy = ConflictResolution::FirstMatch,
    ) {
        // Seřazení podle priority patří sem, ne na volajícího. Kdyby na
        // pořadí v poli záleželo, vrátili bychom se k implicitnímu pořadí ifů.
        usort($rules, static fn (DiscountRule $a, DiscountRule $b): int => $b->priority() <=> $a->priority());

        $this->rules = $rules;
    }

    public function evaluate(DiscountContext $context): DiscountResult
    {
        $matching = [];

        foreach ($this->rules as $rule) {
            if ($rule->appliesTo($context)) {
                $matching[] = [$rule, $rule->discountFor($context)];
            }
        }

        $usedIndexes = $this->resolve($matching);

        $evaluated = [];
        $total = 0;

        foreach ($matching as $index => [$rule, $discount]) {
            $wasUsed = in_array($index, $usedIndexes, strict: true);

            if ($wasUsed) {
                $total += $discount;
            }

            $evaluated[] = new AppliedRule($rule->name(), $rule->priority(), $discount, $wasUsed);
        }

        // Sleva nikdy nesmí přesáhnout hodnotu objednávky.
        $total = min($total, $context->orderTotalInCents);

        return new DiscountResult($total, $this->strategy, $evaluated);
    }

    /**
     * @param list<array{DiscountRule, int}> $matching
     *
     * @return list<int> indexy pravidel, která se uplatní
     */
    private function resolve(array $matching): array
    {
        if ($matching === []) {
            return [];
        }

        return match ($this->strategy) {
            ConflictResolution::FirstMatch => [0],
            ConflictResolution::Accumulate => array_keys($matching),
            ConflictResolution::BestForCustomer => [$this->indexOfLargestDiscount($matching)],
        };
    }

    /** @param list<array{DiscountRule, int}> $matching */
    private function indexOfLargestDiscount(array $matching): int
    {
        $best = 0;

        foreach ($matching as $index => [, $discount]) {
            if ($discount > $matching[$best][1]) {
                $best = $index;
            }
        }

        return $best;
    }

    /**
     * Diagnostika: která pravidla nesednou ani na jeden ze zadaných případů?
     *
     * Tohle je druhá věc, kterou hromada `if`ů nedá. Mrtvé pravidlo
     * v podmínce nikdo nenajde; tady se na něj dá zeptat.
     *
     * @param list<DiscountContext> $samples
     *
     * @return list<string> jména pravidel, která nikdy nesedla
     */
    public function neverMatching(array $samples): array
    {
        $dead = [];

        foreach ($this->rules as $rule) {
            foreach ($samples as $sample) {
                if ($rule->appliesTo($sample)) {
                    continue 2;
                }
            }

            $dead[] = $rule->name();
        }

        return $dead;
    }

    /** @return list<array{name: string, priority: int}> katalog pravidel */
    public function catalogue(): array
    {
        return array_map(
            static fn (DiscountRule $rule): array => ['name' => $rule->name(), 'priority' => $rule->priority()],
            $this->rules,
        );
    }
}
