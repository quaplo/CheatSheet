<?php

declare(strict_types=1);

/**
 * Příkaz, který vrátit NEJDE — a proto neimplementuje UndoableCommand.
 *
 * Odeslaný e-mail zpátky nevezmeš. Typ to říká nahlas, takže
 * historie takový příkaz do zásobníku undo vůbec nepustí.
 */
final class SendNotification implements Command
{
    /** @var list<string> */
    private array $sent = [];

    public function __construct(
        private readonly string $recipient,
        private readonly string $message,
    ) {
    }

    public function execute(): void
    {
        $this->sent[] = $this->recipient;
    }

    public function describe(): string
    {
        return sprintf('poslat zprávu „%s“ na %s', $this->message, $this->recipient);
    }
}
