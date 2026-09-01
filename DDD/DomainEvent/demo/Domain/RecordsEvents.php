<?php

declare(strict_types=1);

namespace Domain;

/**
 * Agregát události ZAZNAMENÁVÁ, nikoli publikuje.
 *
 * To je zásadní rozdíl. Kdyby si agregát rovnou volal dispatcher,
 * potřeboval by ho v konstruktoru — a doménový objekt by najednou
 * závisel na infrastruktuře. Takhle jen zapíše, co se stalo, a kdo
 * a kdy to rozešle, je věc aplikační vrstvy.
 */
trait RecordsEvents
{
    /** @var list<DomainEvent> */
    private array $recordedEvents = [];

    private function recordThat(DomainEvent $event): void
    {
        $this->recordedEvents[] = $event;
    }

    /**
     * Vybere zaznamenané události a agregát vyprázdní.
     *
     * Volá aplikační vrstva po úspěšném uložení — proto „release“,
     * ne „get“: události si nikdo nesmí přečíst dvakrát.
     *
     * @return list<DomainEvent>
     */
    public function releaseEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }
}
