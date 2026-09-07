<?php

declare(strict_types=1);

/**
 * Testovací sada tak, jak vyrostla ve čtyřech cyklech.
 *
 * Žádný framework — tenhle repozitář je bez závislostí. Tvar
 * odpovídá tomu, co by v PHPUnit byly jednotlivé testovací metody.
 */
final class Suite
{
    /** @return list<array{cycle: int, name: string, run: callable(object): mixed, expected: mixed}> */
    public static function tests(): array
    {
        return [
            // ① cyklus: délka
            [
                'cycle' => 1,
                'name' => 'krátké heslo neprojde',
                'run' => static fn (object $p): bool => $p->isValid('Ab1'),
                'expected' => false,
            ],
            [
                'cycle' => 1,
                'name' => 'dost dlouhé heslo projde',
                'run' => static fn (object $p): bool => $p->isValid('Heslo123'),
                'expected' => true,
            ],

            // ② cyklus: číslice
            [
                'cycle' => 2,
                'name' => 'heslo bez číslice neprojde',
                'run' => static fn (object $p): bool => $p->isValid('HesloHeslo'),
                'expected' => false,
            ],

            // ③ cyklus: seznam problémů
            [
                'cycle' => 3,
                'name' => 'platné heslo nemá problémy',
                'run' => static fn (object $p): array => $p->problemsWith('Heslo123'),
                'expected' => [],
            ],
            [
                'cycle' => 3,
                'name' => 'krátké heslo bez číslice má dva problémy',
                'run' => static fn (object $p): array => $p->problemsWith('Ab'),
                'expected' => ['heslo musí mít aspoň 8 znaků', 'heslo musí obsahovat číslici'],
            ],
            [
                'cycle' => 3,
                'name' => 'heslo bez velkého písmene má jeden problém',
                'run' => static fn (object $p): array => $p->problemsWith('heslo1234'),
                'expected' => ['heslo musí obsahovat velké písmeno'],
            ],

            // ④ cyklus: mezera a první problém pro formulář
            [
                'cycle' => 4,
                'name' => 'heslo s mezerou neprojde',
                'run' => static fn (object $p): bool => $p->isValid('Heslo 123'),
                'expected' => false,
            ],
            [
                'cycle' => 4,
                'name' => 'mezera je hlášená jako problém',
                'run' => static fn (object $p): array => $p->problemsWith('Heslo 123'),
                'expected' => ['heslo nesmí obsahovat mezeru'],
            ],
            [
                'cycle' => 4,
                'name' => 'platné heslo nemá první problém',
                'run' => static fn (object $p): ?string => $p->firstProblem('Heslo123'),
                'expected' => null,
            ],
            [
                'cycle' => 4,
                'name' => 'první problém krátkého hesla je délka',
                'run' => static fn (object $p): ?string => $p->firstProblem('Ab'),
                'expected' => 'heslo musí mít aspoň 8 znaků',
            ],
            [
                'cycle' => 4,
                'name' => 'první problém je i ten, který není první v pořadí',
                'run' => static fn (object $p): ?string => $p->firstProblem('heslo1234'),
                'expected' => 'heslo musí obsahovat velké písmeno',
            ],
        ];
    }

    /** @return array{passed: int, failed: list<string>} */
    public static function runAgainst(object $policy): array
    {
        $passed = 0;
        $failed = [];

        foreach (self::tests() as $test) {
            if ($test['run']($policy) === $test['expected']) {
                ++$passed;
                continue;
            }

            $failed[] = $test['name'];
        }

        return ['passed' => $passed, 'failed' => $failed];
    }
}
