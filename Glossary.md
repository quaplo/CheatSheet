# Slovníček

Pojmy, které se v katalogu opakují napříč patterny, ale nemají vlastní dokument.

> [!NOTE]
> **Co tady schválně není:** pojmy, které mají vlastní dokument. Ty se nevysvětlují dvakrát — [seznam s odkazy](#pojmy-které-mají-vlastní-dokument) je na konci.

---

### Idempotence

**Operace je idempotentní, když ji můžeš spustit vícekrát a výsledek je stejný jako po prvním spuštění.**

```
idempotentní:      „nastav stav na zaplaceno“ · „smaž objednávku 4711“ · „vystav dobropis k platbě PAY-1“
NENÍ idempotentní: „přičti 100 bodů“ · „vystav dobropis“ (bez určení ke které platbě)
```

**Proč na tom záleží:** zprávy se doručují **aspoň jednou**. To znamená, že tvůj kód dostane tutéž zprávu i podruhé — při opakování po timeoutu, po restartu workera, po výpadku sítě. Bez idempotence dostane zákazník dva dobropisy.

Nejjednodušší způsob, jak ji zařídit: **operace se nejdřív podívá, jestli už proběhla**, a když ano, tiše skončí.

Kde v katalogu: [Saga](Architecture/Saga/#idempotence-není-volitelná) · [Domain Event](DDD/DomainEvent/)

---

### Neměnnost (immutability)

**Objekt, který po vytvoření nejde změnit.** Každá „úprava“ vrací novou instanci, původní zůstává.

```php
final readonly class Money          // ← neměnný
{
    public function add(self $other): self
    {
        return new self($this->amountInCents + $other->amountInCents, $this->currency);
    }
}
```

**Proč na tom záleží:** neměnný objekt ti nikdo nezmění pod rukama. Odpadá celá kategorie chyb, kdy jedna část kódu upraví objekt, který si drží i někdo jiný — a ten druhý o tom neví.

**Výjimka, která potvrzuje pravidlo:** [entita](DDD/Entity/) se měnit **má**. To je celý důvod, proč má identitu — ta ji drží pohromadě, zatímco se atributy mění. Neměnné mají být **hodnoty**, ne entity.

Kde v katalogu: [Value Object](DDD/ValueObject/) · [First Class Collection](ObjectCalisthenics/FirstClassCollection/#neměnná-nebo-měnitelná) · [Entity](DDD/Entity/#proč-entita-není-readonly)

---

### Invariant

**Pravidlo, které musí platit pořád**, ne jen ve chvíli, kdy se něco kontroluje.

> „Součet položek objednávky nepřesáhne schválený limit.“
> „Objednávka má aspoň jednu položku.“
> „Částka je v jedné měně.“

**Rozdíl proti validaci:** validace kontroluje **vstup** v jednom okamžiku. Invariant hlídá **objekt sám** po celou dobu své existence — nesmí existovat cesta, jak ho porušit. Proto se invarianty hlídají v konstruktoru a v metodách, ne ve formuláři.

Kde v katalogu: [Aggregate](DDD/Aggregate/) (invariant celku) · [Entity](DDD/Entity/) · [Value Object](DDD/ValueObject/)

---

### Eventuální konzistence

**Data budou konzistentní „za chvíli“, ne okamžitě.** Mezi změnou a tím, než ji uvidí všichni, uplyne nějaký čas — obvykle milisekundy, občas víc.

**Praktický důsledek, který se podceňuje:** uživatel něco uloží a hned to ve výpisu nevidí. Není to chyba, je to vlastnost — a **musí s ní počítat i rozhraní**. „Zpracováváme…“ je legitimní stav, který se dá ukázat.

Opakem je *okamžitá* (silná) konzistence, kterou dostaneš uvnitř jedné databázové transakce. Přes hranice služeb ji obvykle mít nemůžeš.

Kde v katalogu: [CQRS](Architecture/CQRS/#škála-na-které-si-vyber) · [Saga](Architecture/Saga/) · [Aggregate](DDD/Aggregate/) · [Domain Event](DDD/DomainEvent/)

---

### DTO — data transfer object

**Objekt, který jen nese data přes hranici.** Žádné chování, žádná pravidla, žádná identita.

**Rozdíl proti [value objectu](DDD/ValueObject/):** value object má pravidla (neplatný nevznikne) a chování (`add()`, `format()`). DTO nemá ani jedno — je to tvar, ve kterém data cestují.

Typické použití: vstup use-case (příkaz), výstup dotazu (čtecí model), zpráva na hranici kontextu.

Kde v katalogu: [CQRS](Architecture/CQRS/) · [Service Layer](PoEAA/ServiceLayer/) · [Service Composition](Architecture/ServiceComposition/)

---

### Bezstavovost (stateless)

**Objekt si mezi voláními nic nepamatuje.** Jedna instance obslouží libovolně mnoho operací a ty se navzájem neovlivní.

**Proč na tom záleží:** bezstavovou službu jde bezpečně sdílet — DI kontejner ji vytvoří jednou a předá všem. Kdyby si něco pamatovala, dvě souběžné operace by si navzájem přepsaly data.

Poznávací znamení v PHP: `final readonly class` bez vlastností, nebo jen s těmi, které se nastaví v konstruktoru a už se nemění.

Kde v katalogu: [Domain Service](DDD/DomainService/) · [Specification](DDD/Specification/) · [Strategy](GoF/Behavioral/Strategy/)

---

### N+1

**Načteš N záznamů a pak pro každý z nich uděláš další dotaz** — dohromady N+1 dotazů místo jednoho nebo dvou.

Vzniká nenápadně: cyklus, ve kterém se sahá na něco, co se dotahuje líně. V kódu není vidět, v logu databáze ano.

Demo u [CQRS](Architecture/CQRS/) to měří: **1001 dotazů proti jednomu** pro tentýž výpis dvaceti řádků.

Kde v katalogu: [Repository](PoEAA/Repository/) · [CQRS](Architecture/CQRS/)

---

### Časová vazba (temporal coupling)

**Aby fungovalo A, musí zrovna teď běžet i B.** Vzniká vždy, když voláš cizí službu synchronně.

Cena se dá spočítat: každá služba má 99,9 %, ale při synchronním volání se jejich nedostupnosti sčítají — u osmi služeb je z 43 minut výpadku měsíčně **skoro šest hodin**.

Kde v katalogu: [Service Composition](Architecture/ServiceComposition/#cena-za-synchronní-volání)

---

## Pojmy, které mají vlastní dokument

Tyhle se tady schválně nevysvětlují — mají svůj pattern:

| Pojem | Kde |
| ----- | --- |
| Agregát, kořen agregátu, hranice konzistence | [Aggregate](DDD/Aggregate/) |
| Anemický model | [Entity](DDD/Entity/#problém) |
| Antikorupční vrstva | [Anticorruption Layer](DDD/AnticorruptionLayer/) |
| Bounded context, jednotný jazyk | [Bounded Context](DDD/BoundedContext/) |
| Doménová vs integrační událost | [Domain Event](DDD/DomainEvent/) |
| Doménová vs aplikační služba | [Domain Service](DDD/DomainService/) |
| Kompenzace, pivotní krok | [Saga](Architecture/Saga/) |
| Port, adaptér | [Ports & Adapters](Architecture/PortsAndAdapters/) |
| **Řídicí vs řízený port** (primary/driving × secondary/driven) | [Ports & Adapters → Dvě strany](Architecture/PortsAndAdapters/#dvě-strany-na-jednu-se-zapomíná) |
| Příkaz vs dotaz, čtecí model | [CQRS](Architecture/CQRS/) |
| Repository, identita z aplikace | [Repository](PoEAA/Repository/) |
| Use-case, command handler, sběrnice | [Service Layer](PoEAA/ServiceLayer/) |
| Value object vs entita | [Value Object](DDD/ValueObject/) · [Entity](DDD/Entity/) |
