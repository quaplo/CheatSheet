<?php

declare(strict_types=1);

namespace Contexts;

final class BillingContext
{
    public bool $isDown = false;
    public int $calls = 0;

    /** @var list<string> */
    public array $issuedInvoices = [];

    /** @return array{invoiceNumber: string, dueDate: string, isPaid: bool} */
    public function invoiceFor(string $orderId): array
    {
        $this->calls++;
        $this->assertUp();

        return ['invoiceNumber' => 'FA-2026-0912', 'dueDate' => '2026-09-15', 'isPaid' => true];
    }

    public function issueInvoice(string $orderId, int $totalInCents): string
    {
        $this->calls++;
        $this->assertUp();

        $number = 'FA-' . str_pad((string) (count($this->issuedInvoices) + 1), 4, '0', STR_PAD_LEFT);
        $this->issuedInvoices[] = $number;

        return $number;
    }

    private function assertUp(): void
    {
        if ($this->isDown) {
            throw new \RuntimeException('Kontext Billing není dostupný.');
        }
    }
}
