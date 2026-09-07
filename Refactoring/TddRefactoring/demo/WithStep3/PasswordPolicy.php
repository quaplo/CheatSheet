<?php

declare(strict_types=1);

namespace WithStep3;

/**
 * Tytéž čtyři cykly, ale s třetím krokem.
 *
 * Pravidla jsou na jednom místě. isValid() ani firstProblem()
 * už žádné pravidlo neznají — ptají se na ně.
 *
 * Tenhle tvar nikdo nenavrhl dopředu. Vznikl ve třetím kroku
 * čtvrtého cyklu, kdy bylo poprvé vidět, že pravidla jsou čtyři
 * a chovají se stejně.
 */
final class PasswordPolicy
{
    /** @return list<string> */
    public function problemsWith(string $password): array
    {
        $problems = [];

        foreach (self::rules() as $rule) {
            if (!$rule['isSatisfiedBy']($password)) {
                $problems[] = $rule['problem'];
            }
        }

        return $problems;
    }

    public function isValid(string $password): bool
    {
        return $this->problemsWith($password) === [];
    }

    public function firstProblem(string $password): ?string
    {
        return $this->problemsWith($password)[0] ?? null;
    }

    /** @return list<array{isSatisfiedBy: callable(string): bool, problem: string}> */
    private static function rules(): array
    {
        return [
            [
                'isSatisfiedBy' => static fn (string $p): bool => strlen($p) >= 8,
                'problem' => 'heslo musí mít aspoň 8 znaků',
            ],
            [
                'isSatisfiedBy' => static fn (string $p): bool => preg_match('/\d/', $p) === 1,
                'problem' => 'heslo musí obsahovat číslici',
            ],
            [
                'isSatisfiedBy' => static fn (string $p): bool => preg_match('/[A-Z]/', $p) === 1,
                'problem' => 'heslo musí obsahovat velké písmeno',
            ],
            [
                'isSatisfiedBy' => static fn (string $p): bool => !str_contains($p, ' '),
                'problem' => 'heslo nesmí obsahovat mezeru',
            ],
        ];
    }
}
