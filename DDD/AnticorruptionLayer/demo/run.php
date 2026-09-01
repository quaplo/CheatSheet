<?php

declare(strict_types=1);

/**
 * Spustitelná ukázka patternu Anticorruption Layer.
 *
 * Spuštění:  php run.php
 */

require __DIR__ . '/LegacyErp/ErpClient.php';
require __DIR__ . '/ModernErp/ApiClient.php';
require __DIR__ . '/Domain/SupplierId.php';
require __DIR__ . '/Domain/DeliveryStatus.php';
require __DIR__ . '/Domain/Delivery.php';
require __DIR__ . '/Domain/GoodsReturn.php';
require __DIR__ . '/Domain/DeliveryFeedUnavailable.php';
require __DIR__ . '/Domain/DeliveryFeed.php';
require __DIR__ . '/Acl/ErpFacade.php';
require __DIR__ . '/Acl/ErpTranslator.php';
require __DIR__ . '/Acl/LegacyErpDeliveryFeed.php';
require __DIR__ . '/Acl/ModernErpDeliveryFeed.php';

use Acl\ErpFacade;
use Acl\ErpTranslator;
use Acl\LegacyErpDeliveryFeed;
use Acl\ModernErpDeliveryFeed;
use Domain\DeliveryFeed;
use Domain\DeliveryFeedUnavailable;
use LegacyErp\ErpClient;
use ModernErp\ApiClient;

function money(int $cents): string
{
    return number_format($cents / 100, 2, ',', ' ') . ' Kč';
}

/**
 * Kus domény. Zná JEN port — o žádném ERP neví.
 * Tahle funkce se v celém demu nezmění.
 */
function report(DeliveryFeed $feed): void
{
    foreach ($feed->deliveries() as $delivery) {
        printf(
            "        dodávka  %s  %s  %4d ks  %14s  %s  %s\n",
            mb_str_pad($delivery->number, 13),
            mb_str_pad($delivery->supplierName, 18),
            $delivery->quantity,
            money($delivery->valueInCents),
            $delivery->deliveredOn->format('j. n. Y'),
            $delivery->status->value,
        );
    }

    foreach ($feed->returns() as $return) {
        printf(
            "        VRATKA   %s  %s  %4d ks  %14s  %s\n",
            mb_str_pad($return->number, 13),
            mb_str_pad($return->supplierName, 18),
            $return->quantity,
            money($return->creditedInCents),
            $return->returnedOn->format('j. n. Y'),
        );
    }
}

echo "=== Anticorruption Layer ===\n\n";

// --- 1. Co posílá cizí systém ---------------------------------------------

echo "1. Co přichází z ERP\n\n";

foreach ((new ErpClient())->volejFunkci('DOD_SEZNAM') as $row) {
    echo '        ' . json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
}

echo "\n    Kódy stavů, datum jako řetězec, částka s čárkou, jeden pojem\n";
echo "    „partner“ pro dodavatele i odběratele — a záporné množství,\n";
echo "    které vůbec neznamená dodávku.\n";

// --- 2. Co z toho vidí doména ---------------------------------------------

echo "\n2. Co vidí doména za antikorupční vrstvou\n\n";

$legacy = new LegacyErpDeliveryFeed(
    new ErpFacade(new ErpClient()),
    new ErpTranslator(),
);

report($legacy);

echo "\n    Odběratel vypadl (nezajímá nás), kódy jsou přeložené na stavy,\n";
echo "    částky na haléře, datum na DateTimeImmutable.\n";

// --- 3. Pojmy se nemapují jedna ku jedné ----------------------------------

echo "\n3. Nejde o převod dat, ale pojmů\n";

$returns = $legacy->returns();

printf("\n    ERP:    DOD_MNOZ = '-15'  → jedna věta jako každá jiná\n");
printf("    Doména: %s pro %d ks, dobropis %s\n",
    $returns[0]::class,
    $returns[0]->quantity,
    money($returns[0]->creditedInCents),
);

echo "\n    Obyčejný mapper by převedl −15 na −15. Překlad pozná, že to\n";
echo "    není dodávka se záporným číslem, ale jiná věc.\n";

// --- 4. Selhání také patří přeložit ---------------------------------------

echo "\n4. Cizí selhání se překládá na naše\n";

$broken = new LegacyErpDeliveryFeed(
    new ErpFacade(new ErpClient(), simulateOutage: true),
    new ErpTranslator(),
);

printf("\n    ERP vrátí:  %s\n", json_encode(['ERR' => 'X07', 'MSG' => 'SPOJENI S DB NEDOSTUPNE'], JSON_UNESCAPED_UNICODE));

try {
    $broken->deliveries();
} catch (DeliveryFeedUnavailable $e) {
    printf("    Doména dostane: %s: %s\n", $e::class, $e->getMessage());
}

echo "\n    Kdyby 'ERR X07' proteklo ven, doména by o cizím systému stejně\n";
echo "    věděla — jen oklikou přes chybové hlášky.\n";

// --- 5. Odměna: výměna cizího systému -------------------------------------

echo "\n5. Výměna ERP za nástupce\n\n";

$modern = new ModernErpDeliveryFeed(new ApiClient());

report($modern);

echo "\n    Jiný systém, jiný formát, jiné názvy stavů, jiné identifikátory.\n";
echo "    Funkce report() výše se nezměnila ani o písmeno — dostala jen\n";
echo "    jinou implementaci téhož portu.\n";
echo "\n    Tohle je ta odměna za vrstvu navíc: mění se jeden soubor,\n";
echo "    ne celá aplikace.\n";
