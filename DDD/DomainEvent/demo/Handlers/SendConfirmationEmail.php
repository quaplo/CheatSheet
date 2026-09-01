<?php

declare(strict_types=1);

namespace Handlers;

use Domain\OrderPlaced;

/** Reakce, o které objednávka neví. */
final class SendConfirmationEmail
{
    /** @var list<string> */
    public array $sent = [];

    public function __invoke(OrderPlaced $event): void
    {
        $this->sent[] = $event->customerEmail;

        printf("            → e-mail na %s (objednávka %s)\n", $event->customerEmail, $event->orderId);
    }
}
