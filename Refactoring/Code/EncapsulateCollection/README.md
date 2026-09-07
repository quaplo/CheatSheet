# Encapsulate Collection

> [← zpět na Refaktoring kódu](../)

> **V jedné větě:** Přestaň vydávat pole a vydej kolekci, která umí to, co se s ním dělá — a hlídá pravidla, která se dřív neměla kde vynutit.

> [!IMPORTANT]
> **V PHP je tenhle refaktoring o něčem jiném než ve Fowlerově knize.** Tam je hlavním důvodem, že volající může do vrácené kolekce přidávat. **V PHP se pole při vrácení kopíruje, takže to nejde** — a kdo refaktoring dělá kvůli tomuhle, dělá ho zbytečně. Skutečné důvody jsou [dva jiné](#proč-to-v-php-dělat).

---

## Kdy po tom sáhnout

**Poznáš to podle:**

- `array_map` nebo `array_filter` nad **týmž polem** na pěti místech v projektu
- pravidlo o skupině („nejvýš deset položek", „žádná duplicitní SKU") **není nikde** — protože nemá kde být
- z typu `array` nejde poznat, co je uvnitř; věříš jen PHPDoc komentáři
- objekt vydá pole a **volající si s ním dělá, co chce**
- táž otázka („kolik to dělá celkem?") se odpovídá na každém místě znovu

```php
final class Order
{
    /** @var list<OrderItem> */
    private array $items = [];

    /** @return list<OrderItem> */
    public function items(): array
    {
        return $this->items;
    }
}
```

---

## Proč to v PHP dělat

Fowlerův příklad je v JavaScriptu a řeší problém, který v PHP u polí neexistuje:

```
položek v objednávce          2
po přidání do vráceného pole  2
```

**Pole se při vrácení kopíruje.** Přidání zvenčí neprojde. Zůstávají ale dva důvody, a oba jsou vážnější.

### 1. Kopie pole neochrání objekty v něm

```
cena první položky            7 990,00 Kč
po zásahu přes items()        0,01 Kč   ← změněno zvenčí
součet objednávky             4 980,01 Kč
```

`$order->items()[0]->changePrice(1)` projde. **Kopíruje se pole, ne objekty** — ty jsou pořád reference. Objednávka právě přišla o osm tisíc a nic o tom neví.

### 2. Pravidla skupiny nemá kdo hlídat

Tohle je ten hlavní důvod. Pravidlo „nejvýš deset položek" a „žádné duplicitní SKU" se dá napsat leda do každého volajícího zvlášť — a tam se na něj zapomene:

```
položek (limit má být 10)     13
unikátních SKU                12
```

**Třináct položek při limitu deset a jedno SKU dvakrát.** Nikdo to nezachytil, protože pravidlo nemělo kde být.

K tomu se přidává třetí, méně dramatický, ale nejčastější: **logika je rozesetá.**

```
míst pracujících s polem      5
volání array_* funkcí         7
```

Pět míst v projektu — kontroler, šablona, exportér, e-mail, report — dělá totéž nad týmž polem.

---

## Mechanika

Postup níž je náš, v PHP. Každý krok se dá vydat samostatně.

### 0. Testy

Na chování, které se má zachovat: součty, filtry, počty.

**Po tomhle kroku platí:** změna chování by se poznala.

### 1. Vytvoř třídu kolekce

```php
final class OrderItems implements IteratorAggregate, Countable
{
    /** @var list<OrderItem> */
    private array $items = [];

    public function add(OrderItem $item): void
    {
        $this->items[] = $item;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
```

**`IteratorAggregate` a `Countable` hned na začátku.** Bez nich přestane fungovat `foreach` a `count()` a refaktoring narazí na odpor — právem.

**Po tomhle kroku platí:** existuje nepoužitá třída, nic se nezměnilo.

### 2. Nech vlastníka držet kolekci místo pole

```php
private OrderItems $items;

public function items(): OrderItems
{
    return $this->items;
}
```

Návratový typ se změnil, ale díky `IteratorAggregate` a `Countable` **většina volajících funguje dál** — `foreach` i `count()` platí.

**Po tomhle kroku platí:** volající, kteří jen procházejí, jsou hotoví.

### 3. Přesuň operace do kolekce, jednu po druhé

Vezmi jedno místo s `array_map` a přesuň ho:

```php
// Bylo v Usage::total()
array_sum(array_map(fn ($i) => $i->subtotalInCents(), $order->items()));

// Je v OrderItems
public function totalInCents(): int { /* … */ }
```

**Pojmenuj to doménově**, ne technicky: `totalInCents()`, ne `sumSubtotals()`. Právě tady se z pole stává doménový pojem.

**Po tomhle kroku platí:** jedna operace je na jednom místě; ostatní se přesouvají postupně.

### 4. Přidej pravidla skupiny

Až když kolekce existuje, je kam je napsat:

```php
public function add(OrderItem $item): void
{
    if (count($this->items) >= self::MAX_DISTINCT_ITEMS) {
        throw new DomainException(/* … */);
    }
    // …kontrola duplicit…
}
```

```
duplicitní SKU            SKU MON-27 už v objednávce je; zvyš množství.
překročení limitu         Objednávka smí mít nejvýš 10 různých položek.
```

**Po tomhle kroku platí:** pravidla se nedají obejít.

> [!NOTE]
> **Kde se dá zastavit:** po kroku 2 už kolekce existuje a nic není rozbité — zbytek se dá přesouvat postupně, klidně měsíce. Krok 4 je ten, který přináší největší hodnotu, ale dá se udělat až nakonec.

---

## Jak ověřit, že to funguje

- **Testy projdou beze změny**, pokud se používal jen `foreach` a `count()`.
- **Kde se pole indexovalo** (`$items[0]`), testy spadnou a je to správně — takové místo se má přepsat.
- **Po kroku 4 přibudou nové testy** na pravidla skupiny. To už není refaktoring, ale nová funkce, a patří do samostatného commitu.

Poslední bod se snadno slije dohromady. **Zavedení pravidla je změna chování**, i když se dělá „při tom".

---

## Co to stojí

| Cena | Kdy se vyplatí |
| ---- | -------------- |
| **Třída navíc** na každou kolekci | Když má skupina vlastní pravidla nebo operace |
| **Volající se musí přepsat** tam, kde indexovali pole | Když je operací nad kolekcí víc než dvě |
| **Ztráta pohodlí `array_*` funkcí** zvenčí | Když je pohodlí důvod, proč je logika rozesetá |
| **Přibude vrstva** mezi vlastníka a data | Když se pravidla skupiny mají dát vynutit |

Druhý řádek je jediná reálná bolest a týká se míst, která sahala na `$items[0]`. Těch bývá málo — a obvykle jsou to právě ta, kde je něco špatně.

---

## Kdy to nedělat

- ❌ **Kolekce nemá žádná pravidla ani vlastní operace.** Prosté pole hodnot bez chování je v pořádku jako pole.
- ❌ **Používá se na jednom místě a nikde jinde.** Pak stačí metoda na vlastníkovi.
- ❌ **Je to DTO nebo read model.** Tam se data jen předávají a zapouzdření nic nepřinese.
- ❌ **Kolekce je v [generické podoblasti](../../../SoftwareDesign/DDD/GenericSubdomains/).** Tam se investuje málo a jednoduše.

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| Kolekce nemá `IteratorAggregate` a `Countable` | `foreach` a `count()` přestanou fungovat; tým refaktoring odmítne | Doplnit hned v prvním kroku |
| Kolekce vydá `toArray()` a hotovo | Vzniklo obalené pole; logika zůstala venku | Operace patří do kolekce |
| Metody pojmenované technicky (`filter`, `mapPrices`) | Nevznikl doménový pojem, jen jiná syntaxe | `moreExpensiveThan()`, `totalInCents()` |
| Kolekce je měnitelná a předává se dál | Kdokoli ji změní; u [value objektu](../../../SoftwareDesign/DDD/ValueObject/) je to chyba | Zvážit neměnnou variantu |
| Pravidla se přidají v tomtéž commitu | Změna chování schovaná v refaktoringu | Samostatný commit |
| Kolekce vydá své pole ven jiným jménem | Nic se nezměnilo | Vydávat výsledky, ne data |
| Objekty v kolekci zůstanou měnitelné | Kopie pole je neochrání — hlavní díra zůstává | Zvážit neměnné položky |

Poslední řádek je ten, kvůli kterému má tenhle refaktoring v PHP smysl. **Zapouzdřená kolekce s měnitelnými položkami řeší jen půlku problému** — druhá půlka je [Value Object](../../../SoftwareDesign/DDD/ValueObject/).

---

## Kam to vede

Po dokončení máš [**First Class Collection**](../../../SoftwareDesign/ObjectCalisthenics/FirstClassCollection/) — kolekci jako plnohodnotný doménový typ, ne obalené pole.

Ten dokument navazuje tam, kde tenhle končí: rozebírá **neměnnou i měnitelnou variantu** (a proč `withItem()` v cyklu roste kvadraticky), vztah k `IteratorAggregate` a to, co z kolekce dělá v PHP plnohodnotného občana.

---

## Demo

```bash
php Refactoring/Code/EncapsulateCollection/demo/run.php
```

Objednávka a její položky. Demo nejdřív ukáže, že **v PHP vrácené pole opravdu je kopie** — Fowlerův důvod tu neplatí — a hned nato tu skutečnou díru: `$order->items()[0]->changePrice(1)` projde a objednávka přijde o osm tisíc. Pak spočítá rozesetou logiku (5 míst, 7 volání `array_*`) a předvede objednávku se třinácti položkami při limitu deset. Po refaktoringu kolekce obojí odmítne a **`foreach` i `count()` fungují dál**.

---

## Související

| Dokument | Vztah |
| -------- | ----- |
| [Replace Superclass with Delegate](../ReplaceSuperclassWithDelegate/) | Sourozenec: totéž, když kolekce místo pole uvnitř **dědí** z ArrayObject. |
| [First Class Collection](../../../SoftwareDesign/ObjectCalisthenics/FirstClassCollection/) | **Cíl refaktoringu.** Navazuje neměnnou variantou a vztahem k `IteratorAggregate`. |
| [Value Object](../../../SoftwareDesign/DDD/ValueObject/) (DDD) | Druhá půlka problému: zapouzdřená kolekce měnitelných objektů chrání jen zčásti. |
| [Iterator](../../../SoftwareDesign/GoF/Behavioral/Iterator/) (GoF) | Proč `IteratorAggregate` a co umožní — průchod bez vydání vnitřku. |
| [Aggregate](../../../SoftwareDesign/DDD/Aggregate/) (DDD) | Pravidla skupiny bývají invarianty agregátu; kolekce je místo, kde se hlídají. |
| [Specification](../../../SoftwareDesign/DDD/Specification/) (DDD) | Když filtrů přibývá, předává se kolekci podmínka místo psaní další metody. |
| [Tell, Don't Ask](../../../SoftwareDesign/Principles/ObjectDesign.md#tell-dont-ask) | Princip, který se tím naplní: neptej se na pole, řekni kolekci, co chceš. |
| [Replace Primitive with Object](../ReplacePrimitiveWithObject/) | Druhá půlka téhož problému: tam se zapouzdřuje jednotlivá hodnota, tady skupina. |
| [Charakterizační testy](../../CharacterizationTests/) | **Krok nula.** Co dělat, když testy neexistují a zadání, podle kterého by se napsaly, taky ne. |

---

## Původ

|             |                    |
| ----------- | ------------------ |
| **Autor**   | Martin Fowler      |
| **Rok**     | 1999, přepracováno 2018 |
| **Zdroj**   | *Refactoring*, katalog |
| **Náročnost** | ●●○○○            |

Fowler refaktoring popsal v obou vydáních *Refactoringu* a v katalogu ukazuje getter, který místo vnitřního pole vrací jeho kopii (`.slice()`), plus metody `addCourse()` a `removeCourse()`. Na svém bliki k tomu dodává, že nejlepší je **read-only pohled**, a kopie je náhradní řešení tam, kde ho jazyk nenabízí.

**V PHP je situace jiná a stojí za to ji znát**, protože mění důvod, proč refaktoring dělat. Pole je hodnotový typ a při vrácení se kopíruje — Fowlerův hlavní argument tedy odpadá. Zůstávají dva jiné, které Fowlerův katalog nezdůrazňuje: **objekty uvnitř zůstávají měnitelné** a **pravidla skupiny nemají kde být**.

Náročnost je dvojka. Mechanika je jednoduchá a testy jistí správnost; jediná past je krok 1 — **kolekce bez `IteratorAggregate` a `Countable` rozbije `foreach` a `count()` po celém projektu** a tým refaktoring právem odmítne.

---

## Zdroje

- Martin Fowler: [*Encapsulate Collection*](https://refactoring.com/catalog/encapsulateCollection.html) — katalog online
- Martin Fowler: [*Encapsulated Collection*](https://www.martinfowler.com/bliki/EncapsulatedCollection.html) — bliki
- Martin Fowler: *Refactoring*, 2. vydání, Addison-Wesley, 2018

---

<details>
<summary>Metadata techniky</summary>

```yaml
name: Encapsulate Collection
level: code
author: Martin Fowler
year: 1999
duration: hodiny
reversible: ano
requires_tests: ano
difficulty: 2
tags: [zapouzdření, kolekce, invarianty, IteratorAggregate]
leads_to: [FirstClassCollection]
related: [FirstClassCollection, ValueObject, Iterator, Aggregate, Specification]
status: done
```

</details>
