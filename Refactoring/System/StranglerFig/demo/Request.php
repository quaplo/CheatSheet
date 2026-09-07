<?php

declare(strict_types=1);

/**
 * Požadavek, který přijde do systému.
 *
 * Fasáda se podle něj rozhoduje, kam ho poslat — typicky podle cesty,
 * ale může to být cokoli, co jde z požadavku vyčíst.
 */
final readonly class Request
{
    public function __construct(
        public string $capability,
        public string $path,
    ) {
    }
}
