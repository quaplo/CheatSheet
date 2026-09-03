# Identity Map (Mapa identit)

> [← zpět na PoEAA](../)

> **V jedné větě:** Týž záznam načtený v jedné operaci podruhé vrátí **tutéž instanci**, ne její kopii.

> [!NOTE]
> Tenhle vzor si v PHP skoro nikdy nenapíšeš — **Doctrine ho má uvnitř** a je součástí [Unit of Work](../UnitOfWork/). Znát ho ale musíš, protože vysvětluje tři věci, které jinak vypadají jako magie: proč druhý `find()` nejde do databáze, proč se změna objektu „sama“ objeví na jiném místě kódu, a proč se v dávkovém importu volá `clear()`.

---

## Problém

Jedna operace si potřebuje týž záznam načíst na dvou místech. Bez mapy identit dostane **dva různé objekty se stejnými daty** — a od té chvíle jedou každý po svém.

**Poznáš to podle:**

- změna, kterou jsi prokazatelně udělal, **není v databázi** — a nic nespadlo
- v jednom requestu se objeví dvě instance téhož záznamu s různými hodnotami
- `$a == $b` je `true`, ale `$a === $b` je `false`, a kód se podle toho chová divně
- porovnání entit se dělá přes `->getId()`, protože `===` nefunguje spolehlivě
- táž entita se v jednom requestu načte pětkrát a pětkrát se na ni jde do databáze

```php
// Před: dvě části jedné operace, dva objekty
$forPricing = $repository->find('MON-27');
$forCatalog = $repository->find('MON-27');

$forPricing->changePrice(749000);        // sleva
$forCatalog->rename('Monitor 27" Full HD');

$repository->save($forPricing);
$repository->save($forCatalog);          // ← přepíše cenu zpátky na původní
```

Demo to ukazuje na výstupu:

```
táž instance?          ne
v databázi zůstalo:    Monitor 27" Full HD za 7 990,00 Kč
```

Nové jméno tam je, **sleva zmizela**. Druhý `save()` zapsal celý objekt, který o změně ceny nikdy nevěděl. Nespadlo nic, nezalogovalo se nic — jen je v databázi špatná cena. To je nejhorší druh chyby, jaký existuje.

---

## Řešení

Drž si během operace **rejstřík už načtených objektů**. Než půjdeš do databáze, podívej se do něj.

```php
final class IdentityMap
{
    /** @var array<string, array<string, object>> třída => id => objekt */
    private array $objects = [];

    public function get(string $class, string $id): ?object
    {
        return $this->objects[$class][$id] ?? null;
    }

    public function add(string $class, string $id, object $object): void
    {
        $this->objects[$class][$id] = $object;
    }
}
```

Repository se do ní podívá jako první:

```php
public function find(string $sku): Product
{
    $known = $this->identityMap->get(Product::class, $sku);

    if ($known instanceof Product) {
        return $known;
    }

    $row = $this->storage->fetchRow($sku);
    $product = new Product($sku, $row['name'], $row['price']);

    $this->identityMap->add(Product::class, $sku, $product);

    return $product;
}
```

Táž operace, tentýž kód, jiný výsledek:

```
táž instance?          ano
v databázi zůstalo:    Monitor 27" Full HD za 7 490,00 Kč
dotazů:                1   ← druhý find() do databáze nešel
```

**Nejsou to dvě kopie, které se přepisují — je to jeden objekt, který se změnil dvakrát.**

Úspora dotazů je vidět taky, ale je to **vedlejší efekt, ne důvod**. Důvod je konzistence uvnitř jedné operace.

### Identity Map není cache

Tohle je jediná věc, kterou si z celého dokumentu odnes. Obojí vrací data bez dotazu do databáze, a přesto to jsou různé věci s různými pravidly.

```
                       z cache          z identity map
== (rovnocenné)        ano              ano
=== (táž instance)     ne               ano
vidí pozdější změnu    ne               ano
```

| | **Cache** | **Identity Map** |
| --- | --- | --- |
| Řeší | Rychlost | **Identitu** |
| Smí vrátit kopii | Ano | **Ne, nikdy** |
| Kde žije | Redis, APCu, soubor — přes requesty | **Paměť procesu, jeden request** |
| Sdílená mezi uživateli | Běžně ano | **Nikdy** |
| Zastaralá data | Očekávaný kompromis, řeší se TTL | Chyba, řeší se koncem operace |
| Když ji vypneš | Aplikace je pomalejší | **Aplikace tiše rozbíjí data** |

Praktický důsledek: **Identity Map se nikdy neukládá do Redisu.** Serializace z ní udělá kopii a tím zmizí to jediné, kvůli čemu existuje. Kdyby to šlo, byla by to cache.

