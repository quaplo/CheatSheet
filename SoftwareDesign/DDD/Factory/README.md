# Factory (Továrna)

> [← zpět na DDD](../)

> **V jedné větě:** Vytvoř celý [agregát](../Aggregate/) najednou a vynuť při tom jeho pravidla — místo aby si ho volající skládal sám a mohl ho složit špatně.

> [!NOTE]
> **Nezaměňovat s [Factory Method](../../GoF/Creational/FactoryMethod/) z GoF.** Ten řeší, *která třída* se má vytvořit, a je o polymorfismu. Tenhle vzor řeší, *aby objekt vznikl v platném stavu*, a je o invariantech. Podrobné [srovnání níž](#factory-v-ddd-a-factory-method-v-gof).

---

## Problém

Agregát má pravidla, která musí platit vždycky. Objednávka má aspoň jednu položku, součet sedí, u platby předem je limit. Když si ji volající skládá sám, musí ta pravidla znát — a musí je znát na každém místě, kde objednávka vzniká.

Evans:

> „Creation of an object can be a major operation in itself, but **complex assembly operations do not fit the responsibility of the created objects.** Combining such responsibilities can produce ungainly designs that are hard to understand. **Making the client direct construction muddies the design of the client, breaches encapsulation of the assembled object or aggregate, and overly couples the client to the implementation** of the created object."

**Poznáš to podle:**

- konstruktor má **osm parametrů** a půlka z nich jde odvodit z ostatních
- volající počítá **součet položek** a předává ho do konstruktoru
- pravidlo „objednávka musí mít aspoň jednu položku" je **na třech místech**
- jde vytvořit objekt, který je **zjevně neplatný**, a nic to nezachytí
- v testu se agregát skládá jinak než v produkčním kódu — a **jinak správně**
- `new Order(...)` nikde neříká, jestli objednávka **vzniká, nebo se načítá**

```php
// Před: volající musí znát vnitřek i pravidla
$total = 0;
foreach ($lines as $line) {
    $total += $line->unitPrice->amountInCents * $line->quantity;   // ← počítá klient
}

$order = new Order($number, $lines, Money::fromCents($total), 'předem');
// …a nikdo neověřil, že položky nejsou prázdné, že se SKU neopakují
// ani že platba předem má limit
```

---

## Řešení

> „Therefore: **Shift the responsibility for creating instances of complex objects and aggregates to a separate object**, which may itself have no responsibility in the domain model but is still part of the domain design. **Provide an interface that encapsulates all complex assembly** and that does not require the client to reference the concrete classes of the objects being instantiated. **Create an entire aggregate as a piece, enforcing its invariants.** Create a complex value object as a piece, possibly after assembling the elements with a builder."

```php
final class Order
{
    private function __construct(/* … */) {}

    /** @param list<OrderLine> $lines */
    public static function place(string $number, array $lines, string $paymentMethod): self
    {
        if ($lines === []) {
            throw new DomainException('Objednávka musí mít aspoň jednu položku.');
        }

        // …kontrola duplicitních SKU…

        $total = Money::fromCents(0);

        foreach ($lines as $line) {
            $total = $total->add($line->subtotal());
        }

        // …kontrola limitu pro platbu předem…

        return new self($number, $lines, $total, $paymentMethod);
    }
}
```

```
objednávka:            2026/001
položek:               2
celkem:                3 970,00 Kč   ← spočítala továrna, ne volající
```

**Volající předal položky a dostal platnou objednávku.** Nemusel počítat součet ani znát pravidla.

### Invarianty na jednom místě

```
objednávka bez položek        Objednávka musí mít aspoň jednu položku.
stejné SKU dvakrát            SKU MON-27 je v objednávce dvakrát; slučte položky.
platba předem nad limit       Platba předem je možná do 5 000,00 Kč; tahle objednávka je za 8 970,00 Kč.
```

Klíčové slovo v Evansově definici je **„as a piece"** — celý agregát najednou. Ne založit prázdný a postupně doplňovat; mezi tím by existoval objekt v neplatném stavu a nikdo by nevěděl, kdy se stane platným.

Souvisí to s [Fail Fast](../../Principles/ObjectDesign.md#fail-fast): objekt buď vznikne správně, nebo nevznikne vůbec.

### Konstruktor je soukromý

```
konstruktor je:        private
parametrů:             4
```

Kdyby byl veřejný, byla by továrna jen doporučení. Volající by musel:

1. spočítat součet položek správně
2. ověřit, že se SKU neopakují
3. znát limit pro platbu předem
4. **udělat to všechno znovu na každém místě, kde objednávka vzniká**

Poslední bod je ten, který rozhoduje. Jedno místo, kde se na to zapomene, stačí.

### Vytvoření není rekonstrukce

Nejdůležitější rozlišení celého vzoru a v knize se dovozuje spíš, než říká přímo:

```
                          place()               reconstitute()
kontroluje invarianty     ano                   ne
kdy se použije            nová objednávka       načtení z databáze
kdo ji volá               doména                repository / mapper
```

Proč rekonstrukce nekontroluje pravidla, ukáže demo na konkrétní situaci:

```
stará objednávka z roku 2019:  12 000,00 Kč, platba předem
přes place() by dnes:          neprošla
```

Limit pro platbu předem se mezitím snížil. **Kdyby `reconstitute()` kontrolovalo pravidla, nešlo by načíst objednávku, která byla podle starých pravidel platná** — a systém by nešel spustit nad vlastními daty.

Rozdíl je v tom, co ta metoda znamená: `place()` říká *„tohle se právě stalo"*, `reconstitute()` říká *„tohle se kdysi stalo a já to jen znovu skládám"*. Podrobněji je to u [Entity](../Entity/) a u [Data Mapperu](../../PoEAA/DataMapper/).

### Kde továrna žije

Evans připouští dvě umístění:

| Kde | Kdy | Příklad |
| --- | --- | ------- |
| **Statická metoda na agregátu** | Výchozí volba; továrna nepotřebuje závislosti | `Order::place(...)` |
| **Samostatná třída** | Když sestavení potřebuje závislosti nebo je opravdu složité | `OrderFactory` s generátorem čísel |

Evans u samostatné třídy dodává, že *„may itself have no responsibility in the domain model but is still part of the domain design"* — továrna **není** [doménová služba](../DomainService/) a nemá v doméně žádnou vlastní roli; je to prostředek, jak agregát vzniká.

**Výchozí volba je statická metoda.** Samostatnou třídu zaveď, až když továrna potřebuje něco zvenčí — typicky generátor identifikátorů nebo kurzovní lístek.

---

## Účastníci

| Role | V příkladu | Odpovědnost |
| ---- | ---------- | ----------- |
| **Továrna** | `Order::place()` | Sestaví celý agregát a vynutí invarianty |
| **Vytvářený agregát** | `Order` | Má soukromý konstruktor; sám o sestavení nerozhoduje |
| **Rekonstrukce** | `Order::reconstitute()` | Obnoví stav bez kontrol |
| **Klient** | use-case | Předá vstupy; o pravidlech nic neví |

---

## Implementace v PHP

### Pojmenovaná statická metoda

PHP nemá pojmenované konstruktory, ale statická metoda je zastoupí a navíc **nese jméno**:

```php
Order::place($number, $lines, $paymentMethod);       // nová objednávka
Order::reconstitute($number, $lines, $total, $pm);   // z databáze
```

```
veřejných konstruktorů:  0
pojmenovaných cest:      2
```

`new Order(...)` by neřeklo nic — a hlavně by **nešlo rozlišit, jestli objednávka vzniká, nebo se načítá**. To je praktický důvod, proč se konstruktor schovává i tam, kde žádné složité sestavení není.

### Když je parametrů moc, přijde na řadu builder

Evans to má v definici: *„possibly after assembling the elements with a builder."*

```php
// Když má sestavení hodně kroků a část je volitelná
$order = OrderBuilder::for($customer)
    ->addLine('MON-27', 1)
    ->addLine('KLA-01', 2)
    ->payingBy('na dobírku')
    ->place();          // ← teprve tady vzniká agregát a kontrolují se invarianty
```

Podstatné je, že **builder sbírá vstupy, ale agregát vzniká až na konci** — jedním krokem, přes továrnu. Kdyby builder skládal objekt průběžně, byli bychom zpátky u neplatných mezistavů. Rozdíl mezi oběma vzory rozebírá [Builder](../../GoF/Creational/Builder/).

### Co továrna vracet nemá

```php
// Špatně: továrna vrací null a volající musí hádat proč
public static function place(/* … */): ?self

// Správně: buď platný agregát, nebo výjimka s důvodem
public static function place(/* … */): self
{
    throw new DomainException('Objednávka musí mít aspoň jednu položku.');
}
```

`null` z továrny znamená, že volající zase musí znát pravidla — jen o úroveň jinak. Výjimka nese **důvod**, a ten je součástí domény.

---

## Factory v DDD a Factory Method v GoF

Nejčastější zdroj zmatku, protože jména jsou skoro stejná.

| | **Factory (DDD)** | [**Factory Method (GoF)**](../../GoF/Creational/FactoryMethod/) |
| --- | --- | --- |
| Co řeší | **Aby objekt vznikl v platném stavu** | **Která třída se má vytvořit** |
| Hlavní přínos | Vynucené invarianty, zapouzdření sestavení | Polymorfismus; volající nezná konkrétní typ |
| Typická podoba | Statická metoda na agregátu | Metoda přepsaná v potomkovi |
| Kolik typů vrací | **Jeden** | **Víc — o to jde** |
| Kdy sáhnout | Složité sestavení s pravidly | Rozhodnutí o typu podle kontextu |

**Můžou se potkat** — továrna, která podle vstupu vrátí `RetailOrder` nebo `WholesaleOrder`, dělá obojí. Ale je užitečné vědět, kterou z těch dvou věcí zrovna řešíš.

---

## Kdy použít

- ✅ **Agregát má invarianty**, které musí platit od první chvíle.
- ✅ **Sestavení je složité** nebo odhaluje vnitřní strukturu.
- ✅ Objekt vzniká **na víc místech** a pravidla by se opakovala.
- ✅ Potřebuješ rozlišit **vytvoření od rekonstrukce**.
- ✅ Vytvoření má v doméně **jméno** — „objednávka se podá", „faktura se vystaví".

## Kdy nepoužít

- ✅ **Konstruktor stačí.** Jednoduchý [value object](../ValueObject/) se dvěma poli továrnu nepotřebuje.
- ❌ **Továrna by jen volala konstruktor.** Vrstva bez užitku.
- ❌ **Objekt nemá invarianty.** DTO nebo read model se skládá volně a je to v pořádku.
- ❌ **Řešíš, který typ vytvořit.** To je [Factory Method](../../GoF/Creational/FactoryMethod/).
- ❌ **Sestavení potřebuje data z databáze.** Pak si je obstarej dřív a předej je do továrny — jinak z ní vznikne služba.

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| Konstruktor zůstane veřejný | Továrna je jen doporučení; jednou se obejde | `private function __construct` |
| Rekonstrukce kontroluje invarianty | Nejde načíst data platná podle starých pravidel | `reconstitute()` bez kontrol |
| Agregát se skládá postupně | Existuje neplatný mezistav | Evans: **create as a piece** |
| Továrna vrací `null` | Volající zase musí znát pravidla | Výjimka s doménovým důvodem |
| Továrna si sahá do databáze | Přestane jít otestovat a stane se službou | Data dostane na vstupu |
| Volající počítá součet a předá ho | Pravidlo je venku a dá se ho porušit | Počítá továrna |
| Jedna továrna pro vytvoření i načtení | Nejde rozlišit, co se právě děje | Dvě pojmenované metody |
| Továrna jako [doménová služba](../DomainService/) | Evans: nemá v modelu vlastní roli | Statická metoda, nebo prostá třída |
| Sestavení pravidel v testu jinak než v kódu | Testuje se stav, který v produkci nevznikne | Testy staví agregát toutéž továrnou |

---

## V praxi

- **Doctrine a soukromé konstruktory** — ORM umí naplnit objekt přes reflexi, takže `private __construct` mu nevadí; `reconstitute()` je pak explicitní varianta téhož.
- **`DateTimeImmutable::createFromFormat()`** — pojmenovaná statická továrna v samotném PHP.
- **`Money::fromCents()` vs `Money::fromString()`** — dvě jména, dvě intence, jeden typ.
- **Test data buildery** — v testech staví agregáty přes tutéž továrnu, jen s rozumnými výchozími hodnotami; [Builder](../../GoF/Creational/Builder/#test-data-builder) to rozebírá.

---

## Související patterny

| Pattern | Vztah |
| ------- | ----- |
| [Aggregate](../Aggregate/) | **Hlavní důvod, proč továrna existuje** — agregát musí vzniknout celý a s platnými invarianty. |
| [Factory Method](../../GoF/Creational/FactoryMethod/) (GoF) | **Podobné jméno, jiný problém.** [Srovnání výš](#factory-v-ddd-a-factory-method-v-gof). |
| [Builder](../../GoF/Creational/Builder/) (GoF) | Sbírá vstupy, když je jich hodně; agregát pak vznikne jedním voláním továrny. Evans to zmiňuje přímo. |
| [Entity](../Entity/) | Odtud pochází dvojice „vytvoření vs. rekonstrukce" i `nextIdentity()`. |
| [Value Object](../ValueObject/) | Evans mluví i o „large value object"; složená hodnota se skládá stejně. |
| [Repository](../../PoEAA/Repository/) (PoEAA) | Volá rekonstrukci při načtení a `nextIdentity()` před vytvořením. |
| [Data Mapper](../../PoEAA/DataMapper/) (PoEAA) | Druhá cesta k témuž — mapper obnovuje stav bez kontrol. |
| [Specification](../Specification/) | Když je pravidlo složité nebo znovupoužitelné, továrna se na něj zeptá místo vlastní podmínky. |

---

## Vztah k principům

| Princip | Jak souvisí |
| ------- | ----------- |
| [Fail Fast](../../Principles/ObjectDesign.md#fail-fast) | Objekt buď vznikne správně, nebo nevznikne. Žádné neplatné mezistavy. |
| [SRP](../../Principles/SOLID.md#single-responsibility-principle-srp) | Sestavení je jiná odpovědnost než chování; agregát nemusí umět obojí. |
| [Zviditelni implicitní](../../Principles/ObjectDesign.md#zviditelni-implicitní) | `place()` a `reconstitute()` říkají, co se děje; `new` neříká nic. |
| [Nízká provázanost](../../Principles/CohesionAndCoupling.md#stupnice-provázanosti) | Volající nezná vnitřní strukturu agregátu ani jeho pravidla. |
| [DRY](../../Principles/Simplicity.md#dry--dont-repeat-yourself) | Invarianty na jednom místě místo na každém, kde agregát vzniká. |

---

## Demo

```bash
php SoftwareDesign/DDD/Factory/demo/run.php
```

Agregát objednávky se čtyřmi invarianty. Demo vytvoří objednávku jedním voláním, pak **zkusí tři způsoby, jak ji vytvořit špatně**, a ukáže, co továrna nepustí. Reflexí ověří, že konstruktor je soukromý a že k agregátu vedou jen dvě pojmenované cesty. Poslední část staví **vytvoření proti rekonstrukci** na objednávce z roku 2019, která by podle dnešních pravidel neprošla — a proto ji `reconstitute()` kontrolovat nesmí.

---

## Původ

|               |                                                   |
| ------------- | ------------------------------------------------- |
| **Zdroj**     | *Domain-Driven Design: Tackling Complexity in the Heart of Software* |
| **Autor**     | Eric Evans                                        |
| **Rok**       | 2003                                              |
| **Kategorie** | Taktický návrh — stavební bloky (kapitola 6)      |
| **Obtížnost** | ●●○○○                                             |

Evans ji řadí mezi stavební bloky vedle [entit](../Entity/), [hodnot](../ValueObject/) a [repository](../../PoEAA/Repository/) — tedy mezi věci, ze kterých se model skládá. Zajímavé je, jak opatrně ji vymezuje: *„a separate object, which **may itself have no responsibility in the domain model** but is still part of the domain design."*

Továrna tedy **není součástí modelu domény, ale je součástí jeho návrhu.** Toho rozlišení se vyplatí držet — jakmile začne továrna dělat doménová rozhodnutí, stala se z ní [doménová služba](../DomainService/) a patří jinam.

Obtížnost je dvojka. Napsat statickou metodu, která ověří pár podmínek, je snadné; **těžší je ubránit se dvěma pokušením**:

- **Nechat konstruktor veřejný** „pro jistotu" nebo kvůli ORM. Tím se z továrny stane doporučení a jednou se obejde.
- **Nechat rekonstrukci kontrolovat pravidla**, protože to vypadá bezpečněji. Ve skutečnosti to znamená, že se nad vlastními historickými daty systém nerozjede.

Za zmínku stojí, že v PHP je tenhle vzor snazší než v jazycích, kde jsou konstruktory jediná cesta k objektu. Statické pojmenované metody jsou tu běžné a **`DateTimeImmutable::createFromFormat()` je továrna v jádru jazyka** — takže vzor bývá v týmu zavedený dřív, než mu někdo dá jméno.

---

## Zdroje

- Eric Evans: *Domain-Driven Design*, Addison-Wesley, 2003 — kapitola 6, *The Life Cycle of a Domain Object*
- Eric Evans: [*Domain-Driven Design Reference*](https://www.domainlanguage.com/wp-content/uploads/2016/05/DDD_Reference_2015-03.pdf) (PDF, 2015) — souhrn definic, pod licencí CC BY 4.0

---

<details>
<summary>Metadata patternu</summary>

```yaml
name: Factory
name_cs: Továrna
category: Taktický návrh — stavební bloky
source: DDD – Domain-Driven Design
authors: Eric Evans
year: 2003
difficulty: 2
tags: [vytvoření, invarianty, agregát, rekonstrukce, pojmenovaný konstruktor]
principles: [FailFast, SRP, MakeImplicitExplicit, CohesionAndCoupling, DRY]
related: [Aggregate, FactoryMethod, Builder, Entity, ValueObject, Repository, DataMapper, Specification]
status: done
```

</details>
