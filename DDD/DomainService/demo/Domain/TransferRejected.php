<?php

declare(strict_types=1);

namespace Domain;

/** Doménová chyba převodu — v pojmech domény, ne infrastruktury. */
final class TransferRejected extends \DomainException
{
}
