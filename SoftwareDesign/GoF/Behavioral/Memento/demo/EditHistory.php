<?php

declare(strict_types=1);

/**
 * Pečovatel: drží snímky a nic o nich neví.
 *
 * Všimni si typu — pracuje s Memento, ne s OrderMemento. Dovnitř
 * se dostat nemůže, i kdyby chtěl.
 */
final class EditHistory
{
    /** @var list<Memento> */
    private array $snapshots = [];

    public function push(Memento $memento): void
    {
        $this->snapshots[] = $memento;
    }

    public function pop(): ?Memento
    {
        return array_pop($this->snapshots);
    }

    public function count(): int
    {
        return count($this->snapshots);
    }
}
