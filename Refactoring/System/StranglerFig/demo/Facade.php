<?php

declare(strict_types=1);

/**
 * FASÁDA — zachycení požadavků (event interception).
 *
 * Sedí před oběma systémy a rozhoduje, kam požadavek půjde.
 * Klient o její existenci neví; volá pořád tutéž adresu.
 *
 * Cartwright, Horn a Lewis: „If you are using the Strangler Fig
 * pattern then you will also be using some form of Event Interception."
 *
 * V praxi to bývá reverzní proxy, API gateway nebo prostě routovací
 * vrstva v aplikaci — mechanismus je jedno, princip je stejný.
 */
final class Facade
{
    /** @var array<string, bool> schopnost => jde na nový systém */
    private array $routing = [];

    /** @var array<string, int> kolik požadavků obsloužil který systém */
    public array $servedCount = ['legacy' => 0, 'nový' => 0];

    public function __construct(
        private readonly LegacyMonolith $legacy,
        private readonly NewSystem $new,
    ) {
    }

    /** Přesměruje schopnost na nový systém. */
    public function route(string $capability, bool $toNew = true): void
    {
        $this->routing[$capability] = $toNew;
    }

    public function handle(Request $request): Response
    {
        $target = ($this->routing[$request->capability] ?? false)
            ? $this->new
            : $this->legacy;

        $response = $target->handle($request);
        ++$this->servedCount[$response->servedBy];

        return $response;
    }

    /** @return list<string> schopnosti, které ještě obsluhuje starý systém */
    public function stillOnLegacy(): array
    {
        return array_values(array_filter(
            $this->legacy->capabilities(),
            fn (string $c): bool => !($this->routing[$c] ?? false),
        ));
    }

    public function resetCounters(): void
    {
        $this->servedCount = ['legacy' => 0, 'nový' => 0];
    }
}
