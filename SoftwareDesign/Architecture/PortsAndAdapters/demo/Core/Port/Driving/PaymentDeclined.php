<?php

declare(strict_types=1);

namespace Core\Port\Driving;

/** Selhání vyjádřené v pojmech jádra, ne v pojmech platební brány. */
final class PaymentDeclined extends \RuntimeException
{
}
