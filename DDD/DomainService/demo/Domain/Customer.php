<?php

declare(strict_types=1);

namespace Domain;

/**
 * Kořen agregátu. Hlídá si SVÁ pravidla — a jen svá.
 *
 * Ví, že nemůže uplatnit víc bodů, než má, a že neaktivní zákazník
 * body nesbírá. Neví ale nic o převodu mezi dvěma zákazníky — protože
 * to není pravidlo o něm, ale o dvojici.
 */
final class Customer
{
    private function __construct(
        public readonly CustomerId $id,
        public readonly string $name,
        private int $points,
        private bool $isActive,
    ) {
    }

    public static function register(string $id, string $name, int $points = 0): self
    {
        return new self(CustomerId::fromString($id), $name, $points, isActive: true);
    }

    public function earnPoints(int $points): void
    {
        if ($points <= 0) {
            throw new \InvalidArgumentException('Připsat lze jen kladný počet bodů.');
        }

        if ($this->isActive === false) {
            throw new \DomainException(sprintf('Neaktivní zákazník %s body nesbírá.', $this->name));
        }

        $this->points += $points;
    }

    public function redeemPoints(int $points): void
    {
        if ($points > $this->points) {
            throw new \DomainException(sprintf(
                '%s má jen %d bodů, nelze uplatnit %d.',
                $this->name,
                $this->points,
                $points,
            ));
        }

        $this->points -= $points;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function points(): int
    {
        return $this->points;
    }
}
