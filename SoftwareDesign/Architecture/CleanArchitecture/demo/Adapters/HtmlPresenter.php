<?php

declare(strict_types=1);

namespace Adapters;

use Core\UseCases\ShowOrderResponse;

final class HtmlPresenter
{
    public function present(ShowOrderResponse $response): string
    {
        $button = $response->canBeCancelled ? '<button>Stornovat</button>' : '';

        return sprintf(
            '<h1>Objednávka %s</h1><p>%s Kč</p>%s',
            $response->orderId,
            number_format($response->totalInCents / 100, 2, ',', ' '),
            $button,
        );
    }
}
