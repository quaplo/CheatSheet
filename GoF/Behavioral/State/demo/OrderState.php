<?php

declare(strict_types=1);

/**
 * Základ všech stavů.
 *
 * Tady je celý trik patternu: **výchozí odpovědí na každou operaci je
 * „nelze“**. Konkrétní stav přepíše jen to, co v něm dovolené je.
 *
 * Ve `switch`i musíš na každý zakázaný případ myslet a napsat ho.
 * Tady na něj myslet nemusíš — zakázáno je všechno, co jsi výslovně
 * nepovolil. Zapomenout se dá jen povolení, a to se pozná hned.
 */
abstract class OrderState
{
    /** Operace, které stav zná. Slouží i pro introspekci. */
    public const array OPERATIONS = ['zaplatit', 'odeslat', 'doručit', 'zrušit'];

    abstract public function name(): string;

    public function pay(): self
    {
        throw IllegalTransition::from($this, 'zaplatit');
    }

    public function ship(): self
    {
        throw IllegalTransition::from($this, 'odeslat');
    }

    public function deliver(): self
    {
        throw IllegalTransition::from($this, 'doručit');
    }

    public function cancel(): self
    {
        throw IllegalTransition::from($this, 'zrušit');
    }

    /**
     * Co v tomhle stavu jde?
     *
     * Zjišťuje se to tak, že se zeptáme, které metody stav skutečně
     * přepsal — protože „přepsáno“ přesně znamená „povoleno“. Díky tomu
     * se seznam nemůže rozejít s chováním.
     *
     * (V produkci bys to spíš deklaroval ručně, nebo použil Symfony
     * Workflow. Tady je to hlavně proto, aby bylo vidět, na čem pattern
     * stojí.)
     *
     * @return list<string>
     */
    final public function allowedOperations(): array
    {
        $allowed = [];

        foreach (['pay' => 'zaplatit', 'ship' => 'odeslat', 'deliver' => 'doručit', 'cancel' => 'zrušit'] as $method => $label) {
            $declaredIn = (new ReflectionMethod($this, $method))->getDeclaringClass()->getName();

            if ($declaredIn !== self::class) {
                $allowed[] = $label;
            }
        }

        return $allowed;
    }

    /** Rekonstrukce z úložiště — v databázi je uložené jen jméno stavu. */
    public static function fromName(string $name): self
    {
        return match ($name) {
            'nová' => new NewOrder(),
            'zaplacená' => new PaidOrder(),
            'odeslaná' => new ShippedOrder(),
            'doručená' => new DeliveredOrder(),
            'zrušená' => new CancelledOrder(),
            default => throw new InvalidArgumentException(sprintf('Neznámý stav „%s“.', $name)),
        };
    }
}
