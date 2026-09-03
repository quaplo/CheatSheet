# Iterator (Iterátor)

> [← zpět na Behavioral](../)

> **V jedné větě:** Průchod kolekcí, aniž bys věděl, co je uvnitř — a v moderním PHP hlavně způsob, jak procházet víc dat, než se vejde do paměti.

> [!NOTE]
> **PHP tenhle vzor má zabudovaný** — `foreach`, `Iterator`, `IteratorAggregate`, generátory. Ruční `implements Iterator` s pěti metodami dnes skoro nikdy nepíšeš. Zajímavá otázka proto není *jak ho napsat*, ale [kdy si ho psát sám](#kdy-si-iterátor-psát-a-jaký) a co ti generátory umožní, co jinak nejde.

---

## Problém

Máš kolekci a někdo ji potřebuje projít. Buď mu vydáš vnitřek, nebo mu na každý průchod napíšeš metodu.

**Poznáš to podle:**

- kolekce má `toArray()` nebo `getItems()` a volající si dělá `foreach` nad vnitřkem
- změna vnitřní struktury (pole → `SplHeap` → kurzor) **rozbije volající kód**
- na každý způsob průchodu vzniká další metoda: `allSorted()`, `allFiltered()`, `allSortedFiltered()`
- načte se milion řádků do pole, protože jinak to nejde projít — a aplikace spadne na paměti
- nejde napsat průchod, který **nemá konec** nebo je předem neznámý
- filtr vyrobí druhé pole vedle prvního, i když z něj potřebuješ tři položky

```php
// Před: struktura vyleze ven
foreach ($orderItems->toArray() as $item) { /* … */ }

// …a tohle spadne na paměti dřív, než se dostane k prvnímu řádku
$rows = $repository->findAll();     // milion záznamů
foreach ($rows as $row) { /* … */ }
```

---

## Řešení

Dej kolekci schopnost **vydávat prvky po jednom** a nech si vnitřek pro sebe.

V PHP se to nedělá ručně. `IteratorAggregate` říká, **kdo** umí procházet — a `foreach` se postará o zbytek:

```php
/** @implements IteratorAggregate<string, OrderItem> */
final class OrderItems implements IteratorAggregate, Countable
{
    /** @var array<string, OrderItem> */
    private array $items = [];

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
```

```php
foreach ($items as $sku => $item) { /* … */ }
count($items);
```

Uvnitř je pole indexované podle SKU. **Kdyby se z toho zítra stal `SplHeap` nebo databázový kurzor, tenhle `foreach` zůstane.**

### Kdy si iterátor psát a jaký

Tři možnosti a rozdíl mezi nimi je praktický:

| | `IteratorAggregate` | `Iterator` | **Generátor** |
| --- | --- | --- | --- |
| Co napíšeš | Jednu metodu | **Pět metod** | `yield` v běžné metodě |
| Kdy | Kolekce už data má | Průchod má vlastní stav | **Data vznikají za chodu** |
| Paměť | Podle zdroje | Podle zdroje | **Konstantní** |
| Líné vyhodnocení | Ne | Jde, ale ručně | **Ano, samo** |
| Jak často v PHP | Často | **Skoro nikdy** | Často |

**Ruční `implements Iterator` s `current()`, `key()`, `next()`, `rewind()`, `valid()` dnes prakticky nepíšeš.** Buď máš data a stačí `IteratorAggregate`, nebo je vyrábíš a chceš generátor.

### Generátor: co s polem nejde

Tohle je ta část, kvůli které je Iterator v PHP pořád živý vzor. Demo měří stotisíc položek:

```
                      paměť      součet cen
pole                  42,0 MB   59 999 500 Kč
generátor              0,0 MB   59 999 500 Kč
```

Výsledek je stejný, paměť ne. **Pole si drží všechny položky naráz, generátor vždycky jen jednu.**

A pak to podstatné — co s polem nejde vůbec:

```
Kdybych chtěl 3 000 000 položek:
    pole by potřebovalo    ~1 260,5 MB   (odhad z naměřené ceny na položku)
    limit tohohle procesu:  128M
    generátor to zvládl:    0,0 MB za 0.3 s
```

To není optimalizace. To je rozdíl mezi **„projde to“** a **„spadne to na paměti“**.

### Líné vyhodnocení

Generátor vyrobí hodnotu, až když si o ni někdo řekne. Proto jde napsat i posloupnost, která **nemá konec**:

```php
public static function infinitePrices(): Generator
{
    $price = 10000;

    while (true) {
        yield $price;
        $price += 500;
    }
}
```

```php
foreach (LargeCatalog::infinitePrices() as $price) {
    if ($price > 12000) {
        break;
    }
    // …
}
```

`while (true)` a přesto to doběhne. **Nekonečná řada je legitimní věc, ne chyba** — a bez líného vyhodnocení by to nešlo napsat.

### `yield from` a stromy

Průchod stromem je místo, kde se Iterator a [Composite](../../Structural/Composite/) potkávají. `yield from` je rekurze v generátoru:

```php
public function getIterator(): Generator
{
    yield $this;

    foreach ($this->children as $child) {
        yield from $child;
    }
}
```

```
Elektronika → Monitory → Herní → Kancelářské → Periferie → Klávesnice
uzlů: 6, a foreach o hloubce stromu neví nic
```

Volající dostane plochou posloupnost. **O tom, že je uvnitř strom, neví.**

### Past, na kterou narazí každý

```
první průchod:  3 položek
druhý průchod:  Cannot traverse an already closed generator
```

**Pole projdeš kolikrát chceš, generátor jednou.** Tohle je nejčastější překvapení a má dvě řešení:

| Situace | Řešení |
| ------- | ------ |
| Volající to potřebuje víckrát | Vrať pole, nebo `iterator_to_array()` |
| Volající to *možná* potřebuje víckrát | Vrať **továrnu**, která generátor vyrobí znovu |
| Data se vejdou do paměti a jde o pohodlí | Prostě pole — generátor tu nic nepřidá |

Praktický důsledek pro návrh API: **návratový typ `Generator` je slib, který volajícího omezuje.** Když si nejsi jistý, vrať `iterable` a nech si otevřená vrátka.

---

## Účastníci

| Role | V příkladu | Odpovědnost |
| ---- | ---------- | ----------- |
| **Kolekce** | `OrderItems`, `CategoryNode` | Umí vydat iterátor; vnitřek nevydává |
| **Iterátor** | `ArrayIterator`, `Generator` | Drží pozici v průchodu |
| **Klient** | `foreach` | Prochází a o struktuře neví nic |

---

## Implementace v PHP

Generátor jako filtr — nevrací pole, vrací postupně:

```php
/** @return Generator<string, OrderItem> */
public function moreExpensiveThan(int $priceInCents): Generator
{
    foreach ($this->items as $sku => $item) {
        if ($item->priceInCents > $priceInCents) {
            yield $sku => $item;
        }
    }
}
```

Nad třemi položkami je to jedno. Nad milionem je to rozdíl mezi funkční a nefunkční aplikací.

### Co PHP nabízí a co se skutečně používá

| Nástroj | K čemu | Používá se |
| ------- | ------ | ---------- |
| `IteratorAggregate` | Kolekce vydá svůj iterátor | **Ano, běžně** |
| Generátory (`yield`) | Data vznikají za chodu, líně | **Ano, hodně** |
| `yield from` | Rekurze, delegace na jiný iterátor | Ano, u stromů |
| `iterable` typ | Přijmi pole **i** iterátor | Ano, v API |
| `iterator_to_array()` | Generátor → pole | Ano, opatrně |
| `ArrayIterator` | Pole jako objekt | Ano, uvnitř `getIterator()` |
| `Iterator` ručně | Průchod s vlastním stavem | **Zřídka** |
| `SPL` iterátory (`LimitIterator`, `CallbackFilterIterator`) | Skládání průchodů | Zřídka, generátory jsou čitelnější |
| `RecursiveIteratorIterator` | Stromy | Zřídka, `yield from` je jednodušší |

### `iterable` v podpisech

Drobnost, která se vyplatí:

```php
// Přijme pole i generátor — volající si vybere
public function process(iterable $items): void

// Přijme jen pole — volající musí materializovat
public function process(array $items): void
```

**Na vstupu ber `iterable`**, ať volajícího nenutíš vytvářet pole zbytečně. **Na výstupu** se rozhodni vědomě: `array` je pohodlnější, `Generator` úspornější, `iterable` nechává volnost tobě.

---

## Kdy použít

- ✅ Kolekce má **skrýt svou strukturu** — a jednou ji možná změnit.
- ✅ Dat je **víc, než se vejde do paměti** (export, import, dávkové zpracování).
- ✅ Data **vznikají za chodu** — čtení po řádcích, stránkované API, stream.
- ✅ Posloupnost je **nekonečná** nebo má neznámou délku.
- ✅ Procházíš **strom** a volající jeho tvar znát nemá.
- ✅ Z tisíce položek potřebuješ prvních deset.

## Kdy nepoužít

- ❌ **Data se vejdou do paměti a projdou se jednou.** Pole je jednodušší a `foreach` nad ním funguje taky.
- ❌ **Volající to potřebuje projít víckrát.** Generátor to neumí; vrať pole.
- ❌ **Potřebuješ počítat, řadit nebo indexovat.** Na to musíš stejně materializovat.
- ❌ **Píšeš `implements Iterator` ručně.** Skoro vždycky stačí `IteratorAggregate` nebo generátor.
- ❌ **Agregace patří do databáze.** `SUM()` v SQL porazí jakýkoli průchod v PHP — viz [Repository](../../../PoEAA/Repository/).

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| Kolekce vydá `toArray()` a volající si dělá `foreach` | Struktura je venku; změna vnitřku rozbije volající | `IteratorAggregate` |
| `Generator` v návratovém typu, když se to prochází víckrát | Druhý průchod skončí výjimkou | Vrať `array`, nebo továrnu na generátor |
| `iterator_to_array()` nad milionem položek | Zrušil jsi celý přínos generátoru | Zpracuj v průchodu |
| Generátor se drží v proměnné a předává dál | Nikdo neví, jestli už byl projitý | Předávej `iterable`, nebo továrnu |
| `count()` nad generátorem | Nefunguje — musí se projít celý | `Countable` na kolekci, nebo `COUNT()` v SQL |
| Filtrování a řazení v PHP nad daty z databáze | [N+1](../../../Glossary.md#n1) a zbytečná paměť | `WHERE` a `ORDER BY` do dotazu |
| `implements Iterator` s pěti metodami | Zbytečná práce a snadné chyby ve stavu | `IteratorAggregate` nebo generátor |
| Generátor s vedlejšími efekty | Kdy se co stane, závisí na tom, kdy se prochází | Generátor jen vydává hodnoty |

---

## V praxi

- **Doctrine `toIterable()`** — průchod výsledkem dotazu bez načtení všeho do paměti. Nezbytné u exportů a migrací.
- **`SplFileObject` a `fgets()`** — čtení souboru po řádcích je iterátor zabudovaný do jazyka.
- **Stránkované API** — generátor, který si sám dotahuje další stránku, je nejčistší způsob, jak z něj udělat obyčejný `foreach`.
- **Symfony Console** — zpracování dávek přes generátor drží paměť konstantní i u milionových importů.
- **`yield from`** — nejjednodušší způsob průchodu stromem v PHP.

---

## Související patterny

| Pattern | Vztah |
| ------- | ----- |
| [First Class Collection](../../../ObjectCalisthenics/FirstClassCollection/) | Implementuje `IteratorAggregate` — to je způsob, jak zůstat kolekcí, aniž by vydala vnitřek. |
| [Composite](../../Structural/Composite/) | Přirozený doplněk: Composite dá stromu tvar, Iterator ho umí projít, aniž by ho volající znal. |
| [Repository](../../../PoEAA/Repository/) | Nad velkými výsledky vrací iterátor, ne pole. Ale agregace patří do databáze. |
| [CQRS](../../../Architecture/CQRS/) | Čtecí strana u velkých exportů stojí na líném průchodu. |
| [Specification](../../../DDD/Specification/) | Filtr, který se předá průchodu — dokud je dat málo. |
| [Decorator](../../Structural/Decorator/) | SPL iterátory (`LimitIterator`, `CallbackFilterIterator`) jsou dekorátory nad iterátorem. |

---

## Vztah k principům

| Princip | Jak souvisí |
| ------- | ----------- |
| [SRP](../../../Principles/SOLID.md#single-responsibility-principle-srp) | Kolekce drží data, iterátor drží pozici v průchodu. Dvě odpovědnosti, dva objekty. |
| [Zákon Demeter](../../../Principles/ObjectDesign.md#zákon-demeter-law-of-demeter) | Volající nesahá do vnitřku kolekce, jen ji projde. |
| [OCP](../../../Principles/SOLID.md#openclosed-principle-ocp) | Nový způsob průchodu = nový iterátor. Kolekce se nemění. |
| [Nízká provázanost](../../../Principles/CohesionAndCoupling.md) | Volající nezávisí na tom, jestli je uvnitř pole, halda nebo kurzor. |

---

## Demo

```bash
php GoF/Behavioral/Iterator/demo/run.php
```

Ukáže kolekci, jejíž strukturu volající nezná, a filtr jako generátor. Pak **změří paměť**: stotisíc položek stojí polem 42 MB a generátorem nula — a tři miliony, které by v poli potřebovaly přes gigabajt, generátor zvládne za tři desetiny sekundy při limitu 128 MB. Následuje nekonečná posloupnost, průchod stromem přes `yield from` a nakonec past, na kterou narazí každý: generátor jde projít jen jednou.

---

## Původ

|               |                                                   |
| ------------- | ------------------------------------------------- |
| **Zdroj**     | *Design Patterns: Elements of Reusable Object-Oriented Software* |
| **Autoři**    | Gamma, Helm, Johnson, Vlissides („Gang of Four“)   |
| **Rok**       | 1994                                              |
| **Kategorie** | Behavioral                                        |
| **Obtížnost** | ●○○○○                                             |

Iterator je z celé knihy vzor, který **jazyky nejúplněji vstřebaly**. V roce 1994 se průchod kolekcí psal ručně a GoF ho popsali proto, že C++ ani Smalltalk neměly jednotný způsob, jak projít seznam, strom a haldu stejným kódem.

Dnes to má zabudované prakticky každý jazyk — v PHP `foreach`, v Javě `for-each`, v Pythonu `for … in`. Ten vzor nezmizel, jen se přesunul **z aplikačního kódu do jazyka**, a to je nejlepší osud, jaký vzor může mít.

Zajímavější je, co přibylo. **Generátory** (PHP 5.5, 2013) přidaly něco, co GoF neřešili: **líné vyhodnocení**. Původní iterátor procházel existující kolekci; generátor hodnoty **vyrábí, až když si o ně někdo řekne**. Tím se z průchodu stala i technika práce s daty, která se do paměti nevejdou — a to je dnes hlavní důvod, proč se o iterátorech v PHP vůbec mluví.

Za povšimnutí stojí i to, že GoF popsali dvě varianty: **externí** iterátor (klient si říká o další prvek) a **interní** (kolekce si průchod řídí sama a volá zpětné volání). `foreach` je externí, `array_map` interní. Obojí se používá dodnes, jen se tomu tak neříká.

---

## Zdroje

- Gamma, Helm, Johnson, Vlissides: *Design Patterns*, Addison-Wesley, 1994 — kapitola 5, str. 257
- [PHP: Generators](https://www.php.net/manual/en/language.generators.overview.php)
- [PHP: IteratorAggregate](https://www.php.net/manual/en/class.iteratoraggregate.php)
- [Doctrine: `toIterable()`](https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/batch-processing.html)

---

<details>
<summary>Metadata patternu</summary>

```yaml
name: Iterator
name_cs: Iterátor
category: Behavioral
source: GoF – Design Patterns
authors: Gamma, Helm, Johnson, Vlissides
year: 1994
difficulty: 1
tags: [průchod, generátory, líné vyhodnocení, paměť, kolekce]
principles: [SRP, LawOfDemeter, OCP, CohesionAndCoupling]
related: [FirstClassCollection, Composite, Repository, CQRS, Specification, Decorator]
status: done
```

</details>
