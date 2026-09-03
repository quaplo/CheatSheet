<?php

declare(strict_types=1);

namespace Core\Port\Driving;

/**
 * Řídicí (primary) port — co aplikace umí, řečeno jejími slovy.
 *
 * Tohle je ta půlka, na kterou se často zapomíná. Většina lidí si pod
 * hexagonální architekturou představí jen repository interface (řízená
 * strana). Řídicí port je ale stejně důležitý: díky němu neví HTTP
 * controller nic o tom, jak se objednávka zakládá — zná jen tenhle kontrakt.
 */
interface PlaceOrder
{
    /**
     * @return string číslo založené objednávky
     *
     * @throws PaymentDeclined
     */
    public function place(PlaceOrderCommand $command): string;
}
