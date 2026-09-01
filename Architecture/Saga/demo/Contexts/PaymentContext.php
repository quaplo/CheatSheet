<?php

declare(strict_types=1);

namespace Contexts;

/**
 * Platební kontext s ÚČETNÍ KNIHOU.
 *
 * Kniha je tu schválně: ukazuje nejdůležitější věc na celém patternu.
 * Kompenzace platbu NEMAŽE — přidá dobropis. V knize zůstane obojí,
 * protože obojí se skutečně stalo.
 */
final class PaymentContext
{
    public bool $failNext = false;

    /** @var list<array{type: string, orderId: string, amount: int, id: string}> */
    public array $ledger = [];

    public function charge(string $orderId, int $amountInCents): string
    {
        if ($this->failNext) {
            $this->failNext = false;

            throw new \RuntimeException('Platba: karta zamítnuta.');
        }

        $paymentId = 'PAY-' . strtoupper(substr(md5($orderId), 0, 6));
        $this->ledger[] = ['type' => 'stržení', 'orderId' => $orderId, 'amount' => $amountInCents, 'id' => $paymentId];

        return $paymentId;
    }

    /** Kompenzace = nový účetní záznam, ne smazání starého. */
    public function refund(string $paymentId): void
    {
        foreach ($this->ledger as $entry) {
            if ($entry['type'] === 'dobropis' && $entry['id'] === $paymentId) {
                return;   // idempotence
            }
        }

        foreach ($this->ledger as $entry) {
            if ($entry['type'] === 'stržení' && $entry['id'] === $paymentId) {
                $this->ledger[] = ['type' => 'dobropis', 'orderId' => $entry['orderId'], 'amount' => -$entry['amount'], 'id' => $paymentId];

                return;
            }
        }
    }

    public function balanceFor(string $orderId): int
    {
        return array_sum(array_map(
            static fn (array $e): int => $e['orderId'] === $orderId ? $e['amount'] : 0,
            $this->ledger,
        ));
    }
}
