<?php

declare(strict_types=1);

namespace Application;

/** Zjednodušený publisher — viz pattern Domain Event. */
final class EventPublisher
{
    /** @var list<string> */
    public array $published = [];

    /** @param array<string, mixed> $payload */
    public function publish(string $name, array $payload): void
    {
        $this->published[] = $name;
    }
}
