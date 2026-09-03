<?php

declare(strict_types=1);

/**
 * Příjemce (receiver) — objekt, který skutečnou práci umí.
 *
 * O existenci příkazů neví vůbec nic. To je záměr: příkaz je
 * prostředník mezi tím, kdo operaci vyvolal, a tím, kdo ji provede.
 */
final class TextDocument
{
    private string $content = '';

    public function content(): string
    {
        return $this->content;
    }

    public function length(): int
    {
        return mb_strlen($this->content);
    }

    public function append(string $text): void
    {
        $this->content .= $text;
    }

    public function removeLast(int $length): void
    {
        $this->content = mb_substr($this->content, 0, $this->length() - $length);
    }

    public function replaceAll(string $search, string $replacement): void
    {
        $this->content = str_replace($search, $replacement, $this->content);
    }

    public function restore(string $content): void
    {
        $this->content = $content;
    }
}
