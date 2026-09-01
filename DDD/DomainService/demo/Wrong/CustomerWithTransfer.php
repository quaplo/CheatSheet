<?php

declare(strict_types=1);

namespace Wrong;

/**
 * ANTI-PŘÍKLAD: operace přilepená na entitu.
 *
 * `$alice->transferTo($bob, 500)` se čte hezky, ale skrývá dvě potíže:
 *
 *   1. ASYMETRIE — proč metoda patří zdroji a ne cíli? Pro doménu
 *      jsou obě strany rovnocenné; kód tvrdí něco jiného.
 *
 *   2. CIZÍ AGREGÁT — Alice sahá do Boba a mění ho. Tím se ruší
 *      hranice agregátu a pravidlo „jedna transakce = jeden agregát“
 *      přestává mít smysl.
 *
 * Druhý bod je ten vážný. První je jen signál, že něco nesedí.
 */
final class CustomerWithTransfer
{
    private function __construct(
        public readonly string $id,
        public readonly string $name,
        private int $points,
    ) {
    }

    public static function register(string $id, string $name, int $points): self
    {
        return new self($id, $name, $points);
    }

    public function transferTo(self $recipient, int $points): void
    {
        if ($points > $this->points) {
            throw new \DomainException('Nedostatek bodů.');
        }

        $this->points -= $points;

        // ← Tady měním CIZÍ agregát. Odsud vede cesta k tomu, že se
        //   při jednom uložení zapisují dva agregáty a nikdo neví,
        //   co se stane, když druhý zápis selže.
        $recipient->points += $points - intdiv($points * 10, 100);
    }

    public function points(): int
    {
        return $this->points;
    }
}
