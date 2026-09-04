<?php

declare(strict_types=1);

namespace After;

/** Reklamace — uplatnění vady zboží po dodání. */
final class Complaint
{
    public function __construct(public readonly string $orderNumber)
    {
    }
}
