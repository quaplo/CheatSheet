<?php

declare(strict_types=1);

/**
 * Schvalovatel s limitem.
 *
 * Jedna třída pokryje celou hierarchii — jednotlivé články se liší jen
 * jménem a částkou, ne chováním.
 */
final class LimitedApprover extends Approver
{
    public function __construct(
        private readonly string $name,
        private readonly int $limitInCents,
    ) {
    }

    public function name(): string
    {
        return sprintf('%s (do %s Kč)', $this->name, number_format($this->limitInCents / 100, 0, ',', ' '));
    }

    protected function canApprove(ApprovalRequest $request): bool
    {
        return $request->discountInCents <= $this->limitInCents;
    }
}
