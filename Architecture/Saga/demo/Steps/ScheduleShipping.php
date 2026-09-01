<?php

declare(strict_types=1);

namespace Steps;

use Contexts\ShippingContext;
use Saga\SagaState;
use Saga\SagaStep;

/**
 * PIVOTNÍ krok.
 *
 * Jakmile zásilku převezme dopravce, zpět už to nejde — kompenzovat
 * lze leda reklamací, což je nový proces, ne návrat. Proto je nevratný
 * krok schválně POSLEDNÍ: do té doby se dá všechno vzít zpátky.
 */
final readonly class ScheduleShipping implements SagaStep
{
    public function __construct(
        private ShippingContext $shipping,
    ) {
    }

    public function name(): string
    {
        return 'naplánování dopravy';
    }

    public function execute(SagaState $state): void
    {
        $state->remember($this->name(), $this->shipping->schedule($state->orderId));
    }

    public function compensate(SagaState $state): void
    {
        if ($state->hasCompleted($this->name())) {
            $this->shipping->cancel($state->resultOf($this->name()));
        }
    }

    public function isPivot(): bool
    {
        return true;
    }
}
