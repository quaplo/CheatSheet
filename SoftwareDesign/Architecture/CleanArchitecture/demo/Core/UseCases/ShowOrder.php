<?php

declare(strict_types=1);

namespace Core\UseCases;

/**
 * Interactor: pravidla specifická pro TUHLE aplikaci.
 *
 * „V administraci smí být storno vidět, dokud není odesláno" je
 * pravidlo aplikace, ne podniku — proto je tady a ne v entitě.
 */
final class ShowOrder
{
    public function __construct(private readonly OrderRepository $orders)
    {
    }

    public function __invoke(ShowOrderRequest $request): ShowOrderResponse
    {
        $order = $this->orders->find($request->orderId);

        return new ShowOrderResponse(
            orderId: $order->id,
            totalInCents: $order->totalInCents(),
            lineCount: $order->lineCount(),
            status: $order->status(),
            canBeCancelled: $order->status() !== 'shipped',
        );
    }
}
