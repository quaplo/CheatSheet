<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Chain of Responsibility.
 *
 * Spuštění:  php run.php
 *
 * Ukazuje obě podoby: klasický řetěz (první schopný vyřídí a končí)
 * a pipeline (každý článek obaluje ten následující).
 */

require __DIR__ . '/ApprovalRequest.php';
require __DIR__ . '/ApprovalDecision.php';
require __DIR__ . '/Approver.php';
require __DIR__ . '/LimitedApprover.php';
require __DIR__ . '/OrderRequest.php';
require __DIR__ . '/OrderResult.php';
require __DIR__ . '/OrderMiddleware.php';
require __DIR__ . '/OrderPipeline.php';
require __DIR__ . '/ValidateOrderMiddleware.php';
require __DIR__ . '/CheckStockMiddleware.php';
require __DIR__ . '/AuditMiddleware.php';

function money(int $cents): string
{
    return number_format($cents / 100, 0, ',', ' ') . ' Kč';
}

echo "=== Chain of Responsibility ===\n\n";

// ==========================================================================
// A. Klasický řetěz — první schopný vyřídí, ostatní se nezeptají
// ==========================================================================

echo "A. Klasický řetěz: schvalování mimořádné slevy\n\n";

$approvers = static fn (): Approver => Approver::chain(
    new LimitedApprover('Vedoucí směny', 50000),
    new LimitedApprover('Manažer', 500000),
    new LimitedApprover('Ředitel', 5000000),
);

$requests = [
    new ApprovalRequest('OBJ-001', 30000, 'alice'),
    new ApprovalRequest('OBJ-002', 320000, 'bob'),
    new ApprovalRequest('OBJ-003', 2400000, 'carol'),
    new ApprovalRequest('OBJ-004', 9000000, 'dave'),
];

foreach ($requests as $request) {
    $decision = $approvers()->handle($request);

    printf("    %s  sleva %s\n", $request->orderNumber, money($request->discountInCents));
    printf("        cesta:  %s\n", implode('  →  ', $decision->consulted) ?: '—');

    if ($decision->isApproved) {
        printf("        výsledek: schválil %s\n\n", $decision->approvedBy);
    } else {
        printf("        výsledek: %s\n\n", $decision->reason);
    }
}

echo "    Odesílatel neví, kdo žádost vyřídí. Poslední případ ukazuje to\n";
echo "    podstatné: řetěz má ošetřený konec, takže žádost nezmizí.\n";

// ==========================================================================
// B. Pipeline — každý článek obaluje ten následující
// ==========================================================================

echo "\n\nB. Pipeline: zpracování objednávky\n\n";

$pipeline = new OrderPipeline([
    new AuditMiddleware(),          // vnější vrstva — vidí i výsledek
    new ValidateOrderMiddleware(),
    new CheckStockMiddleware(),
]);

$orders = [
    new OrderRequest('OBJ-101', 129000, 3, inStock: true),
    new OrderRequest('OBJ-102', 89000, 2, inStock: false),
    new OrderRequest('OBJ-103', 0, 0, inStock: true),
];

foreach ($orders as $order) {
    $result = $pipeline->process($order);

    printf("    %s\n", $order->orderNumber);
    printf("        %s\n", $result->message);

    foreach ($result->log as $entry) {
        printf("            · %s\n", $entry);
    }

    echo "\n";
}

echo "    Všimni si OBJ-102: sklad řetěz utnul, takže se validace stihla\n";
echo "    zapsat, ale audit uviděl i to zamítnutí — obaluje celý průchod.\n";

// ==========================================================================
// C. Pořadí článků je součástí chování
// ==========================================================================

echo "\n\nC. Táž objednávka, prohozené pořadí článků\n\n";

$brokenOrder = new OrderRequest('OBJ-104', 0, 0, inStock: false);

$stockFirst = new OrderPipeline([new CheckStockMiddleware(), new ValidateOrderMiddleware()]);
$validationFirst = new OrderPipeline([new ValidateOrderMiddleware(), new CheckStockMiddleware()]);

printf("    sklad první:    %s\n", $stockFirst->process($brokenOrder)->message);
printf("    validace první: %s\n", $validationFirst->process($brokenOrder)->message);

echo "\n    Stejné články, jiné pořadí, jiná chybová hláška pro zákazníka.\n";
echo "    Pořadí v řetězu není konfigurace — je to rozhodnutí o chování.\n";
