<?php

declare(strict_types=1);

namespace Adapters;

use Core\UseCases\ShowOrderResponse;

final class JsonPresenter
{
    public function present(ShowOrderResponse $response): string
    {
        return json_encode([
            'id' => $response->orderId,
            'total' => $response->totalInCents,
            'cancellable' => $response->canBeCancelled,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}
