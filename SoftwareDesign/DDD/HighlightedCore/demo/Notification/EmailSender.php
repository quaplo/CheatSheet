<?php

declare(strict_types=1);

namespace Notification;

/** Obecná podoblast: odesílání e-mailů. */
final class EmailSender
{
    public function send(string $to, string $subject): void
    {
    }
}
