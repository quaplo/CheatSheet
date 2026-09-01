<?php

declare(strict_types=1);

namespace Before;

/**
 * ANTI-PŘÍKLAD: anemická entita.
 *
 * Samé gettery a settery, žádné chování. Vypadá to nevinně a v každém
 * druhém projektu to tak je — jenže tahle třída nehlídá nic. Kdokoli
 * ji může uvést do stavu, který v doméně nedává smysl.
 */
final class AnemicCustomer
{
    public function __construct(
        private string $id,
        private string $email,
        private string $companyName,
        private int $loyaltyPoints,
        private string $tier,          // ← uložená úroveň, může se rozejít s body
        private bool $isActive,
    ) {
    }

    public function getId(): string { return $this->id; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): void { $this->email = $email; }
    public function getCompanyName(): string { return $this->companyName; }
    public function setCompanyName(string $name): void { $this->companyName = $name; }
    public function getLoyaltyPoints(): int { return $this->loyaltyPoints; }
    public function setLoyaltyPoints(int $points): void { $this->loyaltyPoints = $points; }
    public function getTier(): string { return $this->tier; }
    public function setTier(string $tier): void { $this->tier = $tier; }
    public function isActive(): bool { return $this->isActive; }
    public function setActive(bool $active): void { $this->isActive = $active; }
}