Druhý důsledek: cache je optimalizace, kterou lze vypnout. Identity Map ne.

### Jak dlouho má mapa žít

Nejtěžší rozhodnutí celého vzoru. Mapa **záměrně vrací to, co si pamatuje** — a to je uvnitř operace správně a mimo ni špatně:

```
načteno:               2 490,00 Kč
v databázi je teď:     1 790,00 Kč     ← někdo mezitím změnil cenu
mapa vrací pořád:      2 490,00 Kč     ← zastaralé
po clear():            1 790,00 Kč
```

| Rozsah | Vhodné? | Proč |
| ------ | ------- | ---- |
| **Jedna transakce / use-case** | ✅ Výchozí volba | Přesně tam, kde konzistenci potřebuješ |
| Jeden HTTP request | ✅ Běžné | Co Doctrine dělá, když entity manager nevyčistíš |
| Dávka mezi dvěma `clear()` | ✅ Nutné u importů | Jinak dojde paměť |
| **Napříč requesty** | ❌ Nikdy | To už je cache a chová se jinak |
| **Globální / statická** | ❌ Nikdy | Viz [Singleton](../../GoF/Creational/Singleton/) — a v dlouhoběžících procesech i únik paměti |

Uvnitř jedné operace chceš **konzistentní pohled**: kdyby ti dva `find()` v půlce use-case vrátily různá data, nešlo by o operaci rozumně uvažovat. Přes hranici operace je totéž chyba.

### Dávky: proč se v cyklu volá `clear()`

Mapa drží referenci na každý načtený objekt. Garbage collector ho proto **nemůže uklidit** — a v dlouhém importu to není otázka optimalizace:

```
                           paměť        objektů v mapě
bez clear()                22,8 MB      50 000
clear() po 1 000           0,0 MB       0
```

<sub>PHP 8.5, 50 000 položek. Jde o řád, ne o absolutní čísla.</sub>

Přesně tohle je důvod, proč se v dávkovém zpracování v Doctrine píše:

```php
foreach ($query->toIterable() as $i => $product) {
    // …zpracuj…

    if ($i % 1000 === 0) {
        $entityManager->flush();
        $entityManager->clear();     // ← vyprázdnění identity map
    }
}
```

Bez `clear()` doběhne import do konce jen náhodou. **A pozor:** po `clear()` jsou všechny dosud načtené entity **odpojené** (detached) — Doctrine je přestane sledovat, změny na nich se neuloží a `===` s nově načtenou instancí už neplatí. Proto se `clear()` volá až po `flush()`, ne před ním.

---

## Účastníci

| Role | V příkladu | Odpovědnost |
| ---- | ---------- | ----------- |
| **Identity Map** | `IdentityMap` | Rejstřík `třída → id → objekt` pro dobu operace |
| **Klíč** | `Product::class` + `sku` | Identita záznamu; typ musí být součástí klíče |
| **Načítač** | `ProductRepository` | Podívá se do mapy dřív, než jde do databáze |
| **Entita** | `Product` | Má identitu a mění se; **o mapě neví** |

Že entita o mapě neví, není detail. Identity Map je věc persistenční vrstvy — jakmile se o ní doména dozví, přestává být doménou.

---

## Implementace v PHP

### Klíč musí obsahovat typ

```php
// Špatně: Product s ID 1 a Customer s ID 1 se přepíšou
private array $objects = [];
$this->objects[$id] = $object;

// Správně
$this->objects[$class][$id] = $object;
```

Sdílený jmenný prostor ID napříč třídami je chyba, která se projeví až v produkci a vypadá nevysvětlitelně. Pokud používáš [value objecty jako identifikátory](../../DDD/ValueObject/) (`OrderId`, `Sku`), převeď je na řetězec — pole v PHP se indexuje jen `int` a `string`.

### Objekt do mapy patří hned, ne až na konci

```php
$product = new Product($sku, $row['name'], $row['price']);

$this->identityMap->add(Product::class, $sku, $product);   // ← hned

$product->setCategory($this->categoryRepository->find($row['category']));
```

Kdyby se objekt do mapy vložil až po načtení vazeb, **cyklická vazba** (produkt → kategorie → produkt) skončí nekonečnou rekurzí. Doctrine to řeší stejně.

### Kdy si ji psát sám

Skoro nikdy. Ale existují dva případy:

| Situace | Řeší to |
| ------- | ------- |
| Používáš ORM | **Nic nepiš**, máš ji |
| Vlastní repository nad PDO a entity, které se v operaci mění | Vlastní mapa, ~20 řádků |
| Čtecí strana, [read modely](../../Architecture/CQRS/) | **Nepotřebuješ ji** — nic se nemění |
| Cache mezi requesty | Chceš cache, ne tohle |

