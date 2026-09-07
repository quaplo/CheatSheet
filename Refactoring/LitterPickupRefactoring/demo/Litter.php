<?php

declare(strict_types=1);

/**
 * Detektor odpadků.
 *
 * Hledá čtyři věci, které v PHP najde i statická analýza — a právě
 * proto jsou to dobré příklady odpadků: poznají se bez přemýšlení.
 */
final class Litter
{
    /** @return array<string, list<string>> nález => seznam konkrétních míst */
    public static function in(string $file): array
    {
        $code = file_get_contents($file);
        $tokens = token_get_all($code);

        return [
            'nepoužitý import' => self::unusedImports($tokens),
            'mrtvá privátní metoda' => self::deadPrivateMethods($tokens),
            'jednopísmenná proměnná' => self::shortVariables($tokens),
            'duplicitní literál' => self::duplicateLiterals($tokens),
            'magické číslo v těle metody' => self::magicNumbers($tokens),
        ];
    }

    /** @param list<array{0:int,1:string,2:int}|string> $tokens */
    private static function unusedImports(array $tokens): array
    {
        $imported = [];
        $used = [];
        $inUse = false;

        foreach ($tokens as $token) {
            if (is_array($token) && $token[0] === T_USE) {
                $inUse = true;
                continue;
            }

            if ($token === ';') {
                $inUse = false;
                continue;
            }

            if (!is_array($token) || $token[0] !== T_STRING) {
                continue;
            }

            if ($inUse) {
                $imported[] = $token[1];
            } else {
                $used[$token[1]] = true;
            }
        }

        return array_values(array_filter($imported, static fn (string $n): bool => !isset($used[$n])));
    }

    /** @param list<array{0:int,1:string,2:int}|string> $tokens */
    private static function deadPrivateMethods(array $tokens): array
    {
        $declared = [];
        $called = [];

        foreach ($tokens as $i => $token) {
            if (!is_array($token)) {
                continue;
            }

            if ($token[0] === T_PRIVATE) {
                $name = self::methodNameAfter($tokens, $i);

                if ($name !== null) {
                    $declared[] = $name;
                }
            }

            // Volání: ->name  nebo  ::name
            if ($token[0] === T_OBJECT_OPERATOR || $token[0] === T_DOUBLE_COLON) {
                $next = self::nextMeaningful($tokens, $i);

                if ($next !== null) {
                    $called[$next] = true;
                }
            }
        }

        return array_values(array_filter($declared, static fn (string $n): bool => !isset($called[$n])));
    }

    /** @param list<array{0:int,1:string,2:int}|string> $tokens */
    private static function shortVariables(array $tokens): array
    {
        $found = [];

        foreach ($tokens as $token) {
            if (is_array($token) && $token[0] === T_VARIABLE && strlen($token[1]) <= 2) {
                $found[$token[1]] = true;
            }
        }

        return array_keys($found);
    }

    /** @param list<array{0:int,1:string,2:int}|string> $tokens */
    private static function duplicateLiterals(array $tokens): array
    {
        $counts = [];

        foreach ($tokens as $token) {
            if (is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING && strlen($token[1]) > 3) {
                $counts[$token[1]] = ($counts[$token[1]] ?? 0) + 1;
            }
        }

        return array_keys(array_filter($counts, static fn (int $c): bool => $c > 1));
    }

    /** @param list<array{0:int,1:string,2:int}|string> $tokens */
    private static function magicNumbers(array $tokens): array
    {
        $found = [];
        $inConst = false;

        foreach ($tokens as $token) {
            if (is_array($token) && $token[0] === T_CONST) {
                $inConst = true;
                continue;
            }

            if ($token === ';') {
                $inConst = false;
                continue;
            }

            if (!$inConst && is_array($token) && $token[0] === T_LNUMBER && !in_array($token[1], ['0', '1'], true)) {
                $found[$token[1]] = true;
            }
        }

        return array_keys($found);
    }

    /** @param list<array{0:int,1:string,2:int}|string> $tokens */
    private static function methodNameAfter(array $tokens, int $from): ?string
    {
        $sawFunction = false;

        for ($i = $from + 1, $n = count($tokens); $i < $n; ++$i) {
            $token = $tokens[$i];

            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_STATIC], true)) {
                continue;
            }

            if (is_array($token) && $token[0] === T_FUNCTION) {
                $sawFunction = true;
                continue;
            }

            if ($sawFunction && is_array($token) && $token[0] === T_STRING) {
                return $token[1];
            }

            return null;
        }

        return null;
    }

    /** @param list<array{0:int,1:string,2:int}|string> $tokens */
    private static function nextMeaningful(array $tokens, int $from): ?string
    {
        for ($i = $from + 1, $n = count($tokens); $i < $n; ++$i) {
            $token = $tokens[$i];

            if (is_array($token) && $token[0] === T_WHITESPACE) {
                continue;
            }

            return is_array($token) && $token[0] === T_STRING ? $token[1] : null;
        }

        return null;
    }
}
