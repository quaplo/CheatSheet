<?php

declare(strict_types=1);

enum Classification: string
{
    case Core = 'Core Domain';
    case Supporting = 'Supporting Subdomain';
    case Generic = 'Generic Subdomain';

    /**
     * Evansova doporučení pro každý typ, převedená do jedné věty.
     */
    public function recommendation(): string
    {
        return match ($this) {
            self::Core => 'stavět sami, nejlepšími lidmi, hledat hluboký model',
            self::Supporting => 'stavět sami, ale jednoduše — bez ambicí',
            self::Generic => 'koupit, stáhnout, nebo zadat ven',
        };
    }

    /** Kdo na tom má podle Evanse pracovat. */
    public function staffing(): string
    {
        return match ($this) {
            self::Core => 'nejzkušenější lidé',
            self::Supporting => 'kdokoli z týmu',
            self::Generic => 'nikdo z jádrových vývojářů',
        };
    }
}
