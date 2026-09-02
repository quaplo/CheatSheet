<?php

declare(strict_types=1);

/**
 * Příkaz, který umí vrátit svůj vlastní efekt.
 *
 * Záměrně je to samostatné rozhraní: ne každá operace jde vrátit
 * (odeslaný e-mail už zpátky nevezmeš) a typ to má říkat nahlas.
 */
interface UndoableCommand extends Command
{
    public function undo(): void;
}
