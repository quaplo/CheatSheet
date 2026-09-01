<?php

declare(strict_types=1);

/**
 * Odlehčená varianta: stav jako enum.
 *
 * V PHP 8.1+ tohle pokryje většinu stavových automatů — přechody jsou
 * na jednom místě, neplatný přechod vyhodí výjimku a celé je to jeden
 * soubor místo sedmi.
 *
 * Hranice, za kterou přestane stačit: jakmile má stav nést **chování
 * nebo data** (viz CancelledOrder::$refundRequired), enum ti dojde.
 * Enum je hodnota; stav se pak potřebuje stát objektem.
 */
enum OrderStatus: string
{
    case New = 'nová';
    case Paid = 'zaplacená';
    case Shipped = 'odeslaná';
    case Delivered = 'doručená';
    case Cancelled = 'zrušená';

    /** @return list<self> */
    public function allowedNext(): array
    {
        return match ($this) {
            self::New => [self::Paid, self::Cancelled],
            self::Paid => [self::Shipped, self::Cancelled],
            self::Shipped => [self::Delivered],
            self::Delivered, self::Cancelled => [],
        };
    }

    public function transitionTo(self $target): self
    {
        if (in_array($target, $this->allowedNext(), strict: true) === false) {
            throw new LogicException(sprintf(
                'Ze stavu „%s“ nelze přejít do „%s“. Možné přechody: %s.',
                $this->value,
                $target->value,
                implode(', ', array_map(static fn (self $s): string => $s->value, $this->allowedNext())) ?: 'žádné',
            ));
        }

        return $target;
    }
}
