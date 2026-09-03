<?php

declare(strict_types=1);

namespace Saga;

/**
 * Jeden krok ságy.
 *
 * Dvojice execute/compensate je celý pattern. Podstatné je, že
 * compensate() NENÍ rollback — je to další doménová operace, která
 * ruší následky té první. Sklad uvolní rezervaci, platba vystaví
 * dobropis. Nic se „nevrací“, jen se přidává další fakt.
 */
interface SagaStep
{
    public function name(): string;

    /**
     * Provede krok ve vlastní lokální transakci cizího kontextu.
     *
     * @throws \RuntimeException když krok selže
     */
    public function execute(SagaState $state): void;

    /**
     * Zruší následky kroku. MUSÍ být idempotentní — při opakování
     * kompenzace se nesmí nic zdvojit.
     */
    public function compensate(SagaState $state): void;

    /**
     * Je krok nevratný? Po pivotním kroku už se zpět nejde —
     * dá se jen opakovat dopředu.
     */
    public function isPivot(): bool;
}
