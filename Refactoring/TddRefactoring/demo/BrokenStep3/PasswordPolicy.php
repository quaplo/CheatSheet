<?php

declare(strict_types=1);

namespace BrokenStep3;

/**
 * Třetí krok udělaný špatně.
 *
 * Vypadá to lépe než verze s foreach — a chová se to jinak.
 * array_filter() zachovává klíče, takže když projde první
 * pravidlo a selže třetí, není v poli index 0.
 *
 * Tohle je přesně ten druh chyby, kvůli které se refaktoruje
 * jen v zelené: testy z prvních dvou kroků ji chytí hned.
 */
final class PasswordPolicy
{
    /** @return array<int, string> */
    public function problemsWith(string $password): array
    {
        return array_filter(
            array_map(
                static fn (array $rule): ?string => $rule['isSatisfiedBy']($password) ? null : $rule['problem'],
                self::rules(),
            ),
            static fn (?string $problem): bool => $problem !== null,
        );
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
