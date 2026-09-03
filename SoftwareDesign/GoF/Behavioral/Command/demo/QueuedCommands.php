<?php

declare(strict_types=1);

/**
 * Odložené spuštění: příkaz si nese všechno potřebné, takže
 * jde uložit a provést jindy, jinde a jiným procesem.
 *
 * Tohle je druhý velký důvod, proč Command existuje — a proč
 * na něm stojí každá fronta úloh.
 */
final class CommandQueue
{
    /** @var list<string> serializované příkazy */
    private array $queue = [];

    public function push(Command $command): void
    {
        $this->queue[] = serialize($command);
    }

    public function size(): int
    {
        return count($this->queue);
    }

    /** Simuluje worker v jiném procesu: nic o příkazech neví předem. */
    public function processAll(): int
    {
        $processed = 0;

        while ($this->queue !== []) {
            /** @var Command $command */
            $command = unserialize(array_shift($this->queue));
            $command->execute();
            ++$processed;
        }

        return $processed;
    }
}

/**
 * Příkaz vhodný do fronty: obsahuje jen data, ne živé závislosti.
 *
 * Kdyby si v konstruktoru držel připojení k databázi nebo mailer,
 * serializace by neprošla — proto se závislost hledá až při běhu.
 */
final class ExportOrders implements Command
{
    /** @var list<string> záznam toho, co worker provedl */
    public static array $log = [];

    public function __construct(
        private readonly string $customerId,
        private readonly string $format,
    ) {
    }

    public function execute(): void
    {
        self::$log[] = sprintf('export %s pro %s', $this->format, $this->customerId);
    }

    public function describe(): string
    {
        return sprintf('export %s pro zákazníka %s', $this->format, $this->customerId);
    }
}
