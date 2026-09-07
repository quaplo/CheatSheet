<?php

declare(strict_types=1);

namespace Before;

/**
 * Tři místa, která objednávku vytvářejí.
 *
 * Zkus z volání poznat, které je které — bez čtení té třídy.
 */
final class Usage
{
    public static function fromEshop(string $number, array $items, string $customerId): Order
    {
        return new Order($number, $items, $customerId, null, false, false);
    }

    public static function fromPartnerImport(string $number, array $items, string $partnerCode): Order
    {
        return new Order($number, $items, null, $partnerCode, true, true);
    }

    public static function fromDatabase(array $row): Order
    {
        return new Order(
            $row['number'],
            $row['items'],
            $row['customer_id'],
            $row['partner_code'],
            (bool) $row['is_paid'],
            false,
        );
    }
}
