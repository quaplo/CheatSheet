<?php

declare(strict_types=1);

namespace WithoutStep3;

/**
 * Čtyři cykly červená–zelená, ani jednou refaktoring.
 *
 * Každá metoda vznikla proto, aby prošel jeden test, a pravidla
 * si nese sama. Kód je správný — testy procházejí — a přesto se
 * čtvrté pravidlo muselo psát třikrát.
 */
final class PasswordPolicy
{
    public function isValid(string $password): bool
    {
        if (strlen($password) < 8) {
            return false;
        }

        if (preg_match('/\d/', $password) !== 1) {
            return false;
        }

        if (preg_match('/[A-Z]/', $password) !== 1) {
            return false;
        }

        if (str_contains($password, ' ')) {
            return false;
        }

        return true;
    }

    /** @return list<string> */
    public function problemsWith(string $password): array
    {
        $problems = [];

        if (strlen($password) < 8) {
            $problems[] = 'heslo musí mít aspoň 8 znaků';
        }

        if (preg_match('/\d/', $password) !== 1) {
            $problems[] = 'heslo musí obsahovat číslici';
        }

        if (preg_match('/[A-Z]/', $password) !== 1) {
            $problems[] = 'heslo musí obsahovat velké písmeno';
        }

        if (str_contains($password, ' ')) {
            $problems[] = 'heslo nesmí obsahovat mezeru';
        }

        return $problems;
    }

    public function firstProblem(string $password): ?string
    {
        if (strlen($password) < 8) {
            return 'heslo musí mít aspoň 8 znaků';
        }

        if (preg_match('/\d/', $password) !== 1) {
            return 'heslo musí obsahovat číslici';
        }

        if (preg_match('/[A-Z]/', $password) !== 1) {
            return 'heslo musí obsahovat velké písmeno';
        }

        if (str_contains($password, ' ')) {
            return 'heslo nesmí obsahovat mezeru';
        }

        return null;
    }
}
