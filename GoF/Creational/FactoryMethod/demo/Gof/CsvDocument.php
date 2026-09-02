<?php

declare(strict_types=1);

namespace Gof;

final class CsvDocument implements Document
{
    public function render(array $data): string
    {
        $lines = [implode(';', array_keys($data[0]))];

        foreach ($data as $row) {
            $lines[] = implode(';', $row);
        }

        return implode("\n", $lines);
    }

    public function extension(): string
    {
        return 'csv';
    }
}
