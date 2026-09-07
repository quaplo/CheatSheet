<?php

declare(strict_types=1);

/**
 * Výpočet LCOM4 — „lack of cohesion of methods".
 *
 * Metriku definoval Hitz a Montazeri jako počet souvislých komponent
 * grafu, kde uzly jsou metody a hrana vede mezi dvěma metodami,
 * které sahají na totéž pole (nebo jedna volá druhou).
 *
 *   LCOM4 = 1  … třída drží pohromadě
 *   LCOM4 ≥ 2  … jsou to ve skutečnosti dvě (a víc) tříd
 *
 * Nejužitečnější není samo číslo, ale ty komponenty: říkají,
 * KUDY tu třídu rozříznout.
 */
final class Lcom
{
    /**
     * @return array{value: int, components: list<list<string>>, fieldsByMethod: array<string, list<string>>}
     */
    public static function analyse(string $file, string $class): array
    {
        $fieldsByMethod = self::fieldsByMethod($file);
        $callsByMethod = self::callsByMethod($file);
        $methods = array_keys($fieldsByMethod);

        // Union-find: metody se spojí, když sdílejí pole nebo se volají.
        $parent = array_combine($methods, $methods);

        $find = static function (string $m) use (&$parent, &$find): string {
            while ($parent[$m] !== $m) {
                $m = $parent[$m];
            }

            return $m;
        };

        $union = static function (string $a, string $b) use (&$parent, $find): void {
            $ra = $find($a);
            $rb = $find($b);

            if ($ra !== $rb) {
                $parent[$ra] = $rb;
            }
        };

        foreach ($methods as $a) {
            foreach ($methods as $b) {
                if ($a >= $b) {
                    continue;
                }

                $sharesField = array_intersect($fieldsByMethod[$a], $fieldsByMethod[$b]) !== [];
                $calls = in_array($b, $callsByMethod[$a] ?? [], true)
                    || in_array($a, $callsByMethod[$b] ?? [], true);

                if ($sharesField || $calls) {
                    $union($a, $b);
                }
            }
        }

        $groups = [];

        foreach ($methods as $m) {
            $groups[$find($m)][] = $m;
        }

        return [
            'value' => count($groups),
            'components' => array_values($groups),
            'fieldsByMethod' => $fieldsByMethod,
        ];
    }

    /** @return array<string, list<string>> metoda => pole, na která sahá */
    private static function fieldsByMethod(string $file): array
    {
        $tokens = array_values(array_filter(
            token_get_all(file_get_contents($file)),
            static fn ($t): bool => !is_array($t)
                || !in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true),
        ));

        $result = [];
        $current = null;
        $depth = 0;

        foreach ($tokens as $i => $token) {
            if (is_array($token) && $token[0] === T_FUNCTION) {
                $name = null;

                for ($j = $i + 1; $j < $i + 4; ++$j) {
                    if (isset($tokens[$j]) && is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                        $name = $tokens[$j][1];
                        break;
                    }
                }

                if ($name !== null && $name !== '__construct') {
                    $current = $name;
                    $result[$current] = [];
                    $depth = 0;
                }

                continue;
            }

            if ($current === null) {
                continue;
            }

            if ($token === '{') {
                ++$depth;
            } elseif ($token === '}') {
                --$depth;

                if ($depth <= 0) {
                    $current = null;
                }
            }

            // $this->pole
            if (is_array($token) && $token[0] === T_VARIABLE && $token[1] === '$this'
                && isset($tokens[$i + 1], $tokens[$i + 2])
                && is_array($tokens[$i + 1]) && $tokens[$i + 1][0] === T_OBJECT_OPERATOR
                && is_array($tokens[$i + 2]) && $tokens[$i + 2][0] === T_STRING
            ) {
                $isCall = ($tokens[$i + 3] ?? null) === '(';

                if (!$isCall) {
                    $result[$current][] = $tokens[$i + 2][1];
                }
            }
        }

        return array_map(static fn (array $f): array => array_values(array_unique($f)), $result);
    }

    /** @return array<string, list<string>> metoda => metody, které volá */
    private static function callsByMethod(string $file): array
    {
        $tokens = array_values(array_filter(
            token_get_all(file_get_contents($file)),
            static fn ($t): bool => !is_array($t)
                || !in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true),
        ));

        $result = [];
        $current = null;
        $depth = 0;

        foreach ($tokens as $i => $token) {
            if (is_array($token) && $token[0] === T_FUNCTION) {
                for ($j = $i + 1; $j < $i + 4; ++$j) {
                    if (isset($tokens[$j]) && is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                        $current = $tokens[$j][1];
                        $result[$current] ??= [];
                        $depth = 0;
                        break;
                    }
                }

                continue;
            }

            if ($current === null) {
                continue;
            }

            if ($token === '{') {
                ++$depth;
            } elseif ($token === '}') {
                --$depth;

                if ($depth <= 0) {
                    $current = null;

                    continue;
                }
            }

            if (is_array($token) && $token[0] === T_VARIABLE && $token[1] === '$this'
                && isset($tokens[$i + 2]) && is_array($tokens[$i + 2]) && $tokens[$i + 2][0] === T_STRING
                && ($tokens[$i + 3] ?? null) === '('
            ) {
                $result[$current][] = $tokens[$i + 2][1];
            }
        }

        return $result;
    }
}
