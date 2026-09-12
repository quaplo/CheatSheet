<?php

declare(strict_types=1);

namespace Adapters;

use Core\UseCases\ShowOrderResponse;

/**
 * Čtvrtý způsob dodání, přidaný jako poslední.
 *
 * Kvůli němu se nezměnil ani jeden soubor v Core/ — to je celé
 * tvrzení téhle architektury a demo si ho ověřuje.
 */
final class CliPresenter
{
    public function present(ShowOrderResponse $response): string
    {
        return sprintf(
            '%-16s %10s Kč  %s',
            $response->orderId,
            number_format($response->totalInCents / 100, 2, ',', ' '),
            $response->canBeCancelled ? '[lze stornovat]' : '',
        );
    }
}