### Value objecty do mapy nepatří

[Value object](../../DDD/ValueObject/) nemá identitu — dvě stokoruny jsou zaměnitelné a nikdo se neptá, jestli jsou to „tytéž“ peníze. Mapa identit by u nich neměla co mapovat.

To je zároveň užitečný test: **pokud u objektu nedokážeš říct, co je jeho identita, mapa identit pro něj nedává smysl** — a nejspíš to není [entita](../../DDD/Entity/).

---

## Kdy použít

- ✅ **Píšeš vlastní persistenční vrstvu** nad PDO a entity se během operace mění.
- ✅ **Jedna operace načítá týž záznam z víc míst** — a všechna musí vidět totéž.
- ✅ **Máš ORM** — pak už ji používáš, jen o tom víš.

## Kdy nepoužít

- ❌ **Máš Doctrine a chystáš se psát vlastní.** Máš ji uvnitř; druhá vrstva nad ní jen rozbije tu původní.
- ❌ **Chceš zrychlit aplikaci.** To je cache, jiný vzor s jinými pravidly.
- ❌ **Chceš ji sdílet mezi requesty nebo uživateli.** Viz výše — a v tomhle případě i bezpečnostní problém.
- ❌ **Čteš data jen pro zobrazení.** Read model se nemění, takže není co udržovat konzistentní.
- ❌ **Jsi v dlouhoběžícím procesu bez vyprazdňování.** Bez `clear()` je to únik paměti, ne vzor.

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| Mapa přežije request | Zastaralá data napříč operacemi | Život mapy = život transakce |
| Mapa je statická nebo globální | Únik paměti a data jednoho uživatele u druhého | Instance předaná do repository |
| Klíč bez názvu třídy | `Product` 1 a `Customer` 1 se přepíšou | `[$class][$id]` |
| Mapa se serializuje do Redisu | Z instance je kopie — vzor přestal fungovat | Identity Map žije jen v paměti |
| V dávce chybí `clear()` | Paměť roste, dokud proces nespadne | `flush()` a pak `clear()` po N položkách |
| `clear()` před `flush()` | Neuložené změny se zahodí bez varování | Nejdřív `flush()` |
| Vlastní mapa nad Doctrine | Dvě mapy, dvě pravdy, nedohledatelné chyby | Spolehni se na entity manager |
| Objekt se do mapy vloží až po načtení vazeb | Cyklická vazba = nekonečná rekurze | Vložit hned po vytvoření |
| Porovnávání entit přes `getId()` všude | Obcházení symptomu; mapa má zaručit, že stačí `===` | Zprovoznit mapu |
| Value objecty v mapě | Nemají identitu, není co mapovat | Do mapy jen entity |

---

## V praxi

- **Doctrine** — identity map je součást entity manageru. Proto `find()` na týž záznam vrátí tutéž instanci a proto existuje `clear()`.
- **`$entityManager->clear()` v importech** — standardní postup u dávek, obvykle po tisíci položkách spolu s `flush()`.
- **Odpojené (detached) entity** — stav po `clear()`, kdy objekt existuje, ale ORM ho už nesleduje. Nejčastější zdroj „proč se to neuložilo“.
- **Eloquent (Active Record) identity map nemá** — dvě volání `Product::find(1)` vrátí dvě instance. Kdo přechází mezi frameworky, tohle je jeden z reálných rozdílů.
- **`spl_object_id()`** — způsob, jak si v PHP ověřit, jestli jde opravdu o tentýž objekt.

---

## Související patterny

| Pattern | Vztah |
| ------- | ----- |
| [Unit of Work](../UnitOfWork/) | **Nejtěsnější vazba.** Identity Map je jeho paměť: aby šlo porovnávat se snímky, musí být jistota, že jde o jeden objekt, ne o dvě kopie. |
| [Data Mapper](../DataMapper/) | Mapper vyrábí objekty z řádků. Bez mapy identit by z téhož řádku vyrobil pokaždé nový. |
| [Repository](../Repository/) | Místo, kde se do mapy sahá — dřív než do databáze. |
| [Entity](../../DDD/Entity/) | Mapa dává smysl jen u objektů **s identitou**. Čím se identita liší od rovnosti, je popsané tam. |
| [Value Object](../../DDD/ValueObject/) | Protipól: bez identity není co mapovat. |
| [Optimistic Offline Lock](../OptimisticOfflineLock/) | Řeší souběh **mezi** requesty; Identity Map konzistenci **uvnitř** jednoho. |
| [Aggregate](../../DDD/Aggregate/) | Určuje, co se načítá jako celek — a tím i co v mapě obvykle je. |
| [CQRS](../../Architecture/CQRS/) | Čtecí strana mapu nepotřebuje: read modely se nemění. |
| [Singleton](../../GoF/Creational/Singleton/) | **Lákadlo, kterému neustupuj.** Globální mapa vypadá pohodlně a znamená únik paměti a data mezi uživateli. |
| **Lazy Load** (PoEAA) | Odložené načtení vazeb; spolu s mapou zabraňuje tomu, aby jeden objekt vznikl vícekrát. |
| [Active Record](../ActiveRecord/) (PoEAA) | Mapu **nemá** — dvě volání `find(1)` vrátí dvě instance. Není tu vrstva, kam by patřila. |

