# Strangler Fig

> [← zpět na Refaktoring systému](../)

> **V jedné větě:** Postav nový systém kolem starého a přesměrovávej do něj schopnost po schopnosti, dokud starý nemá co dělat — a pak ho vypni.

> [!NOTE]
> Původně se technika jmenovala jen *Strangler Application*. **Fowler ji později přejmenoval na *Strangler Fig***, aby zdůraznil botanický původ a zmírnil násilnou konotaci samotného „škrtiče". Pod oběma jmény jde o totéž.

---

## Kdy po tom sáhnout

Máš systém, který je potřeba nahradit celý — a přepsat ho naráz nejde.

Fowler k tomu poznamenává, proč velké přepisy selhávají:

> „Replacing a serious IT system takes a long time […] **Replacements seem easy to specify, but often it's hard to figure out the details of existing behavior.**"

Druhá půlka věty je ten skutečný problém. **Nikdo neví, co starý systém přesně dělá** — a přepis podle zadání vyrobí něco, co dělá skoro totéž.

**Poznáš to podle:**

- systém je **starý a nikdo mu celý nerozumí**, ale běží a vydělává
- „přepíšeme to za rok" už zaznělo a **nestalo se to**
- specifikace neexistuje a **jediná pravda je běžící kód**
- nová funkce se do starého systému **dostává hůř a hůř**
- části systému jde od sebe **oddělit podle schopností** (katalog, objednávky, fakturace)
- starý a nový systém můžou **běžet vedle sebe**, klidně i v jiné technologii

---

## Předtím

Klient volá starý systém přímo. Ten umí všechno a nikdo se ho nechce dotknout.

```
klient ──────────────► legacy monolit
                        katalog · košík · objednávky · fakturace · reporty
```

Dokud to vypadá takhle, **nedá se vyměnit nic** — leda všechno naráz.

---

## Mechanika

### 0. Najdi šev

Rozděl systém na **schopnosti**, které jdou přesunout samostatně. Šev nevede podle vrstev ani podle tabulek, ale podle toho, **co systém dělá** — a bývá to tam, kde vede i [hranice kontextu](../../../SoftwareDesign/DDD/BoundedContext/).

**Po tomhle kroku platí:** víš, co je nejmenší kus, který se dá přesunout sám.

### 1. Postav fasádu

Mezi klienta a starý systém přijde vrstva, která **zatím jen předává dál**:

```
klient ──► fasáda ──► legacy monolit
```

```
schopností ve starém      5
schopností v novém        0
obslouženo starým         100 z 100
```

Fasáda existuje, ale nic nemění. **Chování se nezměnilo — a to je celý smysl prvního kroku.**

V praxi to bývá reverzní proxy, API gateway nebo routovací vrstva v aplikaci. Mechanismus je jedno; tomuhle zachytávání se říká **event interception** a je to podle autorů série *Patterns of Legacy Displacement* nutná součást:

> „If you are using the Strangler Fig pattern then you will also be using some form of Event Interception."

**Po tomhle kroku platí:** všechen provoz teče přes jedno místo, kde se dá rozhodovat.

### 2. Postav první schopnost v novém systému

Nová schopnost vzniká **vedle** starého systému, ne v něm. Fowler:

> „Like the fig, it begins with small additions, often new features, that are built on top of, yet separate to the legacy code base."

**Po tomhle kroku platí:** nová schopnost je hotová, ale nikdo ji nevolá.

### 3. Přesměruj ji ve fasádě

```php
$facade->route('reporty');
```

**Po tomhle kroku platí:** jedna schopnost jde na nový systém, zbytek beze změny.

### 4. Opakuj

```
na začátku              ░░░░░░░░░░░░░░░░░░░░   0.0 %   zbývá: katalog, košík, objednávky, fakturace, reporty
po přesunu: reporty     █░░░░░░░░░░░░░░░░░░░   3.0 %   zbývá: katalog, košík, objednávky, fakturace
po přesunu: fakturace   ██░░░░░░░░░░░░░░░░░░  10.0 %   zbývá: katalog, košík, objednávky
po přesunu: objednávky  █████░░░░░░░░░░░░░░░  25.0 %   zbývá: katalog, košík
po přesunu: košík       ██████████░░░░░░░░░░  50.0 %   zbývá: katalog
po přesunu: katalog     ████████████████████ 100.0 %   zbývá: —
```

