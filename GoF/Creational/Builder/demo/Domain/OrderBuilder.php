<?php

declare(strict_types=1);

namespace Domain;

/**
 * BUILDER.
 *
 * Sestavuje objednávku po částech a teprve `build()` vyrobí hotový
 * objekt — který je pak neměnný a platný.
 *
 * Klíčové rozlišení proti [Factory Method]: továrna vyrobí objekt
 * JEDNÍM voláním. Builder se používá tam, kde je částí moc, jsou
 * volitelné a přidávají se postupně — třeba podle toho, co uživatel
 * v košíku udělá.
 *
 * Pozor na jednu věc: builder NENÍ místo pro doménová pravidla.
 * Ta zůstávají v konstruktoru Order, takže se nedají obejít tím,
 * že si někdo objednávku sestaví jinak.
 */
final class OrderBuilder
{
    /** @var list<OrderItem> */
    private array $items = [];

    private string $shippingMethod = 'balíkovna';
    private string $paymentMethod = 'karta';
    private ?string $note = null;
    private ?string $couponCode = null;
    private bool $isGift = false;

    private function __construct(
        private readonly string $number,
        private readonly string $customerEmail,
        private readonly \DateTimeImmutable $placedAt,
    ) {
    }

    /** Povinné části jdou do továrny, ne do setterů. */
    public static function for(string $number, string $customerEmail, \DateTimeImmutable $placedAt): self
    {
        return new self($number, $customerEmail, $placedAt);
    }

    public function withItem(string $sku, int $unitPriceInCents, int $quantity = 1): self
    {
        $this->items[] = new OrderItem($sku, $unitPriceInCents, $quantity);

        return $this;
    }

    public function shippedBy(string $method): self
    {
        $this->shippingMethod = $method;

        return $this;
    }

    public function paidBy(string $method): self
    {
        $this->paymentMethod = $method;

        return $this;
    }

    public function withNote(string $note): self
    {
        $this->note = $note;

        return $this;
    }

    public function withCoupon(string $code): self
    {
        $this->couponCode = $code;

        return $this;
    }

    public function asGift(string $message): self
    {
        $this->isGift = true;
        $this->note = $message;

        return $this;
    }

    /** Až tady vznikne objekt — a až tady se ověří pravidla. */
    public function build(): Order
    {
        return new Order(
            $this->number,
            $this->customerEmail,
            $this->items,
            $this->shippingMethod,
            $this->paymentMethod,
            $this->note,
            $this->couponCode,
            $this->isGift,
            $this->placedAt,
        );
    }
}