---

## Vztah k principům

| Princip | Jak souvisí |
| ------- | ----------- |
| [SRP](../../Principles/SOLID.md#single-responsibility-principle-srp) | Mapa dělá jednu věc: pamatuje si, co už je načtené. Nerozhoduje o načítání ani o zápisu. |
| [Zviditelni implicitní](../../Principles/ObjectDesign.md#zviditelni-implicitní) | Bez mapy je předpoklad „tohle je pořád tentýž produkt“ nevyslovený a občas neplatí. Mapa z něj dělá pravidlo. |
| [Nízká provázanost](../../Principles/CohesionAndCoupling.md#stupnice-provázanosti) | Doména o mapě neví — je to výhradně věc persistenční vrstvy. |
| [KISS](../../Principles/Simplicity.md#kiss--keep-it-simple) | Samotná mapa je asociativní pole. Složitost není v ní, ale v rozhodnutí, jak dlouho žije. |

---

## Demo

```bash
php PoEAA/IdentityMap/demo/run.php
```

Pět částí. Nejdřív tichá ztráta dat bez mapy — dvě instance téhož produktu, druhý `save()` přepíše slevu a nic se nezaloguje. Pak totéž s mapou: jedna instance, obě změny uložené, o dotaz míň. Třetí část staví vedle sebe kopii z cache a instanci z mapy a ukazuje, že `==` platí u obou, ale `===` a viditelnost pozdější změny jen u mapy. Čtvrtá ukazuje, kdy začne mapa vracet zastaralá data a co s tím dělá `clear()`. Poslední **měří paměť** na padesáti tisících položkách: bez vyprazdňování 22,8 MB a padesát tisíc objektů, s `clear()` po tisíci nula.

---

## Původ

|               |                                                   |
| ------------- | ------------------------------------------------- |
| **Zdroj**     | *Patterns of Enterprise Application Architecture*  |
| **Autor**     | Martin Fowler                                     |
| **Rok**       | 2002                                              |
| **Kategorie** | Object-Relational Behavioral Patterns             |
| **Obtížnost** | ●●○○○                                             |

Fowler zařadil Identity Map mezi vzory chování objektově-relačního mapování — vedle [Unit of Work](../UnitOfWork/) a Lazy Load. Motivace byla praktická: v aplikacích, které tehdy psal, se opakovaně objevovala chyba, kdy dvě části jedné transakce pracovaly s různými kopiemi téhož záznamu a jedna změna tiše zmizela.

Obtížnost je dvojka, i když **samotná třída je asociativní pole**. Cena není v napsání, ale v životním cyklu: mapa, která žije o něco déle, než má, vrací zastaralá data; mapa, která se nevyprazdňuje, sežere paměť; a mapa vedle té, kterou už má ORM, vyrobí dvě pravdy. Tohle jsou přesně chyby, které se dělají tiše.

Dnes je vzor **zabudovaný** — v Doctrine, Hibernate i Entity Framework. Za zmínku stojí, že [Active Record](../ActiveRecord/) ho typicky nemá: v Eloquentu vrátí dvě volání `find(1)` dvě instance. Není to nedopatření, ale důsledek toho, že Active Record nemá vrstvu, kam by mapa patřila.

---

## Zdroje

- Martin Fowler: *Patterns of Enterprise Application Architecture*, Addison-Wesley, 2002 — str. 195
- [martinfowler.com: Identity Map](https://martinfowler.com/eaaCatalog/identityMap.html)
- [Doctrine: dávkové zpracování](https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/batch-processing.html)

---

<details>
<summary>Metadata patternu</summary>

```yaml
name: Identity Map
name_cs: Mapa identit
category: Object-Relational Behavioral
source: PoEAA – Patterns of Enterprise Application Architecture
authors: Martin Fowler
year: 2002
difficulty: 2
tags: [identita, persistence, ORM, paměť, konzistence]
principles: [SRP, MakeImplicitExplicit, CohesionAndCoupling, KISS]
related: [UnitOfWork, DataMapper, Repository, Entity, ValueObject, OptimisticOfflineLock, Aggregate, CQRS, Singleton, LazyLoad]
status: done
```

</details>
