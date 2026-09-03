<?php

declare(strict_types=1);

/** Jeden ohraničený kontext — a hlavně tým, který ho vlastní. */
final readonly class Context
{
    public function __construct(
        public string $name,
        public string $team,
        public string $meaningOfCustomer,
    ) {
    }
}
