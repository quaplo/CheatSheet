<?php

declare(strict_types=1);

namespace Acl;

use Domain\DeliveryFeedUnavailable;
use LegacyErp\ErpClient;

/**
 * FASÁDA — první ze tří dílů antikorupční vrstvy.
 *
 * Zjednodušuje cizí systém na to málo, co od něj chceme, a **překládá
 * jeho selhání na naše**. Tahle druhá věc se zapomíná nejčastěji:
 * když se `ERR X07` propustí ven, doména se o cizím systému stejně
 * dozví — jen oklikou přes chybové hlášky.
 */
final readonly class ErpFacade
{
    public function __construct(
        private ErpClient $client,
        private bool $simulateOutage = false,
    ) {
    }

    /**
     * Vrátí jen věty dodavatelů. Že ERP míchá dodavatele s odběrateli
     * do jednoho pojmu „partner“, končí tady.
     *
     * @return list<array<string, string>>
     */
    public function supplierRows(): array
    {
        $rows = $this->client->volejFunkci($this->simulateOutage ? 'DOD_SEZNAM_VYPADEK' : 'DOD_SEZNAM');

        $this->assertNoError($rows);

        return array_values(array_filter(
            $rows,
            static fn (array $row): bool => ($row['PARTNER_TYP'] ?? '') === 'D',
        ));
    }

    /** @param list<array<string, string>> $rows */
    private function assertNoError(array $rows): void
    {
        foreach ($rows as $row) {
            if (isset($row['ERR'])) {
                throw new DeliveryFeedUnavailable(sprintf(
                    'Zdroj dodávek není dostupný (ERP %s).',
                    $row['ERR'],
                ));
            }
        }
    }
}
