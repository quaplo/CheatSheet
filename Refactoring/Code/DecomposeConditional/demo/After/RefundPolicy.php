<?php

declare(strict_types=1);

namespace After;

/**
 * Táž pravidla, rozložená na věty.
 *
 * Chování je nezměněné. Změnilo se, kolik toho musíš přečíst,
 * abys věděl, co se děje.
 */
final class RefundPolicy
{
    private const int WITHDRAWAL_PERIOD_SECONDS = 14 * 24 * 3600;
    private const int GIFT_WRAP_PRICE = 2500;

    /** @param array<string, mixed> $order */
    public function amountFor(array $order, int $now): int
    {
        if ($this->isWithdrawalBeforeShipping($order, $now)) {
            return $this->fullRefund($order);
        }

        if ($this->isReturnAfterDelivery($order, $now)) {
            return $this->refundWithoutShipping($order);
        }

        return 0;
    }

    /** @param array<string, mixed> $order */
    private function isWithdrawalBeforeShipping(array $order, int $now): bool
    {
        return $order['shippedAt'] === null
            && $now - $order['paidAt'] < self::WITHDRAWAL_PERIOD_SECONDS
            && $order['paymentMethod'] !== 'dobirka';
    }

    /** @param array<string, mixed> $order */
    private function isReturnAfterDelivery(array $order, int $now): bool
    {
        return $order['shippedAt'] !== null
            && $now - $order['shippedAt'] < self::WITHDRAWAL_PERIOD_SECONDS
            && $order['returnedAt'] !== null;
    }

    /** @param array<string, mixed> $order */
    private function fullRefund(array $order): int
    {
        return $order['totalInCents'] - $this->giftWrapDeduction($order);
    }

    /** @param array<string, mixed> $order */
    private function refundWithoutShipping(array $order): int
    {
        $amount = $order['totalInCents'] - $order['shippingInCents'] - $this->giftWrapDeduction($order);

        return max($amount, 0);
    }

    /** @param array<string, mixed> $order */
    private function giftWrapDeduction(array $order): int
    {
        return $order['giftWrapped'] ? self::GIFT_WRAP_PRICE : 0;
    }
}
