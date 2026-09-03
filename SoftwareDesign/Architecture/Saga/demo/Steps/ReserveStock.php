<?php

declare(strict_types=1);

namespace Steps;

use Contexts\StockContext;
use Saga\SagaState;
use Saga\SagaStep;

final readonly class ReserveStock implements SagaStep
{
    public function __construct(
        private StockContext $stock,
    ) {
    }

    public function name(): string
    {
        return 'rezervace skladu';
    }

    public function execute(SagaState $state): void
    {
        $state->remember($this->name(), $this->stock->reserve($state->orderId, $state->sku, $state->quantity));
    }

    public function compensate(SagaState $state): void
    {
        if ($state->hasCompleted($this->name())) {
            $this->stock->release($state->resultOf($this->name()));
        }
    }

    public function isPivot(): bool
    {
        return false;
    }
}
