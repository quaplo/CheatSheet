<?php

declare(strict_types=1);

/**
 * Mapa kontextů jako data v repozitáři.
 *
 * Smysl je jediný: mapa nakreslená v nástroji na diagramy zastará během
 * měsíce, protože ji nikdo nepřipomínkuje v pull requestu. Mapa jako kód
 * se aktualizuje spolu se změnou, dá se revidovat a dá se z ní vygenerovat
 * obrázek i kontroly.
 *
 * (Existuje na to i hotový nástroj — Context Mapper. Tohle je 100řádková
 * ukázka téhož nápadu.)
 */
final readonly class ContextMap
{
    /**
     * @param list<Context>      $contexts
     * @param list<Relationship> $relationships
     */
    public function __construct(
        private array $contexts,
        private array $relationships,
    ) {
    }

    /** @return list<Relationship> */
    public function upstreamOf(string $context): array
    {
        return array_values(array_filter(
            $this->relationships,
            static fn (Relationship $r): bool => $r->downstream === $context,
        ));
    }

    /** @return list<Relationship> */
    public function downstreamOf(string $context): array
    {
        return array_values(array_filter(
            $this->relationships,
            static fn (Relationship $r): bool => $r->upstream === $context,
        ));
    }

    /**
     * Rizika, na která mapa upozorní sama.
     *
     * Tohle je ta část, kvůli které se vyplatí mít mapu jako data:
     * na obrázku v konfluenci si těchhle věcí nikdo nevšimne.
     *
     * @return list<string>
     */
    public function risks(): array
    {
        $risks = [];

        foreach ($this->relationships as $relationship) {
            if ($relationship->type === RelationshipType::SharedKernel) {
                $risks[] = sprintf(
                    'SHARED KERNEL mezi %s a %s — kdo ho vlastní? Změna zasáhne oba týmy naráz.',
                    $relationship->upstream,
                    $relationship->downstream,
                );
            }

            if ($relationship->type === RelationshipType::Conformist) {
                $risks[] = sprintf(
                    'CONFORMIST: %s přebírá model %s bez překladu — cizí pojmy se dostanou dovnitř.',
                    $relationship->downstream,
                    $relationship->upstream,
                );
            }
        }

        foreach ($this->contexts as $context) {
            $consumers = $this->downstreamOf($context->name);

            $publishes = array_filter(
                $consumers,
                static fn (Relationship $r): bool => $r->type === RelationshipType::OpenHostService,
            );

            if (count($consumers) >= 3 && $publishes === []) {
                $risks[] = sprintf(
                    '%s má %d konzumentů a nepublikuje kontrakt — zvaž Open Host Service.',
                    $context->name,
                    count($consumers),
                );
            }

            if ($consumers === [] && $this->upstreamOf($context->name) === []) {
                $risks[] = sprintf(
                    '%s nemá žádný vztah — je to opravdu Separate Ways, nebo se na něj zapomnělo?',
                    $context->name,
                );
            }
        }

        foreach ($this->cycles() as $cycle) {
            $risks[] = sprintf(
                'CYKLUS: %s — vzájemná závislost, nelze vydávat nezávisle.',
                implode(' → ', $cycle),
            );
        }

        return $risks;
    }

    /**
     * Najde vzájemné závislosti. Hledá jen dvojice — ty jsou v praxi
     * nejčastější a nejsnáz opravitelné.
     *
     * @return list<list<string>>
     */
    private function cycles(): array
    {
        $cycles = [];

        foreach ($this->relationships as $a) {
            foreach ($this->relationships as $b) {
                if ($a->upstream === $b->downstream
                    && $a->downstream === $b->upstream
                    && $a->upstream < $a->downstream
                ) {
                    $cycles[] = [$a->upstream, $a->downstream, $a->upstream];
                }
            }
        }

        return $cycles;
    }

    /** Vykreslení mapy do Mermaidu, který GitHub zobrazí. */
    public function toMermaid(): string
    {
        $lines = ['flowchart LR'];

        foreach ($this->contexts as $context) {
            $lines[] = sprintf('    %s["%s<br/>%s"]', $this->nodeId($context->name), $context->name, $context->team);
        }

        foreach ($this->relationships as $relationship) {
            $lines[] = sprintf(
                '    %s -->|"%s"| %s',
                $this->nodeId($relationship->upstream),
                $relationship->type->value,
                $this->nodeId($relationship->downstream),
            );
        }

        return implode("\n", $lines);
    }

    private function nodeId(string $name): string
    {
        return preg_replace('/[^A-Za-z0-9]/', '', $name) ?? $name;
    }

    /** @return list<Context> */
    public function contexts(): array
    {
        return $this->contexts;
    }
}
