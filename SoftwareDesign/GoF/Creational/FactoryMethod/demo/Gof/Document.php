<?php

declare(strict_types=1);

namespace Gof;

/** Produkt, který továrna vyrábí. */
interface Document
{
    public function render(array $data): string;

    public function extension(): string;
}
