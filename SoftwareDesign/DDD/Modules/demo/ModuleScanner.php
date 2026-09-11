<?php

declare(strict_types=1);

/**
 * Kontrola hranic mezi moduly — to, co PHP samo neumí.
 *
 * Pravidlo je jediné: do cizího modulu se smí sáhnout jen přes
 * jeho `Api\`. Cokoli jiného je zásah do vnitřku.
 *
 * Tohle je zmenšenina toho, co dělá deptrac. Ne proto, že by ho
 * bylo potřeba nahrazovat — ale aby bylo vidět, že na tu kontrolu
 * stačí padesát řádků, a není tedy důvod ji nemít.
 */
final class ModuleScanner
{
    private const string PUBLIC_SEGMENT = 'Api';

    /** @return list<array{from: string, to: string, file: string}> */
    public static function violationsIn(string $dir): array
    {
        $violations = [];

        foreach (self::phpFiles($dir) as $file) {
            $code = file_get_contents($file);
            $namespace = self::namespaceOf($code);
            $module = self::moduleOf($namespace);

            foreach (self::importsIn($code) as $import) {
                $target = self::moduleOf($import);

                if ($target === null || $target === $module) {
                    continue;
                }

                if (self::isPublicApi($import)) {
                    continue;
                }

                $violations[] = [
                    'from' => self::shorten($namespace) . '\\' . basename($file, '.php'),
                    'to' => self::shorten($import),
                    'file' => basename($file),
                ];
            }
        }

        return $violations;
    }

    /** @return list<string> jména modulů podle složek nejvyšší úrovně */
    public static function modulesIn(string $dir): array
    {
        $modules = array_map('basename', array_filter(glob($dir . '/*'), 'is_dir'));
        sort($modules);

        return array_values($modules);
    }

    /** @return list<string> */
    private static function phpFiles(string $dir): array
    {
        $files = [];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    private static function namespaceOf(string $code): string
    {
        return preg_match('/^namespace\s+([^;]+);/m', $code, $m) === 1 ? $m[1] : '';
    }

    /** @return list<string> */
    private static function importsIn(string $code): array
    {
        preg_match_all('/^use\s+([^;]+);/m', $code, $m);

        return $m[1];
    }

    /** Modul je druhý článek jmenného prostoru: Root\Modul\… */
    private static function moduleOf(string $name): ?string
    {
        $parts = explode('\\', $name);

        return $parts[1] ?? null;
    }

    private static function isPublicApi(string $import): bool
    {
        return (explode('\\', $import)[2] ?? null) === self::PUBLIC_SEGMENT;
    }

    private static function shorten(string $name): string
    {
        return implode('\\', array_slice(explode('\\', $name), 1));
    }
}
