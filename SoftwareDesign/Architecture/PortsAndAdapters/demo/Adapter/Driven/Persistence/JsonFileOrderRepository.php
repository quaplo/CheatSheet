<?php

declare(strict_types=1);

namespace Adapter\Driven\Persistence;

use Core\Domain\Order;
use Core\Port\Driven\OrderRepository;

/**
 * Řízený adaptér, který skutečně někam zapisuje.
 *
 * Zastupuje „opravdovou“ persistenci (u vás Doctrine). Podstatné je, že
 * překlad mezi doménovým objektem a formátem úložiště je **tady** —
 * jádro o JSON, sloupcích ani tabulkách neví.
 */
final class JsonFileOrderRepository implements OrderRepository
{
    public function __construct(
        private readonly string $file,
    ) {
        if (is_file($this->file) === false) {
            $this->write([]);
        }
    }

    public function nextNumber(): string
    {
        return sprintf('OBJ-%03d', count($this->read()) + 1);
    }

    public function save(Order $order): void
    {
        $rows = $this->read();

        // Mapování doména → úložiště. Tenhle překlad patří do adaptéru.
        $rows[$order->number] = [
            'number' => $order->number,
            'email' => $order->customerEmail,
            'total' => $order->totalInCents,
            'payment_reference' => $order->paymentReference,
        ];

        $this->write($rows);
    }

    public function findByNumber(string $number): ?Order
    {
        $rows = $this->read();

        return isset($rows[$number]) ? $this->toOrder($rows[$number]) : null;
    }

    public function all(): array
    {
        return array_values(array_map($this->toOrder(...), $this->read()));
    }

    /** @param array{number: string, email: string, total: int, payment_reference: ?string} $row */
    private function toOrder(array $row): Order
    {
        // Mapování úložiště → doména.
        $order = Order::place($row['number'], $row['email'], $row['total']);

        return $row['payment_reference'] !== null
            ? $order->paidWith($row['payment_reference'])
            : $order;
    }

    /** @return array<string, array{number: string, email: string, total: int, payment_reference: ?string}> */
    private function read(): array
    {
        $contents = file_get_contents($this->file);

        return $contents === false ? [] : (json_decode($contents, true) ?? []);
    }

    /** @param array<string, mixed> $rows */
    private function write(array $rows): void
    {
        file_put_contents($this->file, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
