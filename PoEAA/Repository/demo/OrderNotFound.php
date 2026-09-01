<?php

declare(strict_types=1);

/** Chybějící agregát je doménová chyba, ne `null` a ne SQL výjimka. */
final class OrderNotFound extends RuntimeException
{
    public static function withId(OrderId $id): self
    {
        return new self(sprintf('Objednávka %s neexistuje.', $id->value));
    }
}
