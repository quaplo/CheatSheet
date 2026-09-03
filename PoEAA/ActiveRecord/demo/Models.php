<?php

declare(strict_types=1);

/**
 * Konkrétní Active Record model.
 *
 * Všimni si, jak je krátký. Tohle je hlavní důvod, proč pattern
 * existuje a proč na něm stojí polovina úspěšných PHP aplikací:
 * na CRUD nad tabulkou je Data Mapper zbytečná ceremonie.
 */
final class Customer extends ActiveRecord
{
    protected static function table(): string
    {
        return 'customers';
    }

    protected static function primaryKey(): string
    {
        return 'id';
    }

    /** @return list<Order> */
    public function orders(): array
    {
        return Order::where('customer_id', $this->id);
    }
}

final class Order extends ActiveRecord
{
    protected static function table(): string
    {
        return 'orders';
    }

    protected static function primaryKey(): string
    {
        return 'number';
    }

    /** Vazba načtená na požádání — právě tudy vzniká N+1. */
    public function customer(): ?Customer
    {
        return Customer::find($this->customer_id);
    }

    /**
     * Doménové pravidlo přímo v modelu.
     *
     * Samo o sobě je to v pořádku. Problém je, že se k němu nedá
     * dostat bez databáze — objekt vzniká jen jejím prostřednictvím.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['nová', 'potvrzená'], true);
    }

    public function cancel(): void
    {
        if (!$this->canBeCancelled()) {
            throw new DomainException(
                sprintf('Objednávku ve stavu „%s“ nelze zrušit.', $this->status),
            );
        }

        $this->status = 'zrušená';
        $this->save();
    }

    public function format(): string
    {
        return sprintf('%s — %s za %s', $this->number, $this->status, formatPrice((int) $this->total_cents));
    }
}

function formatPrice(int $cents): string
{
    return number_format($cents / 100, 2, ',', ' ') . ' Kč';
}
