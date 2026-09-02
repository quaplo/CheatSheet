<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Command.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/Command.php';
require __DIR__ . '/UndoableCommand.php';
require __DIR__ . '/TextDocument.php';
require __DIR__ . '/AppendText.php';
require __DIR__ . '/ReplaceAll.php';
require __DIR__ . '/SendNotification.php';
require __DIR__ . '/MacroCommand.php';
require __DIR__ . '/History.php';
require __DIR__ . '/QueuedCommands.php';
require __DIR__ . '/CqrsStyle.php';

/** Zarovnání, které nerozhodí česká diakritika (printf počítá bajty). */
function pad(string $text, int $width): string
{
    return mb_str_pad($text, $width);
}

function show(TextDocument $document, ?string $note = null): string
{
    $text = $document->content() === '' ? '(prázdný)' : '„' . $document->content() . '“';

    return $note === null ? $text : $text . '   ← ' . $note;
}

echo "=== Command ===\n\n";

// --- 1. Operace jako objekt ------------------------------------------------

echo "1. Operace zabalená do objektu\n\n";

$document = new TextDocument();
$history = new History();

$history->run(new AppendText($document, 'Objednávka '));
$history->run(new AppendText($document, 'byla přijata.'));

echo '    dokument: ' . show($document) . "\n";
echo "    historie:\n";

foreach ($history->describeDone() as $i => $description) {
    printf("        %d. %s\n", $i + 1, $description);
}

echo "\n    History o textu ani o dokumentu neví nic — jen spouští\n";
echo "    a ukládá. Proto umí vrátit i operaci, která vznikne zítra.\n\n";

// --- 2. Undo a redo --------------------------------------------------------

echo "2. Undo a redo\n\n";

printf("    %s %s\n", pad('výchozí stav', 13), show($document));
printf("    %s %s\n", pad('undo', 13), show($document, $history->undo()));
printf("    %s %s\n", pad('undo', 13), show($document, $history->undo()));
printf("    %s %s\n", pad('redo', 13), show($document, $history->redo()));
printf("    %s %s\n\n", pad('redo', 13), show($document, $history->redo()));

// --- 3. Dva způsoby, jak vrátit efekt --------------------------------------

echo "3. Dva způsoby, jak vrátit efekt\n\n";

$document->restore('Cena je 100 Kč, celková cena je 250 Kč.');
$replace = new ReplaceAll($document, 'cena', 'částka');

echo '    před:     ' . show($document) . "\n";
$history->run($replace);
echo '    po:       ' . show($document) . "\n";
$history->undo();
echo '    po undo:  ' . show($document) . "\n\n";

echo "    AppendText si pamatuje jen délku a text odečte.\n";
echo "    ReplaceAll to nemůže — zpětná záměna by přepsala i to,\n";
echo "    co v textu bylo předtím. Proto si uloží snímek.\n\n";

// --- 4. Co vrátit nejde ----------------------------------------------------

echo "4. Co vrátit nejde, se do historie nedostane\n\n";

$notification = new SendNotification('alice@example.com', 'Objednávka odeslána');
$history->run($notification);

printf("    spuštěno:                %s\n", $notification->describe());
printf("    UndoableCommand?         %s\n", $notification instanceof UndoableCommand ? 'ano' : 'ne');
printf("    v zásobníku undo:        %s\n\n", in_array($notification->describe(), $history->describeDone(), true) ? 'ano' : 'ne');

echo "    Odeslaný e-mail zpátky nevezmeš. Rozdělení na Command\n";
echo "    a UndoableCommand to říká typem, ne komentářem.\n\n";

// --- 5. Makro: skupina příkazů jako jeden -----------------------------------

echo "5. Makro — skupina příkazů se chová jako jeden\n\n";

$document->restore('');
$macro = new MacroCommand(
    'vyplnit hlavičku',
    new AppendText($document, "Faktura č. 2024/001\n"),
    new AppendText($document, "    Odběratel: Alice\n"),
    new AppendText($document, '    Splatnost: 14 dní'),
);

$history->run($macro);

foreach (explode("\n", $document->content()) as $line) {
    echo '    | ' . $line . "\n";
}

printf("\n    jeden příkaz v historii:  %s\n", $macro->describe());

$history->undo();
printf("    po jednom undo:           %s\n\n", show($document));

echo "    Undo běží v opačném pořadí. Volající nepozná, jestli\n";
echo "    drží jednu operaci, nebo dvacet — to je Composite.\n\n";

// --- 6. Fronta: příkaz provede jiný proces ---------------------------------

echo "6. Fronta — příkaz provede někdo jiný a jindy\n\n";

$queue = new CommandQueue();
$queue->push(new ExportOrders('alice', 'CSV'));
$queue->push(new ExportOrders('bob', 'PDF'));
$queue->push(new ExportOrders('carol', 'CSV'));

printf("    ve frontě:            %d příkazy (serializované)\n", $queue->size());
printf("    worker zpracoval:     %d\n", $queue->processAll());

foreach (ExportOrders::$log as $entry) {
    echo '        ✓ ' . $entry . "\n";
}

echo "\n    Příkaz přežil serializaci, protože si nese jen data.\n";
echo "    Kdyby si v konstruktoru držel připojení k databázi,\n";
echo "    fronta by ho nepřenesla. Tohle je nejčastější past.\n\n";

// --- 7. Command v GoF vs. command v CQRS -----------------------------------

echo "7. Stejné jméno, jiná věc\n\n";

$gofCommand = new AppendText(new TextDocument(), 'x');
$cqrsCommand = new PlaceOrder('alice', 129000);
$handler = new PlaceOrderHandler();
$handler($cqrsCommand);

printf("    %s %s\n", pad('', 26), pad('GoF Command', 22) . 'CQRS command');
printf("    %s %s\n", pad('umí se provést sám', 26), pad($gofCommand instanceof Command ? 'ano (execute)' : '—', 22) . (method_exists($cqrsCommand, 'execute') ? 'ano' : 'ne'));
printf("    %s %s\n", pad('zná příjemce', 26), pad('ano', 22) . 'ne');
printf("    %s %s\n", pad('kdo dělá práci', 26), pad('příkaz sám', 22) . 'handler');
printf("    %s %s\n\n", pad('výsledek', 26), pad('undo/redo, fronta', 22) . $handler->handled[0]);

echo "    Obojí se jmenuje command a obojí je „operace jako objekt“.\n";
echo "    Rozdíl je v tom, kde je chování: GoF ho dá dovnitř,\n";
echo "    CQRS ho vytkne do handleru a příkaz nechá jako data.\n";
