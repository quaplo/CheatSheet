<?php

declare(strict_types=1);

namespace Gof;

/**
 * GoF FACTORY METHOD.
 *
 * Rozdíl proti pojmenovanému konstruktoru je zásadní: tady o tom,
 * CO se vytvoří, rozhoduje **potomek**, ne volající.
 *
 * Základní třída zná celý postup exportu — jen neví, jaký dokument
 * z něj vypadne. To doplní podtřída metodou `createDocument()`,
 * což je ta „tovární metoda“ v původním smyslu.
 */
abstract class ExportJob
{
    /**
     * Kostra, která je pro všechny exporty stejná.
     *
     * @param list<array<string, string|int>> $rows
     */
    final public function run(array $rows): string
    {
        $document = $this->createDocument();      // ← rozhodne potomek

        $content = $document->render($rows);
        $filename = sprintf('export-%s.%s', date('Y-m-d'), $document->extension());

        return sprintf("%s  (%d B)\n%s", $filename, strlen($content), $content);
    }

    /** Tovární metoda. Co vznikne, ví jen potomek. */
    abstract protected function createDocument(): Document;
}
