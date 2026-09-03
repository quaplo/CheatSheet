<?php

declare(strict_types=1);

/**
 * Invoker — spouští příkazy a drží historii.
 *
 * O tom, co jednotlivé příkazy dělají, neví nic. Právě proto
 * může undo/redo fungovat pro libovolnou operaci, i pro tu,
 * která vznikne až zítra.
 */
final class History
{
    /** @var list<UndoableCommand> */
    private array $done = [];

    /** @var list<UndoableCommand> */
    private array $undone = [];

    public function run(Command $command): void
    {
        $command->execute();

        if ($command instanceof UndoableCommand) {
            $this->done[] = $command;
            $this->undone = [];
        }
    }

    public function undo(): ?string
    {
        $command = array_pop($this->done);

        if ($command === null) {
            return null;
        }

        $command->undo();
        $this->undone[] = $command;

        return $command->describe();
    }

    public function redo(): ?string
    {
        $command = array_pop($this->undone);

        if ($command === null) {
            return null;
        }

        $command->execute();
        $this->done[] = $command;

        return $command->describe();
    }

    /** @return list<string> */
    public function describeDone(): array
    {
        return array_map(static fn (UndoableCommand $c): string => $c->describe(), $this->done);
    }
}
