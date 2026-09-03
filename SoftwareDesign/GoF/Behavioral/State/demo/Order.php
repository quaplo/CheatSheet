<?php

declare(strict_types=1);

/**
 * Kontext. Neobsahuje jedinou podmínku o stavu — jen deleguje.
 *
 * Objednávka je neměnná, takže přechod vrací novou instanci. Původní GoF
 * popis kontext mění na místě; s neměnnými entitami je tohle přirozenější
 * a odpadá u toho celá kategorie chyb se sdílenou instancí.
 */
final readonly class Order
{
    private function __construct(
        public string $number,
        public OrderState $state,
    ) {
    }

    public static function place(string $number): self
    {
        return new self($number, new NewOrder());
    }

    /** Rekonstrukce z úložiště — v databázi je uložené jen jméno stavu. */
    public static function reconstitute(string $number, string $stateName): self
    {
        return new self($number, OrderState::fromName($stateName));
    }

    public function pay(): self
    {
        return new self($this->number, $this->state->pay());
    }

    public function ship(): self
    {
        return new self($this->number, $this->state->ship());
    }

    public function deliver(): self
    {
        return new self($this->number, $this->state->deliver());
    }

    public function cancel(): self
    {
        return new self($this->number, $this->state->cancel());
    }

    public function status(): string
    {
        return $this->state->name();
    }
}
