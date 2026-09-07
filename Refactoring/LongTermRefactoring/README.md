# Dlouhodobý refaktoring

> [← zpět na Refaktoring](../)

> **V jedné větě:** Přestavba na měsíce, která se dělá **v hlavní větvi během běžné práce** — tým se dohodne, kam to má dojít, a každý úkol se cestou o kousek přiblíží.

Fowler ho popisuje jako sedmé a poslední workflow:

> „Some restructuring requires bigger changes than can be done in a single development episode — **spanning multiple iterations over several months**. Examples might include replacing a large module, changing your database persistence framework, or untangling some dependencies."
>
> — Martin Fowler, *Workflows of Refactoring*, 2014

A hned k tomu dodává, jak se to dělá, aniž by se vývoj zastavil:

> „The team needs to agree on **a rough end-state as well as a rough plan** to get there. Then **during their regular work** they take the opportunity to carry out refactorings that move the architecture towards the desired direction. **Since all changes are refactorings, the code base can remain in a working state even as features are added.**"

Ta poslední věta je celý ten postup. Není to sliby — je to podmínka, ze které plyne všechno ostatní.

---

## Kdy po tom sáhnout

Spouštěč je ten, u kterého [plánovaný refaktoring](../PlannedRefactoring/) přestává stačit: **práce se nevejde do jedné story a nedá se udělat najednou.**

**Poznáš to podle:**

- odhad zní „tři měsíce" a nikdo tomu nevěří
- změna se dotkne desítek souborů, které jinak spolu nesouvisejí
- „to se musí udělat celé naráz, jinak to nebude fungovat" — a přitom to naráz nejde
- už jednou se to zkusilo a skončilo to na větvi, kterou nikdo nesloučil

Fowlerovy vlastní příklady: **výměna velkého modulu, změna perzistenční vrstvy, rozplétání závislostí.** Všechny tři mají společné, že se týkají něčeho, co používá celý zbytek kódu.

---

## Dvě věci, na kterých se tým musí dohodnout

Fowler jmenuje obě a obě jsou „rough" — hrubé. To není slabina, to je záměr.

**1. Hrubý cílový stav.** Jedna věta, které rozumí každý: *„přístup k datům jde přes `OrderRepository`, `LegacyDb` už nikdo nevolá."* Ne diagram na dvacet stran. Musí se podle ní dát rozhodnout u konkrétního souboru, jestli je krok správným směrem.

**2. Hrubý plán, jak se tam dostat.** Ne harmonogram — pořadí. Co musí být hotové dřív, aby zbytek šel po kouskách.

> [!IMPORTANT]
> Bez dohodnutého cíle to není dlouhodobý refaktoring, ale **tři nedokončené migrace vedle sebe**. Každý uklízí správným směrem podle sebe a po roce má kód tři konkurenční abstrakce místo jedné.

Čím to je, že hrubý cíl stačí? Protože se podle něj nerozhoduje, **co** se udělá — to určí úkoly, které zrovna přijdou. Rozhoduje se podle něj jen **kterým směrem** se při nich uklidí.

---

## Jak to vypadá

Cíl: přístup k datům přes `OrderRepository` místo statického `LegacyDb`. Práce na měsíce, mezitím se dodávají funkce. Demo to udělá dvakrát.

### Cesta A: přestavba na vlastní větvi

Zní to rozumně — velká změna, vlastní větev, ať to nikomu nepřekáží. Mezitím na hlavní větvi přibude report.

```
git merge                       proběhl bez konfliktu
kontrola po sloučení            SPADLA

  ArgumentCountError: Too few arguments to function OrderService::__construct(),
  0 passed in OrderReport.php on line 9 and exactly 1 expected
```

**Git nenašel jediný konflikt.** Každá strana sáhla na jiný soubor: `OrderReport.php` vznikl na hlavní větvi a volá `new OrderService()`, zatímco na větvi konstruktor mezitím dostal parametr. Konflikt je **významový** — a ten `git merge` nevidí.

To je na dlouhodobém refaktoringu na větvi to nejhorší. Nedostaneš varování. Dostaneš zelené sloučení a rozbitou aplikaci.

### Cesta B: v hlavní větvi, po krocích

Táž práce, jen rozdělená a proložená funkcemi, které mezitím přišly:

```
commit                                              druh           kontrola
výchozí stav                                        výchozí        prošla
funkce: report a počet aktivních objednávek         funkce         prošla
refaktoring: přístup k datům přes OrderRepository   refaktoring    prošla
funkce: největší aktivní objednávka                 funkce         prošla
```

