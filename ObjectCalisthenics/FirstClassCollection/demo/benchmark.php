<?php

declare(strict_types=1);

/**
 * Měření ceny neměnnosti.
 *
 * Spuštění:  php benchmark.php
 *
 * Ukazuje, že withItem() volaný ve smyčce se chová kvadraticky, zatímco
 * hromadné vytvoření je lineární — a že řešením je bulk API, ne měnitelnost.
 *
 * Limit MAX_ITEMS je tu schválně obejitý vlastními třídami bez limitu,
 * abychom mohli měřit i velká N.
 */

require __DIR__ . '/OrderItem.php';

/** Neměnná kolekce bez limitu — jinak totožná s OrderItems. */
final readonly class BenchImmutable
{
    /** @param list<OrderItem> $items */
    private function __construct(private array $items)
    {
    }

    public static function empty(): self
    {
        return new self([]);
    }

    /** @param list<OrderItem> $items */
    public static function fromArray(array $items): self
    {
        return new self($items);
    }

    public function withItem(OrderItem $item): self
    {
        return new self([...$this->items, $item]);
    }

    /** @param list<OrderItem> $items */
    public function withItems(array $items): self
    {
        return new self([...$this->items, ...$items]);
    }

    public function count(): int
    {
        return count($this->items);
    }
}

/** Měnitelná kolekce bez limitu — jinak totožná s MutableOrderItems. */
final class BenchMutable
{
    /** @var list<OrderItem> */
    private array $items = [];

    public function add(OrderItem $item): void
    {
        $this->items[] = $item;
    }

    public function count(): int
    {
        return count($this->items);
    }
}

function measure(string $label, callable $fn): void
{
    gc_collect_cycles();
    $start = hrtime(true);
    $count = $fn();
    $ms = (hrtime(true) - $start) / 1e6;

    // mb_str_pad, protože printf počítá bajty a diakritika by zarovnání rozhodila
    printf("    %s %9.2f ms   (n = %d)\n", mb_str_pad($label, 32), $ms, $count);
}

echo "=== Cena neměnnosti při plnění kolekce ===\n";
echo "PHP " . PHP_VERSION . "\n\n";

foreach ([1_000, 5_000, 20_000, 50_000] as $size) {
    $source = [];

    for ($i = 0; $i < $size; $i++) {
        $source[] = new OrderItem('Produkt ' . $i, 1000, 1, 100);
    }

    printf("N = %s\n", number_format($size, 0, ',', ' '));

    measure('withItem() ve smyčce', static function () use ($source): int {
        $collection = BenchImmutable::empty();

        foreach ($source as $item) {
            $collection = $collection->withItem($item);
        }

        return $collection->count();
    });

    measure('withItems() hromadně', static fn (): int => BenchImmutable::empty()->withItems($source)->count());
    measure('fromArray() hromadně', static fn (): int => BenchImmutable::fromArray($source)->count());

    measure('měnitelné add() ve smyčce', static function () use ($source): int {
        $collection = new BenchMutable();

        foreach ($source as $item) {
            $collection->add($item);
        }

        return $collection->count();
    });

    echo "\n";
}

echo "Závěr: problém není neměnnost, ale přidávání po jednom prvku.\n";
echo "Hromadné vytvoření neměnné kolekce je i pro 50 000 položek rychlejší\n";
echo "než měnitelné add() ve smyčce.\n";