**Pořadí není náhodné.** Reporty jsou jen pro čtení a jejich výpadek nikdo neuvidí; katalog je polovina provozu a jde poslední. **Začíná se tím, co se nejlíp vrací** — ne tím, co je nejzajímavější.

### 5. Vypni starý systém

```
schopností stále na starém      žádná
provozu na starém               0 %
lze vypnout?                    ano
```

Teprve teď. **Dokud fasáda posílá byť jedinou schopnost na starý systém, musí běžet** — a s ním i jeho databáze, zálohy a licence.

> [!NOTE]
> **Kde se dá zastavit:** po každém přesunu. Každá přesunutá schopnost je hotová věc, i kdyby se dál nepokračovalo. Cena zastavení uprostřed je ale vyšší než u [Branch by Abstraction](../BranchByAbstraction/) — zůstanou ti **dva běžící systémy** a fasáda mezi nimi. Viz [Co to stojí](#co-to-stojí).

---

## Průběh a návratová cesta

| | |
| --- | --- |
| **Jak dlouho to trvá** | Měsíce až roky; u velkého systému je to program, ne úkol |
| **Co vidí uživatel** | Nic, pokud se schopnosti chovají stejně |
| **Jak se vrátit** | **Přesměrování jedné schopnosti zpět** ve fasádě |
| **Jak dlouho žije mezistav** | Celou dobu migrace — oba systémy běží současně |

Návratová cesta je zrnitá a to je hlavní přínos:

```
po přesunu objednávek         15 požadavků
po návratu zpět               0 požadavků
```

**Vrací se jedna schopnost, ne celá migrace.** Ostatní přesuny zůstávají — a to je rozdíl proti přepisu naráz, kde se vrací všechno nebo nic.

Háček je v datech. Když nová schopnost začne zapisovat, návrat zpět už není jen přepnutí ve fasádě — **musí se vyřešit, co se stalo s daty, která mezitím vznikla.** Proto se začíná schopnostmi, které jen čtou.

---

## Jak ověřit, že to funguje

**Nová schopnost se musí chovat jako stará** — a stará je jediná specifikace, kterou máš. Postup:

1. **Zaznamenej skutečný provoz** starého systému — vstupy i výstupy.
2. **Pusť tytéž vstupy na novou schopnost** a porovnej. Když se to nechá běžet naostro, je to **Parallel Run**.
3. **Přesměruj část provozu** a sleduj chyby, latenci a výsledky.
4. **Teprve pak přesuň zbytek.**

Autoři *Patterns of Legacy Displacement* u toho varují před tím, co se přehlíží:

> „significant care should be taken if you change the existing write behaviour as you may be **breaking a vital implicit contract**."

Starý systém má chování, které nikdo nezapsal a někdo na něm závisí — pořadí záznamů, formát čísla, zaokrouhlení. **Ten nepsaný kontrakt je největší riziko celé migrace** a testy ho nezachytí, protože o něm nikdo neví.

---

## Co to stojí

| Cena | Kdy se vyplatí |
| ---- | -------------- |
| **Dva běžící systémy** po celou dobu — provoz, zálohy, licence | Když je přepis naráz neúnosně rizikový |
| **Fasáda navíc**, přes kterou teče všechno | Když je potřeba rozhodovat o směrování za běhu |
| **Data ve dvou místech**, dokud se schopnost nepřesune celá | Když jde schopnost oddělit i datově |
| **Trvá to déle** než přepis, často roky | Když se nesmí zastavit vývoj ani provoz |
| **Nikdo nevidí konec** — přínos přichází po částech | Když je postupná návratnost cennější než jeden velký skok |

Poslední řádek je ten, na kterém migrace umírají. **Strangler Fig nemá jeden velký den, kdy je hotovo** — má dva roky drobných kroků. Když se přestane sledovat, kolik schopností zbývá, projekt se rozpustí do běžné práce a zůstanou dva systémy navždy.

Proto je užitečné mít to číslo na očích:

```
zbývá na starém: katalog, košík
```

---

## Kdy to nedělat

- ❌ **Systém je malý.** Přepsat ho za týden je levnější než rok udržovat fasádu a dva systémy.
- ❌ **Nejdou najít švy.** Když je všechno propletené se vším, není co přesouvat samostatně — pak je první krok rozplétání, ne migrace.
- ❌ **Není kdo by to dotáhl.** Migrace na dva roky bez vlastníka skončí v půlce a zůstanou dva systémy.
- ❌ **Starý systém se má vypnout k pevnému datu.** Technika je postupná; termín ji zabije.
- ❌ **Vyměňuje se jedna implementace, ne celý systém.** To je [Branch by Abstraction](../BranchByAbstraction/) a je levnější.

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| Začne se nejsložitější schopností | Nejdřív se narazí, dřív než se technika osvědčí | Začít tím, co se nejlíp vrací — typicky čtení |
| Fasáda se přeskočí | Není kde přesměrovat; migrace se dělá nasazením | Fasáda je krok 1, i když zatím nic nemění |
| Nová schopnost dělá „to samé, jen líp" | Změna chování se schová do migrace a nikdo ji nečeká | Nejdřív přesunout, změny až potom |
| Data zůstanou ve starém systému | Starý systém nejde vypnout, i když nic neobsluhuje | Přesun schopnosti zahrnuje i její data |
| Nesleduje se, kolik zbývá | Migrace se rozpustí do běžné práce | Číslo na očích, pravidelně |
| Migrace nemá vlastníka | Po roce ji nikdo nedotáhne | Někdo za ni odpovídá |
| Fasáda se stane místem, kde je logika | Vznikl třetí systém, který nikdo neplánoval | Fasáda jen směruje |
| Starý systém se vypne dřív | Něco na něj pořád chodí, jen to nikdo neměřil | Vypnout až při nule, a chvíli nechat stát |
| Ignoruje se nepsaný kontrakt | Rozbije se něco, o čem nikdo nevěděl | Porovnávat na skutečném provozu |

---

## Demo

```bash
php Refactoring/System/StranglerFig/demo/run.php
```

E-shop s pěti schopnostmi a fasádou před nimi. Demo ukáže, že **fasáda na začátku nic nemění**, pak přesouvá schopnosti po jedné v pořadí od nejmíň rizikové (reporty, 3 % provozu) po nejrizikovější (katalog, 50 %) a u každého kroku měří, kolik provozu už jede po novém. Pak ověří, **kdy se smí starý systém vypnout** — až když na něm nezbývá žádná schopnost. Čtvrtá část vrací jednu schopnost zpět a ukazuje, že ostatní přesuny zůstávají. Poslední staví techniku vedle [Branch by Abstraction](../BranchByAbstraction/).

---

## Související

| Dokument | Vztah |
| -------- | ----- |
| [Branch by Abstraction](../BranchByAbstraction/) | **Nejbližší příbuzný a nejčastější záměna.** Ten vyměňuje implementaci za společným rozhraním uvnitř kódu; tenhle přesouvá celé schopnosti na hranici systému. [Srovnání](#demo) je v demu. |
| **Parallel Run** | Jak ověřit, že se nová schopnost chová jako stará, dřív než jí svěříš provoz. *(zatím nezpracováno)* |
| [Bounded Context](../../../SoftwareDesign/DDD/BoundedContext/) (DDD) | Švy, podle kterých se systém dělí, obvykle vedou tam, kde vedou hranice kontextů. |
| [Anticorruption Layer](../../../SoftwareDesign/DDD/AnticorruptionLayer/) (DDD) | Co postavit mezi nový systém a starý model, aby jeho pojmy neprosákly do nového. |
| [Ports & Adapters](../../../SoftwareDesign/Architecture/PortsAndAdapters/) | Nový systém se staví takhle; starý zůstává za adaptérem. |
| [Conwayův zákon](../../../SoftwareDesign/Principles/ConwaysLaw.md) | Migrace, která nekopíruje vlastnictví týmů, se protáhne — každý přesun potřebuje někoho, kdo za něj odpovídá. |
| [Feature flag](../../../GitWorkflows/Glossary.md#feature-flag) | Čím se přesměrování ve fasádě přepíná. |

---

## Původ

|             |                    |
| ----------- | ------------------ |
| **Autor**   | Martin Fowler      |
| **Rok**     | 2004               |
| **Zdroj**   | bliki *Strangler Fig Application* |
| **Náročnost** | ●●●●○            |

Fowler viděl škrtící fíkovnice na dovolené v **Queenslandu v roce 2001** a popsal je takto:

> „These are vines that germinate in a nook of a tree. As it grows, it draws nutrients from the host tree until it reaches the ground to grow roots and the canopy to get sunlight."

Metafora sedí přesněji, než je obvyklé: rostlina **nezačíná tím, že by strom porazila**. Roste kolem něj, živí se jím a teprve když má vlastní kořeny a korunu, hostitel odumře. Přesně tak vypadá dobře vedená migrace — a přesně proto se nedá uspěchat.

Článek napsal v roce **2004** pod názvem *Strangler Application*; **později ho přejmenoval na *Strangler Fig Application***, aby zdůraznil botanický původ a zmírnil násilnou konotaci. Starší název se pořád používá.

Techniku později rozpracovali **Ian Cartwright, Rob Horn a James Lewis** v sérii *Patterns of Legacy Displacement* (2024), kde je Strangler Fig zastřešující vzor a jeho mechanismy — **Event Interception** a **Asset Capture** — dostaly vlastní popis. Zajímavé je, že Event Interception mezi svými nástroji uvádí i [Branch by Abstraction](../BranchByAbstraction/): obě techniky do sebe zapadají, jen každá na jiné úrovni.

Náročnost je čtyřka, přestože mechanika je jednoduchá — fasáda a postupné přesměrování. Cena je v délce a v tom, co se za ni stihne pokazit:

- **Trvá to měsíce až roky** a po celou dobu běží dva systémy.
- **Nikdo neví, co starý systém přesně dělá**, a nepsaný kontrakt se ukáže až v produkci.
- **Nemá jeden velký den, kdy je hotovo** — a bez vlastníka se rozpustí.
- **Data se přesouvají hůř než kód.** Schopnost je přesunutá teprve tehdy, když už nesahá do staré databáze.

Nejčastější způsob, jak techniku pokazit, není chyba v postupu. Je to **opuštění v půlce** — kdy se přesunou tři schopnosti, projekt ztratí prioritu a firma zůstane napořád se dvěma systémy a fasádou, kterou nikdo nechápe.

---

## Zdroje

- Martin Fowler: [*Strangler Fig Application*](https://martinfowler.com/bliki/StranglerFigApplication.html), 2004
- Martin Fowler: [*Original Strangler Fig Application*](https://martinfowler.com/bliki/OriginalStranglerFigApplication.html) — původní znění článku
- Cartwright, Horn, Lewis: [*Patterns of Legacy Displacement*](https://martinfowler.com/articles/patterns-legacy-displacement/) — série z roku 2024
- Cartwright, Horn, Lewis: [*Event Interception*](https://www.martinfowler.com/articles/patterns-legacy-displacement/event-interception.html)

---

<details>
<summary>Metadata techniky</summary>

```yaml
name: Strangler Fig
level: system
author: Martin Fowler
year: 2004
duration: měsíce až roky
reversible: ano — po jedné schopnosti; u zápisů složitější kvůli datům
requires_tests: ano
difficulty: 4
tags: [migrace, legacy, fasáda, event interception, postupná náhrada]
leads_to: []
related: [BranchByAbstraction, ParallelRun, BoundedContext, AnticorruptionLayer, PortsAndAdapters, ConwaysLaw]
status: done
```

</details>