Sada nebyla červená ani jednou. Když se v jednom commitu změní konstruktor, **ve stejném commitu se opraví i volající** — protože jsou vedle sebe v jednom pracovním adresáři, ne v jiné větvi.

A poslední funkce se už psala rovnou proti novému tvaru. To je ten skrytý zisk: **čím dřív cíl v hlavní větvi existuje, tím míň nového kódu vzniká ve starém tvaru.**

### Vedle sebe

```
                                cesta A (větev)             cesta B (hlavní větev)
kdy má hlavní větev užitek      až po sloučení              hned po každém kroku
konflikty, které git ohlásil    0                           0 — není co slučovat
kód po sloučení                 rozbitý                     funkční v každém commitu
kdy se chyba pozná              za měsíce, při slučování    v commitu, kde vznikla
```

Druhý řádek je to nejnebezpečnější číslo. **Nula konfliktů neznamená, že je to v pořádku** — znamená to, že se nikdo nemusel na nic podívat.

---

## Čím se to dělá

Postup nemá vlastní mechaniku. Používá techniky, které v katalogu už jsou, a jeho přínos je v tom, **kdy a v jakém pořadí** se sáhne po které:

| Technika | K čemu v dlouhodobé přestavbě |
| -------- | ----------------------------- |
| [Branch by Abstraction](../System/BranchByAbstraction/) | To, co Fowler jmenuje přímo: vrstva, pod kterou žije stará i nová implementace zároveň. |
| [Strangler Fig](../System/StranglerFig/) | Když se nahrazuje celý modul zvenčí, ne jeho vnitřek. |
| [Expand–Contract](../System/ExpandContract/) | Změna rozhraní, které používá i někdo mimo tvůj kód. |
| [Parallel Run](../System/ParallelRun/) | Ověření, že nová implementace počítá totéž, dřív než se přepne. |
| [Refaktoring kódu](../Code/) | Jednotlivé kroky uvnitř každé z nich. |

Fowler zmiňuje jednu z nich jmenovitě:

> „One common technique for long-term refactoring is **Branch By Abstraction**, where you use an abstraction layer that supports the current and a replacement implementation."

Ta se jmenuje „branch", ale žádná větev v ní není — a to je přesně pointa. **Větvení se odehrává v kódu, ne v gitu.**

---

## Plánovaný, nebo dlouhodobý?

Obojí ví tým a obojí je velké. Rozdíl je v tom, **odkud se bere čas** — a z toho plyne všechno ostatní:

| | [Plánovaný](../PlannedRefactoring/) | Dlouhodobý |
| --- | --- | --- |
| Trvá | jednu story | *„multiple iterations over several months"* |
| Odkud čas | **vyhrazený** — položka v plánu | **z běžné práce** — po kouskách při jiných úkolech |
| Co tým odsouhlasí | rozsah story | **hrubý cíl a hrubý plán** |
| Když přijde něco naléhavějšího | vyhrazený čas se škrtne | nemá se co škrtnout |
| Když se to přeruší | zbude půl migrace | zbude abstrakce, která funguje dál |

Předposlední řádek je praktický důvod, proč Fowler u dlouhodobého refaktoringu trvá na „during their regular work". **Co není samostatná položka v plánu, nedá se z plánu vyškrtnout.**

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| Dlouhá větev | Významové konflikty, které git neohlásí | V hlavní větvi, po krocích |
| Není dohodnutý cíl | Po roce jsou tři konkurenční abstrakce | Jedna věta, které rozumí každý |
| Cíl je podrobný návrh | Za tři měsíce neplatí a nikdo ho nečte | Hrubý cíl, hrubý plán |
| Nový kód se dál píše ve starém tvaru | Přestavba nikdy nedožene přírůstek | Cíl musí být v hlavní větvi co nejdřív |
| Kroky mění chování | Přestává platit „remains in a working state" | [Dva klobouky](../TwoHats/) ve velkém |
| „Až domigrujeme, smažeme staré" | Nikdo to neudělá; obojí zůstane navždy | Smazání starého je poslední krok plánu, ne přání |
| Nikdo neví, jak daleko to je | Práce se ztratí a začne se znovu | Měřit, kolik volání ještě chodí po staré cestě |

Poslední řádek stojí za rozvedení. Dlouhodobý refaktoring je jediný, u kterého **postup není vidět** — v každém jednotlivém commitu vypadá jako drobnost. Číslo typu „ještě 34 volání `LegacyDb`" je proto to jediné, co udrží směr napříč měsíci a lidmi. Přesně tak to měří i demo u [plánovaného refaktoringu](../PlannedRefactoring/).

