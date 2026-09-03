<?php

declare(strict_types=1);

namespace Steps;

use Contexts\PaymentContext;
use Saga\SagaState;
use Saga\SagaStep;

final readonly class ChargePayment implements SagaStep
{
    public function __construct(
        private PaymentContext $payments,
    ) {
    }

    public function name(): string
    {
        return 'stržení platby';
    }

    public function execute(SagaState $state): void
    {
        $state->remember($this->name(), $this->payments->charge($state->orderId, $state->totalInCents));
    }

    /** Kompenzace = dobropis. Platba se nemaže, přidá se opačný záznam. */
    public function compensate(SagaState $state): void
    {
        if ($state->hasCompleted($this->name())) {
            $this->payments->refund($state->resultOf($this->name()));
        }
    }

    public function isPivot(): bool
    {
        return false;
    }
}
