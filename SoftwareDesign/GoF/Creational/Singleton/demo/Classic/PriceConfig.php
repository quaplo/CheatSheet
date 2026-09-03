<?php

declare(strict_types=1);

namespace Classic;

/**
 * KLASICKÝ SINGLETON — přesně jak ho popsali GoF.
 *
 * Vypadá nevinně a pohodlně: kdekoli v aplikaci zavoláš
 * `PriceConfig::getInstance()` a máš konfiguraci.
 *
 * Demo ukazuje, co za to platíš. Není to seznam názorů —
 * jsou to věci, které si můžeš spustit.
 */
final class PriceConfig
{
    private static ?self $instance = null;

    public int $vatPercent = 21;
    public int $freeShippingFromCents = 150000;

    /** Privátní konstruktor — nikdo jiný instanci nevytvoří. */
    private function __construct()
    {
    }

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    /** Klonování a serializace se musí zakázat, jinak singleton není singleton. */
    private function __clone()
    {
    }

    public function __wakeup(): void
    {
        throw new \LogicException('Singleton nelze deserializovat.');
    }
}
