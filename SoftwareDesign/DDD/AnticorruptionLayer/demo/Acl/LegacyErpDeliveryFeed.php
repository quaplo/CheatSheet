<?php

declare(strict_types=1);

namespace Acl;

use Domain\DeliveryFeed;

/**
 * ADAPTÉR — třetí díl. Spojuje fasádu s překladačem a naplňuje
 * port, který si definovala doména.
 *
 * Tahle třída je jediné místo v aplikaci, které ví, že nějaké ERP
 * vůbec existuje.
 */
final readonly class LegacyErpDeliveryFeed implements DeliveryFeed
{
    public function __construct(
        private ErpFacade $erp,
        private ErpTranslator $translator,
    ) {
    }

    public function deliveries(): array
    {
        $deliveries = [];

        foreach ($this->erp->supplierRows() as $row) {
            if ($this->translator->isReturn($row) === false) {
                $deliveries[] = $this->translator->toDelivery($row);
            }
        }

        return $deliveries;
    }

    public function returns(): array
    {
        $returns = [];

        foreach ($this->erp->supplierRows() as $row) {
            if ($this->translator->isReturn($row)) {
                $returns[] = $this->translator->toReturn($row);
            }
        }

        return $returns;
    }
}
