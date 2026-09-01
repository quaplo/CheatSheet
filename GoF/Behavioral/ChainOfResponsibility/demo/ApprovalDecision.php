<?php

declare(strict_types=1);

/**
 * Rozhodnutí i s cestou, kterou žádost prošla.
 *
 * Stopa není ozdoba: u řetězu je nejčastější otázka „proč to skončilo
 * zrovna tady?“, a bez ní se na ni odpovídá laděním.
 */
final readonly class ApprovalDecision
{
    /** @param list<string> $consulted */
    private function __construct(
        public bool $isApproved,
        public ?string $approvedBy,
        public string $reason,
        public array $consulted,
    ) {
    }

    public static function approvedBy(string $approver): self
    {
        return new self(true, $approver, 'Schváleno.', [$approver]);
    }

    public static function rejected(string $reason): self
    {
        return new self(false, null, $reason, []);
    }

    /** Přidá na začátek stopy toho, kdo žádost viděl, ale nevyřídil. */
    public function afterConsulting(string $approver): self
    {
        return new self($this->isApproved, $this->approvedBy, $this->reason, [$approver, ...$this->consulted]);
    }
}
