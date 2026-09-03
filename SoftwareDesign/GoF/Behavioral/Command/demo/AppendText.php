<?php

declare(strict_types=1);

/**
 * Vrácení bez ukládání stavu: příkaz ví, co udělal, a umí to odečíst.
 *
 * Tohle je levnější varianta undo — nedrží se kopie dokumentu,
 * jen délka přidaného textu.
 */
final class AppendText implements UndoableCommand
{
    public function __construct(
        private readonly TextDocument $document,
        private readonly string $text,
    ) {
    }

    public function execute(): void
    {
        $this->document->append($this->text);
    }

    public function undo(): void
    {
        $this->document->removeLast(mb_strlen($this->text));
    }

    public function describe(): string
    {
        return sprintf('připsat „%s“', $this->text);
    }
}
