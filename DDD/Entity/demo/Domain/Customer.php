<?php

declare(strict_types=1);

namespace Domain;

/**
 * ENTITA.
 *
 * Tři věci ji dělají entitou, a všechny tři jsou vidět níž:
 *
 *  1. Má IDENTITU, která přežije změnu všech ostatních atributů.
 *  2. Rovná se podle identity, ne podle obsahu.
 *  3. MĚNÍ SE v čase — a právě proto ji identita drží pohromadě.
 *
 * Ten třetí bod je rozdíl proti value objectu. Hodnota se nemění (pětka
 * se nikdy nestane šestkou); entita se mění pořád a zůstává sama sebou.
 * Proto tahle třída NENÍ readonly — na rozdíl od skoro všeho ostatního
 * v tomhle katalogu.
 *
 * Všimni si taky, co tu není: žádný setter. Každá změna má jméno
 * doménové operace a hlídá si svoje pravidlo.
 */
final class Customer
{
    private function __construct(
        public readonly CustomerId $id,      // identita se nemění nikdy
        private EmailAddress $email,
        private string $companyName,
        private int $loyaltyPoints,
        private bool $isActive,
    ) {
    }

    public static function register(CustomerId $id, EmailAddress $email, string $companyName): self
    {
        if (trim($companyName) === '') {
            throw new \InvalidArgumentException('Název firmy nesmí být prázdný.');
        }

        return new self($id, $email, trim($companyName), loyaltyPoints: 0, isActive: true);
    }

    /** Rekonstrukce z úložiště — nemá procházet registračními pravidly. */
    public static function reconstitute(
        CustomerId $id,
        EmailAddress $email,
        string $companyName,
        int $loyaltyPoints,
        bool $isActive,
    ): self {
        return new self($id, $email, $companyName, $loyaltyPoints, $isActive);
    }

    // --- Rovnost -----------------------------------------------------------

    /**
     * Rovnost POUZE podle identity.
     *
     * Dvě instance téhož zákazníka s různým počtem bodů jsou pořád týž
     * zákazník. Kdyby se porovnávaly atributy, znamenala by každá změna
     * jinou entitu — a nešlo by ji sledovat v čase.
     */
    public function equals(self $other): bool
    {
        return $this->id->equals($other->id);
    }

    // --- Doménové operace, ne settery --------------------------------------

    public function changeEmail(EmailAddress $newEmail): void
    {
        if ($this->isActive === false) {
            throw new \LogicException('Neaktivnímu zákazníkovi nelze měnit e-mail.');
        }

        $this->email = $newEmail;
    }

    public function rename(string $newName): void
    {
        if (trim($newName) === '') {
            throw new \InvalidArgumentException('Název firmy nesmí být prázdný.');
        }

        $this->companyName = trim($newName);
    }

    public function earnPoints(int $points): void
    {
        if ($points <= 0) {
            throw new \InvalidArgumentException('Připsat lze jen kladný počet bodů.');
        }

        if ($this->isActive === false) {
            throw new \LogicException('Neaktivní zákazník body nesbírá.');
        }

        $this->loyaltyPoints += $points;
    }

    public function redeemPoints(int $points): void
    {
        if ($points > $this->loyaltyPoints) {
            throw new \LogicException(sprintf(
                'Nelze uplatnit %d bodů, zákazník má jen %d.',
                $points,
                $this->loyaltyPoints,
            ));
        }

        $this->loyaltyPoints -= $points;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    // --- Dotazy: řekni si o odpověď, ne o data -----------------------------

    /** Úroveň se nikde neukládá — vyplývá z bodů, takže se nemůže rozejít. */
    public function tier(): LoyaltyTier
    {
        return LoyaltyTier::forPoints($this->loyaltyPoints);
    }

    public function discountPercent(): int
    {
        return $this->isActive ? $this->tier()->discountPercent() : 0;
    }

    public function canRedeem(int $points): bool
    {
        return $this->isActive && $points <= $this->loyaltyPoints;
    }

    public function points(): int
    {
        return $this->loyaltyPoints;
    }

    public function email(): EmailAddress
    {
        return $this->email;
    }

    public function companyName(): string
    {
        return $this->companyName;
    }
}
