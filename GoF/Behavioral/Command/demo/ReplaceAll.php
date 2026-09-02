<?php

declare(strict_types=1);

/**
 * Vrácení přes uložený stav (Memento).
 *
 * Záměna nejde spolehlivě odečíst — kdyby v textu bylo slovo
 * už předtím, zpětná záměna by ho přepsala taky. Proto si příkaz
 * před provedením uloží snímek a při undo ho vrátí.
 */
final class ReplaceAll implements UndoableCommand
{
    private ?string $contentBefore = null;

    public function __construct(
        private readonly TextDocument $document,
        private readonly string $search,
        private readonly string $replacement,
    ) {
    }

    public function execute(): void
    {
        $this->contentBefore = $this->document->content();
        $this->document->replaceAll($this->search, $this->replacement);
    }

    public function undo(): void
    {
        if ($this->contentBefore === null) {
            throw new LogicException('Nelze vrátit příkaz, který ještě neproběhl.');
        }

        $this->document->restore($this->contentBefore);
    }

    public function describe(): string
    {
        return sprintf('nahradit „%s“ za „%s“', $this->search, $this->replacement);
    }
}
