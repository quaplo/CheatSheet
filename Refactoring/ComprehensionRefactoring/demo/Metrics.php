<?php

declare(strict_types=1);

/**
 * Metriky čitelnosti, které jdou spočítat z tokenů.
 *
 * Žádná z nich neměří „srozumitelnost" — to se změřit nedá. Měří
 * věci, které se srozumitelností souvisejí: kolik je v kódu větvení,
 * jak hluboko se zanořuje a kolik čísel a jmen nic neříká.
 */
final class Metrics
{
    /** @return array{complexity: int, depth: int, magicNumbers: int, shortNames: int, namedConcepts: int} */
    public static function of(string $file): array
    {
        $tokens = token_get_all(file_get_contents($file));

        $complexity = 1;
        $depth = 0;
        $maxDepth = 0;
        $magic = 0;
        $short = 0;
        $named = [];
        $inFunction = false;

        foreach ($tokens as $i => $token) {
            if (is_array($token)) {
                // Větvení zvyšuje cyklomatickou složitost.
                if (in_array($token[0], [T_IF, T_ELSEIF, T_FOREACH, T_FOR, T_WHILE, T_CASE, T_CATCH], true)) {
                    ++$complexity;
                }

                if ($token[0] === T_BOOLEAN_AND || $token[0] === T_BOOLEAN_OR) {
                    ++$complexity;
                }

                if ($token[0] === T_FUNCTION) {
                    $inFunction = true;
                    $depth = 0;
                }

                // Číslo, které není 0 ani 1 a není v konstantě.
                if ($token[0] === T_LNUMBER || $token[0] === T_DNUMBER) {
                    if ($inFunction && !in_array($token[1], ['0', '1', '2', '1.0'], true)) {
                        ++$magic;
                    }
                }

                // Proměnná s jednopísmenným jménem.
                if ($token[0] === T_VARIABLE && strlen($token[1]) <= 2 && $token[1] !== '$i') {
                    ++$short;
                }

                // Pojmenované konstanty a privátní metody = pojmy, které kód zná.
                if ($token[0] === T_STRING && preg_match('/^(is|has)[A-Z]/', $token[1])) {
                    $named[$token[1]] = true;
                }

                if ($token[0] === T_STRING && preg_match('/^[A-Z][A-Z_]{3,}$/', $token[1])) {
                    $named[$token[1]] = true;
                }

                continue;
            }

            if ($token === '{') {
                ++$depth;
                $maxDepth = max($maxDepth, $depth);
            } elseif ($token === '}') {
                --$depth;
            }
        }

        return [
            'complexity' => $complexity,
            'depth' => $maxDepth,
            'magicNumbers' => $magic,
            'shortNames' => $short,
            'namedConcepts' => count($named),
        ];
    }
}
