<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Context Map.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/RelationshipType.php';
require __DIR__ . '/Context.php';
require __DIR__ . '/Relationship.php';
require __DIR__ . '/ContextMap.php';

// Mapa, jaká SKUTEČNĚ je — ne jaká by měla být. To je Evansovo pravidlo
// číslo jedna: nejdřív se nakreslí realita, teprve pak se plánuje změna.
$map = new ContextMap(
    contexts: [
        new Context('Catalog', 'tým Produkt', 'nemá zákazníky, jen zboží'),
        new Context('Sales', 'tým Obchod', 'příležitost s pravděpodobností'),
        new Context('Billing', 'tým Finance', 'plátce s DIČ a limitem'),
        new Context('Support', 'tým Péče', 'tazatel s úrovní podpory'),
        new Context('Identity', 'tým Platforma', 'účet a přihlášení'),
        new Context('LegacyERP', 'externí dodavatel', 'partner, cokoli to znamená'),
        new Context('Analytics', 'tým Data', 'řádek ve faktu'),
    ],
    relationships: [
        new Relationship('Catalog', 'Sales', RelationshipType::OpenHostService, 'REST API se sémantickou verzí'),
        new Relationship('Identity', 'Sales', RelationshipType::Conformist, 'přebíráme jejich User beze změny'),
        new Relationship('Identity', 'Billing', RelationshipType::Conformist, 'totéž'),
        new Relationship('Identity', 'Support', RelationshipType::Conformist, 'totéž'),
        new Relationship('Sales', 'Billing', RelationshipType::CustomerSupplier, 'uzavřený obchod → plátce'),
        new Relationship('Billing', 'Sales', RelationshipType::CustomerSupplier, 'stav plateb zpět do CRM'),
        new Relationship('Sales', 'Support', RelationshipType::SharedKernel, 'sdílíme CustomerId a číselník úrovní'),
        new Relationship('LegacyERP', 'Billing', RelationshipType::AnticorruptionLayer, 'překlad jejich XML na náš model'),
    ],
);

echo "=== Context Map ===\n\n";

// --- 1. Co které slovo kde znamená ----------------------------------------

echo "1. Slovo „zákazník“ napříč kontexty\n\n";

foreach ($map->contexts() as $context) {
    printf("    %s %s  %s\n",
        mb_str_pad($context->name, 12),
        mb_str_pad($context->team, 20),
        $context->meaningOfCustomer,
    );
}

echo "\n    Sedm kontextů, sedm významů. Kdyby to byl jeden model, byl by\n";
echo "    to model ničeho.\n";

// --- 2. Vztahy ------------------------------------------------------------

echo "\n2. Vztahy — kdo se komu musí přizpůsobit\n\n";

foreach ($map->contexts() as $context) {
    $upstream = $map->upstreamOf($context->name);

    if ($upstream === []) {
        continue;
    }

    printf("    %s závisí na:\n", $context->name);

    foreach ($upstream as $relationship) {
        printf("        ← %s  [%s]  %s\n",
            mb_str_pad($relationship->upstream, 12),
            mb_str_pad($relationship->type->value, 20),
            $relationship->note,
        );
    }
}

// --- 3. Rizika, na která mapa upozorní sama -------------------------------

echo "\n3. Co z mapy vypadlo samo\n\n";

foreach ($map->risks() as $risk) {
    printf("    ⚠ %s\n", $risk);
}

echo "\n    Nic z toho není chyba, kterou by šlo najít v kódu. Všechno to\n";
echo "    jsou rozhodnutí, o kterých se má vědět.\n";

// --- 4. Otázka, kterou má každý vztah položit -----------------------------

echo "\n4. Na co se u kterého vztahu ptát\n\n";

$used = [];

foreach ($map->contexts() as $context) {
    foreach ($map->upstreamOf($context->name) as $relationship) {
        $used[$relationship->type->value] = $relationship->type;
    }
}

foreach ($used as $label => $type) {
    printf("    %s %s\n", mb_str_pad($label, 22), $type->question());
}

// --- 5. Mapa jako obrázek --------------------------------------------------

echo "\n5. Vygenerovaný Mermaid (vlož do README, GitHub ho vykreslí)\n\n";
echo "```mermaid\n" . $map->toMermaid() . "\n```\n";
