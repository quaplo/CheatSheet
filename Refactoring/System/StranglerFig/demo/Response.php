<?php

declare(strict_types=1);

final readonly class Response
{
    public function __construct(
        public string $body,
        /** Který systém odpověděl — pro měření, ne pro klienta. */
        public string $servedBy,
    ) {
    }
}
