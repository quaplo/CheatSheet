<?php

declare(strict_types=1);

namespace Notification;

/** Obecná podoblast. */
final readonly class Template
{
    public function __construct(public string $name)
    {
    }
}
