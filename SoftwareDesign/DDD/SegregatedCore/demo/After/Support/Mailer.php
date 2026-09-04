<?php

declare(strict_types=1);

namespace After\Support;

final class Mailer
{
    /** @var list<string> */
    public array $sent = [];

    public function send(string $to, string $subject, string $body): void
    {
        $this->sent[] = $to . ' | ' . $subject;
    }
}
