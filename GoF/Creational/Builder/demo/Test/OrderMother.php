<?php

declare(strict_types=1);

namespace Test;

use Domain\Order;
use Domain\OrderBuilder;

/**
 * TEST DATA BUILDER — nejužitečnější použití tohohle patternu v PHP.
 *
 * Test má říct, co je pro NĚJ podstatné, a mlčet o zbytku. Bez tohohle
 * má každý test devět parametrů, z nichž ho zajímá jeden — a když
 * do konstruktoru přibude desátý, přepisuje se sto testů.
 *
 * Rozumné výchozí hodnoty jsou tady, ne v produkčním kódu.
 */
final class OrderMother
{
    public static function any(): OrderBuilder
    {
        return OrderBuilder::for('OBJ-TEST', 'test@example.com', new \DateTimeImmutable('2026-09-01'))
            ->withItem('MON-27', 799000);
    }

    public static function gift(): Order
    {
        return self::any()->asGift('Všechno nejlepší!')->build();
    }

    public static function withCoupon(string $code): Order
    {
        return self::any()->withCoupon($code)->build();
    }
}
