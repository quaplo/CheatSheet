<?php

declare(strict_types=1);

namespace Gof;

final class JsonDocument implements Document
{
    public function render(array $data): string
    {
        return (string) json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function extension(): string
    {
        return 'json';
    }
}
