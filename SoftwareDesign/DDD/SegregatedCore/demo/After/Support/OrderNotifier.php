<?php

declare(strict_types=1);

namespace After\Support;

use After\Core\Order;

/**
 * Podpůrná role: co se má stát okolo změny stavu.
 *
 * Dřív to dělala objednávka sama uvnitř cancel(). Teď to orchestruje
 * vrstva nad jádrem — a jádro se dá zrušit i bez odeslání e-mailu,
 * což je přesně to, co chceš v testu.
 */
final class OrderNotifier
{
    public function __construct(
        private readonly Mailer $mailer,
        private readonly OrderFormatter $formatter,
    ) {
    }

    public function notifyCancelled(Order $order, string $email): void
    {
        $this->mailer->send($email, 'Objednávka zrušena', $this->formatter->formatSummary($order));
    }

    public function notifyConfirmed(Order $order, string $email): void
    {
        $this->mailer->send($email, 'Objednávka přijata', $this->formatter->formatSummary($order));
    }
}
