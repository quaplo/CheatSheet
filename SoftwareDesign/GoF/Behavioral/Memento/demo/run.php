<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Memento.
 *
 * Spuštění:  php run.php
 */

foreach (['Memento', 'OrderItem', 'OrderMemento', 'Order', 'ShallowOrder', 'EditHistory'] as $class) {
    require __DIR__ . '/' . $class . '.php';
}

function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

function items(int $n): string
{
    return match (true) {
        $n === 1 => '1 kus',
        $n < 5 => $n . ' kusy',
        default => $n . ' kusů',
    };
}

echo "=== Memento ===\n\n";

// --- 1. Undo ---------------------------------------------------------------

echo "1. Krok zpět\n\n";

$order = new Order();
$history = new EditHistory();

$order->addItem('KNIHA-1', 2);
printf("    %s%s\n", pad('výchozí stav', 34), $order->describe());

$history->push($order->save());
$order->applyDiscount(10);
printf("    %s%s\n", pad('po slevě', 34), $order->describe());

$history->push($order->save());
$order->confirm();
printf("    %s%s\n", pad('po potvrzení', 34), $order->describe());

$order->restore($history->pop());
printf("    %s%s\n", pad('zpět (undo)', 34), $order->describe());

$order->restore($history->pop());
printf("    %s%s\n\n", pad('zpět ještě jednou', 34), $order->describe());

printf("    %s%d\n\n", pad('snímků zbylo v historii', 34), $history->count());

// --- 2. Co pečovatel vidí --------------------------------------------------

echo "2. Co o tom snímku pečovatel ví\n\n";

$reflection = new ReflectionClass(Memento::class);

printf("    %s%d\n", pad('metod na rozhraní Memento', 36), count($reflection->getMethods()));
printf("    %s%s\n\n", pad('typ, se kterým EditHistory pracuje', 36), 'Memento');

echo "    Historie snímky jen drží a vrací. Neumí se do nich podívat\n";
echo "    a nemusí vědět, z čeho se stav objednávky skládá — takže\n";
echo "    přidání dalšího pole se jí vůbec nedotkne.\n\n";

echo "    Zapouzdření tu ale nedrží modifikátor přístupu. PHP nemá\n";
echo "    „friend\" ani package-private, takže OrderMemento má pole\n";
echo "    veřejná a readonly. Drží to typ, ne kompilátor.\n\n";

// --- 3. Past: mělká kopie --------------------------------------------------

echo "3. Past, která projde testem\n\n";

$shallow = new ShallowOrder();
$shallow->addItem('KNIHA-1', 2);

$snapshot = $shallow->save();
$before = $shallow->itemCount();

$shallow->changeQuantity(0, 9);
$afterChange = $shallow->itemCount();

$shallow->restore($snapshot);
$afterUndo = $shallow->itemCount();

printf("    %s%s\n", pad('mělký snímek: před změnou', 34), items($before));
printf("    %s%s\n", pad('po změně množství', 34), items($afterChange));
printf("    %s%s\n\n", pad('po undo', 34), items($afterUndo) . ($afterUndo === $before ? '' : '  ← undo nic nevrátilo'));

$deep = new Order();
$deep->addItem('KNIHA-1', 2);
$deepSnapshot = $deep->save();
$deepBefore = $deep->itemCount();
$deep->addItem('KNIHA-1', 7);
$deep->restore($deepSnapshot);

printf("    %s%s\n", pad('hluboký snímek: před změnou', 34), items($deepBefore));
printf("    %s%s\n\n", pad('po undo', 34), items($deep->itemCount()));

echo "    Rozdíl je jediné array_map ve save(). Mělký snímek uložil\n";
echo "    tytéž objekty, které drží originál — takže když se změnily,\n";
echo "    změnil se s nimi i „snímek\". Undo vrátilo objekt do stavu,\n";
echo "    ve kterém stejně už byl.\n\n";

echo "    Nejhorší na tom je, že s neměnnými položkami by tenhle\n";
echo "    test prošel. Chyba se objeví až u prvního měnitelného pole.\n\n";

// --- 4. Kdy Memento nepotřebuješ ------------------------------------------

echo "4. Kdy to celé nepotřebuješ\n\n";

echo "    Když je objekt neměnný, snímek je prostě ta stará reference:\n\n";
echo "        \$before = \$order;                  // to je celý „snímek\"\n";
echo "        \$order = \$order->withDiscount(10);\n";
echo "        \$order = \$before;                  // a to je undo\n\n";

$classes = ['Memento', 'OrderMemento', 'EditHistory'];

printf("    %s%d\n", pad('tříd, které Memento přidává', 34), count($classes));
printf("    %s%s\n\n", pad('při neměnném objektu', 34), '0 — stačí proměnná');

echo "    Memento se vyplatí tam, kde objekt neměnný být nemůže:\n";
echo "    entita s identitou, rozdělaný formulář, editor.\n";
