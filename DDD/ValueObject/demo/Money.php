<?php

declare(strict_types=1);

/**
 * Peněžní částka.
 *
 * Učebnicový value object: nemá identitu (dvě stokoruny jsou tatáž hodnota),
 * je neměnná, sama se hlídá a nese chování, které k penězům patří.
 *
 * Částka je vždy v setinách měnové jednotky (haléře, centy) jako int.
 * Float na peníze nepatří — 0.1 + 0.2 !== 0.3.
 */
final readonly class Money
{
    private function __construct(
        public int $amountInCents,
        public Currency $currency,
    ) {
    }

    public static function fromCents(int $amountInCents, Currency $currency): self
    {
        return new self($amountInCents, $currency);
    }

    public static function zero(Currency $currency): self
    {
        return new self(0, $currency);
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amountInCents + $other->amountInCents, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amountInCents - $other->amountInCents, $this->currency);
    }

    public function multipliedBy(int $factor): self
    {
        return new self($this->amountInCents * $factor, $this->currency);
    }

    /**
     * Rozdělí částku na díly tak, aby se neztratil ani haléř.
     *
     * Naivní dělení 100,00 Kč na tři díly dá 3× 33,33 Kč a jeden haléř
     * zmizí. Tady se zbytek rozdělí mezi první díly.
     *
     * @return list<self>
     */
    public function allocate(int $parts): array
    {
        if ($parts < 1) {
            throw new InvalidArgumentException('Počet dílů musí být alespoň 1.');
        }

        $base = intdiv($this->amountInCents, $parts);
        $remainder = $this->amountInCents - $base * $parts;

        $result = [];

        for ($i = 0; $i < $parts; $i++) {
            $result[] = new self($base + ($i < $remainder ? 1 : 0), $this->currency);
        }

        return $result;
    }

    /**
     * Rovnost se posuzuje podle hodnoty, ne podle instance.
     * PHP nemá přetěžování operátorů, takže rovnost je metoda.
     */
    public function equals(self $other): bool
    {
        return $this->amountInCents === $other->amountInCents
            && $this->currency === $other->currency;
    }

    public function isGreaterThan(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amountInCents > $other->amountInCents;
    }

    public function isZero(): bool
    {
        return $this->amountInCents === 0;
    }

    public function format(): string
    {
        return number_format($this->amountInCents / 100, 2, ',', ' ') . ' ' . $this->currency->symbol();
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(sprintf(
                'Nelze kombinovat %s a %s.',
                $this->currency->value,
                $other->currency->value,
            ));
        }
    }
}
