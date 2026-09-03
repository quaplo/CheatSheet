<?php

declare(strict_types=1);

/**
 * Composite nad příkazy: skupina se chová jako jeden příkaz.
 *
 * Volající nepozná, jestli drží jednu operaci, nebo dvacet.
 * Undo běží v OPAČNÉM pořadí — jinak by se kroky rušily špatně.
 */
final class MacroCommand implements UndoableCommand
{
    /** @var list<UndoableCommand> */
    private array $commands;

    public function __construct(
        private readonly string $name,
        UndoableCommand ...$commands,
    ) {
        $this->commands = array_values($commands);
    }

    public function execute(): void
    {
        foreach ($this->commands as $command) {
            $command->execute();
        }
    }

    public function undo(): void
    {
        foreach (array_reverse($this->commands) as $command) {
            $command->undo();
        }
    }

    public function describe(): string
    {
        $count = count($this->commands);

        $word = match (true) {
            $count === 1 => 'krok',
            $count < 5 => 'kroky',
            default => 'kroků',
        };

        return sprintf('%s (%d %s)', $this->name, $count, $word);
    }
}
