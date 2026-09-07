# Parallel Run

> [← zpět na Refaktoring systému](../)

> **V jedné větě:** Pusť starou i novou implementaci na tentýž skutečný požadavek, porovnej výsledky — a zákazníkovi vrať **vždycky ten starý**.

> [!IMPORTANT]
> **Nová implementace nikdy neodpovídá.** Tím se technika liší od všeho ostatního: nespouští se proto, aby začala sloužit, ale proto, aby se **ověřila na skutečném provozu**, dokud ještě nic neriskuješ. [Rozdíl proti canary release a dark launchingu](#parallel-run-canary-a-dark-launching) je níž.

---

## Kdy po tom sáhnout

Máš novou implementaci, testy prochází — a přesto si netroufáš ji zapnout.

**Poznáš to podle:**

- stará implementace je **jediná specifikace**, co existuje
- nikdo neví, **jestli se nová chová stejně** ve všech případech
- jde o **peníze, ceny nebo fakturaci** a chyba je vidět hned
- testy pokrývají, na co si někdo vzpomněl — a **starý kód dělá i něco jiného**
- „mělo by to být stejné" zaznělo a **nikdo to nedokáže potvrdit**
- chystáš [Branch by Abstraction](../BranchByAbstraction/) nebo [Strangler Fig](../StranglerFig/) a **chybí krok mezi „hotovo" a „přepnuto"**

---

## Předtím

Nová implementace existuje a je otestovaná. Otázka zní: **stačí to?**

```php
// Testy prochází. Ale co dělá stará verze na těch vstupech,
// na které si nikdo nevzpomněl?
$discount = $newCalculator->discountInCents($order);
```

Jednotkové testy odpovídají na otázku „dělá to, co jsme napsali". Parallel Run odpovídá na tu podstatnější: **„dělá to, co dělá ta stará?"**

---

## Mechanika

Terminologie pochází z knihovny **[Scientist](https://github.com/github/scientist)** od GitHubu a ujala se i mimo ni:

| Pojem | Co to je |
| ----- | -------- |
| **Control** (kontrola) | Ověřená implementace. **Její výsledek se vrací.** |
| **Candidate** (kandidát) | Nová implementace, která se zkouší |
| **Experiment** | Obal, který pustí obojí, porovná a reportuje |
| **Publish** | Co se stane s pozorováním — log, metrika, dashboard |

### 1. Obal volání experimentem

```php
$experiment = new Experiment(
    control: new LegacyDiscount(),
    candidate: new NewDiscount(),
);

$discount = $experiment->run($order);
```

**Po tomhle kroku platí:** volající se nezměnil a dostává totéž co dřív.

### 2. Pusť obojí a vrať kontrolu

```
kontrola sama             256,00 Kč
kandidát sám              256,00 Kč
experiment vrátil         256,00 Kč   ← co dostane zákazník
```

Tři vlastnosti, na kterých technika stojí a bez kterých nefunguje:

- **Vrací se vždycky kontrola** — ať kandidát vrátí cokoli
- **Výjimka v kandidátovi se zachytí** a nikam nepropadne
- **Pořadí běhu se střídá** — kdyby jedna větev zahřívala cache pro druhou, vyšla by ta druhá vždycky rychlejší a nikdo by to nepoznal

**Po tomhle kroku platí:** nová implementace běží na produkci a nemůže nic rozbít.

### 3. Sbírej neshody

```
objednávek                500
neshod                    215 (43.0 %)

objednávka      kontrola        kandidát        rozdíl
2026/0000       75,00 Kč        100,00 Kč       25,00 Kč
2026/0003       15,60 Kč        15,59 Kč        0,01 Kč
```

**Po tomhle kroku platí:** víš, že se implementace liší — a v čem.

### 4. Rozliš šum od chyby

Tohle je práce pro člověka a nedá se automatizovat. Většina rozdílů bývá nezajímavá:

```
bez tolerance             215
s tolerancí 1 Kč          1

zbyl 1 skutečný rozdíl z 215
2026/0000       75,00 Kč        100,00 Kč       25,00 Kč
```

Dvě stě čtrnáct haléřových rozdílů je jiné zaokrouhlení — **rozhodnutí, ne chyba**, a udělat ho má byznys.

Zbývá jediný případ z pěti set: malá objednávka s kupónem, kde se strop uplatní jinak. **Právě takové případy jsou důvod, proč technika existuje** — v jednotkových testech na něj nikdo nepomyslel, protože o tom pravidle nikdo nevěděl.

**Po tomhle kroku platí:** rozdíly jsou buď vysvětlené, nebo opravené.

### 5. Vypni experiment a přepni

Když je shoda dostatečná a rozdíly vysvětlené, kandidát se stane kontrolou. Přepnutí samotné už je [Branch by Abstraction](../BranchByAbstraction/) nebo [Strangler Fig](../StranglerFig/).

> [!NOTE]
> **Kde se dá zastavit:** kdykoli. Experiment jde vypnout jedním přepínačem a nic po něm nezbude — je to **nejbezpečnější technika z celé sekce**. Cena je jen výkon a čas, který stojí rozbor neshod.

---

## Průběh a návratová cesta

| | |
| --- | --- |
| **Jak dlouho to trvá** | Dny až týdny — potřebuješ pokrýt i vzácné případy |
| **Co vidí uživatel** | Nic. Odpovídá pořád stará implementace |
| **Jak se vrátit** | Vypnout experiment; nic se nemění |
| **Jak dlouho žije mezistav** | Do vyjasnění rozdílů, pak se experiment odstraní |

**Návratová cesta je triviální, protože se nikdy nic nepřepnulo.** To je celý smysl techniky a taky důvod, proč se dá pustit i tam, kde by ostatní byly moc riskantní.

Jediné, co může selhat, je **výkon** — a na to se vzorkuje.

---

## Jak ověřit, že to funguje

Paradoxně: **tahle technika sama je způsob ověření.** Co je potřeba ohlídat u ní samotné:

- **Porovnávat rozumně.** Přesná rovnost bývá moc přísná — haléřové zaokrouhlení, pořadí v poli, časové razítko. Bez tolerance utopíš skutečné rozdíly v šumu.
- **Nedělat vedlejší efekty dvakrát.** Kandidát nesmí zapisovat do databáze, posílat e-maily ani volat platební bránu. Když to jinak nejde, technika nesedí.
- **Neporovnávat nedeterministické věci** — čas, náhodná čísla, ID. Buď je vyjmi z porovnání, nebo je předej oběma stejně.
- **Měřit i výkon**, ne jen výsledky. Kandidát, který je správný a desetkrát pomalejší, není hotový.

---

## Co to stojí

```
kontrola celkem           0.96 ms
kandidát celkem           0.94 ms
celková režie             1.0× práce navíc
```

| Cena | Kdy se vyplatí |
| ---- | -------------- |
| **Dvojnásobek práce** na každý požadavek | Když je jistota cennější než výkon |
| **Rozbor neshod** — člověk musí projít rozdíly | Když jsou v sázce peníze nebo data |
| **Kód navíc**, který se pak maže | Když se experiment vyhodnotí a odstraní |
| **Nejde použít u zápisů** bez další práce | Když se ověřuje čtení nebo výpočet |

U výpočtu v paměti je zdvojení práce jedno. **U volání do databáze nebo na cizí službu se tím zdvojnásobí zátěž** — a od nějaké úrovně provozu to není únosné.

### Vzorkování

Proto se kandidát nepouští na všechno:

```
vzorek        kandidát běžel      nalezených neshod
1 %           5x                  2
10 %          54x                 19
50 %          245x                107
100 %         500x                214
```

**I jednoprocentní vzorek najde, že se implementace liší.** Na potvrzení, že se **ne**liší, je ho ale málo — a to je ten rozdíl, na kterém technika stojí. Vzorek se proto v čase zvyšuje: začne se na procentu, aby se odhalily zjevné rozdíly, a končí se na vysokém podílu, aby se zachytily i vzácné případy.

Vzorkování musí být **deterministické** — táž objednávka buď do experimentu patří, nebo ne. Jinak se nedá dohledat, proč se rozdíl objevil zrovna tam.

---

## Parallel Run, canary a dark launching

Tři techniky, které se pletou, protože všechny „zkoušejí nové na produkci". Liší se v jediné otázce: **kdo dostane odpověď z nové implementace.**

| | **Parallel Run** | **Canary release** | **Dark launching** |
| --- | --- | --- | --- |
| Nová běží | **ano, na každý vzorek** | ano | ano |
| Nová **odpovídá** | **nikdy** | části uživatelů | nikomu |
| Porovnává se se starou | **ano, to je smysl** | ne | ne |
| Co ověřuje | **shodu chování** | dopad na provoz | že to nespadne |
| Riziko pro zákazníka | **žádné** | omezené na část | žádné |

**Parallel Run je jediný z nich, který odpovídá na otázku „chová se to stejně?"** Ostatní dva odpovídají na „vydrží to?".

V praxi jdou za sebou: nejdřív parallel run (ověření shody), pak canary (postupné přepínání — [Branch by Abstraction](../BranchByAbstraction/)), nakonec plné přepnutí.

---

## Kdy to nedělat

- ❌ **Kandidát má vedlejší efekty.** Dvakrát odeslaný e-mail nebo dvakrát stržená platba jsou horší než neověřená implementace.
- ❌ **Nová verze se má chovat jinak.** Když je změna chování záměrem, není co porovnávat — rozdíly budou všude a nic neřeknou.
- ❌ **Zdvojení zátěže je neúnosné.** U drahých volání ani vzorkování nemusí stačit.
- ❌ **Výsledek se nedá porovnat.** Nedeterministický výstup, obrázek, dlouhý text — porovnání pak stojí víc než užitek.
- ❌ **Nemá kdo rozdíly projít.** Technika vyrobí seznam neshod; když ho nikdo nečte, byla to práce nazmar.

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| Kandidát může ovlivnit odpověď | Přestala to být bezpečná technika | Vrací se **vždy** kontrola |
| Výjimka v kandidátovi propadne ven | Nový kód shodí produkci — přesně to, čemu se vyhýbáš | Zachytit a zaznamenat |
| Kandidát zapisuje nebo volá ven | Vedlejší efekty dvakrát | Jen čtení a výpočty |
| Přesné porovnání bez tolerance | Skutečné rozdíly se utopí v haléřovém šumu | Tolerance podle typu dat |
| Pořadí běhu je pevné | Jedna větev zahřeje cache druhé; měření lže | Střídat pořadí |
| Neshody nikdo nečte | Technika běží a nic nepřináší | Někdo je vlastníkem rozborů |
| Vzorkuje se náhodně | Nedá se dohledat, proč rozdíl vznikl zrovna tam | Deterministicky, podle otisku vstupu |
| Nechá se běžet napořád | Dvojnásobek zátěže bez užitku | Vyhodnotit a odstranit |
| Neměří se výkon kandidáta | Správná, ale pomalá implementace vypadá hotově | Měřit čas obou větví |

---

## Demo

```bash
php Refactoring/System/ParallelRun/demo/run.php
```

Výpočet slevy — stará implementace proti nové, na pěti stech objednávkách. Demo ukáže, že **experiment vrací vždy výsledek kontroly**, najde 215 neshod a pak je roztřídí: 214 z nich je haléřové zaokrouhlení, **zbývá jediný skutečný rozdíl** — malá objednávka s kupónem, kde se strop uplatní jinak. Přesně ten případ, na který v testech nikdo nepomyslel.

Čtvrtá část pustí kandidáta, který na každé páté objednávce spadne: **dvacet objednávek obslouženo, nula pádů u zákazníka, čtyři výjimky v kandidátovi.** Zbytek měří režii a ukazuje, kolik neshod najde jednoprocentní, desetiprocentní a plný vzorek.

---

## Související

| Dokument | Vztah |
| -------- | ----- |
| [Branch by Abstraction](../BranchByAbstraction/) | **Přirozený následník.** Parallel Run ověří shodu, ten pak provoz přepne. Porovnávací režim v jeho demu je zjednodušený Parallel Run. |
| [Strangler Fig](../StranglerFig/) | Totéž o úroveň výš: než se schopnost přesměruje na nový systém, ověří se, že se chová stejně. |
| [Feature flag](../../../GitWorkflows/Glossary.md#feature-flag) | Čím se experiment zapíná, vzorkuje a vypíná. |
| [Idempotence](../../../SoftwareDesign/Glossary.md#idempotence) | Proč kandidát nesmí mít vedlejší efekty — dvakrát provedený zápis není totéž co jednou. |
| [Cohesive Mechanism](../../../SoftwareDesign/DDD/CohesiveMechanism/) (DDD) | Ideální kandidát na ověření: výpočet bez vedlejších efektů, se stejným vstupem i výstupem. |
| [Code review](../../../Processes/CodeReview/) | Rozbor neshod je práce, kterou má někdo vlastnit — jinak experiment běží a nic nepřináší. |

---

## Původ

|             |                    |
| ----------- | ------------------ |
| **Autor**   | Sam Newman (pojmenování vzoru) |
| **Rok**     | 2019               |
| **Zdroj**   | *Monolith to Microservices* |
| **Náročnost** | ●●○○○            |

Jako pojmenovaný vzor ho popsal **Sam Newman** v knize *Monolith to Microservices* (2019), kde ho staví vedle canary release a dark launchingu právě proto, že se pletou.

Praxe je ale starší a nejznámější implementaci vydal **GitHub** — knihovnu **Scientist** pro Ruby, popsanou jako *„a Ruby library for carefully refactoring critical paths"*. Odtud pochází terminologie *control / candidate / experiment*, kterou dnes používají i porty do jiných jazyků. Scientist zavedl i detaily, které se snadno opomenou: **zachytávání výjimek v kandidátovi, náhodné pořadí běhu a měření času obou větví.**

Za pozornost stojí, že tohle **není** jeden ze vzorů Fowlerovy série *Patterns of Legacy Displacement* — ta má jiné (Event Interception, Legacy Mimic, Transitional Architecture). Parallel Run k nim ale přirozeně patří a používá se s nimi.

Náročnost je dvojka — nejnižší v téhle sekci. Mechanika je jednoduchá a **nic nemůže rozbít, protože se nikdy nic nepřepíná**. Cena je jinde a je hlavně lidská:

- **Někdo musí projít neshody.** Technika vyrobí seznam; rozhodnout, co je šum a co chyba, umí jen člověk, který doméně rozumí.
- **Nedá se použít u zápisů.** Vedlejší efekty dvakrát jsou horší než neověřený kód.
- **Snadno se nechá běžet moc dlouho.** Zdvojená zátěž bez užitku, protože „ještě to chvíli necháme".

---

## Zdroje

- Sam Newman: *Monolith to Microservices*, O'Reilly, 2019
- GitHub: [Scientist](https://github.com/github/scientist) — knihovna pro Ruby, odkud pochází terminologie
- [Scientist v PHP](https://github.com/daylerees/scientist) — jeden z portů

---

<details>
<summary>Metadata techniky</summary>

```yaml
name: Parallel Run
level: system
author: Sam Newman (pojmenování), GitHub Scientist (implementace)
year: 2019
duration: dny až týdny
reversible: ano — vypnutím experimentu, nic se nepřepínalo
requires_tests: doporučeno
difficulty: 2
tags: [ověření, porovnání, legacy, vzorkování, scientist]
leads_to: [BranchByAbstraction]
related: [BranchByAbstraction, StranglerFig, CohesiveMechanism]
status: done
```

</details>