---

## Demo

```bash
php Refactoring/LongTermRefactoring/demo/run.php
```

Táž přestavba udělaná dvakrát ve skutečných git repozitářích: jednou na odbočené větvi, jednou v hlavní větvi po krocích. Demo obě historie postaví, u každého commitu spustí kontrolu chování a nakonec větev sloučí.

Výsledek cesty A je ta nejcennější část:

```
git merge                       proběhl bez konfliktu
kontrola po sloučení            SPADLA
```

**Nula konfliktů a rozbitý kód** — `OrderReport.php` z hlavní větve volá `new OrderService()`, kterému mezitím na větvi přibyl parametr konstruktoru. Cesta B projde kontrolou ve všech čtyřech commitech.

Dočasné repozitáře demo po sobě uklidí a dva běhy dávají totožný výstup.

---

## Související

| Dokument | Vztah |
| -------- | ----- |
| [Mikado metoda](../MikadoMethod/) | Jak najít cestu k dohodnutému cíli, když se neví kudy. |
| [Refaktoring systému](../System/) | Techniky, ze kterých se dlouhodobá přestavba skládá. |
| [Branch by Abstraction](../System/BranchByAbstraction/) | Ta, kterou Fowler jmenuje přímo. |
| [Plánovaný refaktoring](../PlannedRefactoring/) | Menší sourozenec: vyhrazený čas místo běžné práce. |
| [Dva klobouky](../TwoHats/) | Pravidlo, na kterém stojí „remains in a working state". |
| [Charakterizační testy](../CharacterizationTests/) | Síť, bez které se do starého modulu sahat nedá. |
| [Litter-pickup](../LitterPickupRefactoring/) | Jak se cíl prosazuje v každodenní práci, úkol po úkolu. |
| [Conway's law](../../SoftwareDesign/Principles/ConwaysLaw.md) | Proč se rozplétání závislostí často zadrhne na tom, kdo s kým mluví. |
| [Code review: autor](../../Processes/CodeReview/Author/) | Jak dát recenzentovi vědět, že tenhle commit je krok dlouhodobé přestavby. |

---

## Původ

|             |                    |
| ----------- | ------------------ |
| **Autor**   | Martin Fowler      |
| **Rok**     | 2014               |
| **Zdroj**   | *Workflows of Refactoring* |
| **Náročnost** | ●●●●○            |

Dlouhodobý refaktoring je **sedmé a poslední** ze sedmi workflow, která Fowler popsal v infodecku z 8. ledna 2014. Je z nich jediné, u kterého se mluví o měsících, a jediné, u kterého Fowler jmenuje konkrétní techniku — [Branch by Abstraction](../System/BranchByAbstraction/).

Čtyřka na náročnosti není za mechaniku. Jednotlivé kroky jsou obyčejné refaktoringy a každý z nich je snadný. Těžké je udržet tři věci po dobu, po kterou se v týmu vymění lidé:

- **Směr.** Cíl musí být tak jednoduchý, aby se dal po půl roce zopakovat zpaměti.
- **Kázeň každého kroku.** Jeden commit, který mění chování, ruší záruku, že hlavní větev pořád funguje.
- **Doprovod novému kódu.** Dokud se nové věci píšou ve starém tvaru, přestavba nedohání, ale zaostává.

---

## Zdroje

- Martin Fowler: [*Workflows of Refactoring*](https://martinfowler.com/articles/workflowsOfRefactoring/), 8. ledna 2014 — [celý text na jedné stránce](https://martinfowler.com/articles/workflowsOfRefactoring/fallback.html)
- Martin Fowler: [*Branch By Abstraction*](https://martinfowler.com/bliki/BranchByAbstraction.html)
- Martin Fowler: [*Semantic Conflict*](https://martinfowler.com/bliki/SemanticConflict.html)

---

<details>
<summary>Metadata techniky</summary>

```yaml
name: Dlouhodobý refaktoring
level: příprava
author: Martin Fowler
year: 2014
duration: měsíce, napříč iteracemi
reversible: ano — každý krok je refaktoring
requires_tests: ano
difficulty: 4
tags: [workflow, dlouhodobá přestavba, hlavní větev, sémantický konflikt, Branch by Abstraction]
leads_to: [BranchByAbstraction, StranglerFig]
related: [PlannedRefactoring, TwoHats, CharacterizationTests, System]
status: done
```

</details>
