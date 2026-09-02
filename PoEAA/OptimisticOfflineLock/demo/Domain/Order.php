<?php

declare(strict_types=1);

namespace Domain;

/**
 * Objednávka s VERZÍ.
 *
 * Verze není doménový pojem — zákazníka ani produkťáka nezajímá.
 * Je to technický údaj, který existuje jen kvůli souběžným změnám.
 * Proto je oddělený od zbytku a doména se ho nikdy neptá.
 */
final class Order
{
    private function __construct(
        public readonly string $number,
        private string $note,
        private string $priority,
        public readonly int $version,
    ) {
    }

    public static function place(string $number): self
    {
        return new self($number, note: '', priority: 'běžná', version: 1);
    }

    public static function reconstitute(string $number, string $note, string $priority, int $version): self
    {
        return new self($number, $note, $priority, $version);
    }

    public function changeNote(string $note): void
    {
        $this->note = $note;
    }

    public function changePriority(string $priority): void
    {
        $this->priority = $priority;
    }

    public function note(): string
    {
        return $this->note;
    }

    public function priority(): string
    {
        return $this->priority;
    }
}
