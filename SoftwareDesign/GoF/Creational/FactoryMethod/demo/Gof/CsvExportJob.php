<?php

declare(strict_types=1);

namespace Gof;

final class CsvExportJob extends ExportJob
{
    protected function createDocument(): Document
    {
        return new CsvDocument();
    }
}
