# Out of the Tar Pit: složitost, stav a řízení

> [← zpět na Principy](README.md)

> **V jedné větě:** Článek, který tvrdí, že **složitost je jediná skutečná příčina** problémů se softwarem, že jejím největším zdrojem je **měnitelný stav** — a že velká část té složitosti není nutná.

> Ben Moseley, Peter Marks · **2006** · *Out of the Tar Pit*

Je to šedesátistránkový text, který se v komunitě cituje často a čte zřídka. Tenhle dokument shrnuje **první polovinu** — diagnózu, která zestárla výborně. Druhá polovina navrhuje lék, který se neujal, a i o tom je [řeč níž](#a-část-která-se-neujala).

---

## Proč to číst

Navazuje na [esenciální a akcidentální složitost](Simplicity.md#esenciální-a-akcidentální-složitost) od Brookse, ale jde dál a jinam. Brooks tvrdil, že většina zbývající složitosti je **esenciální** — tedy že se s ní nedá nic dělat. Moseley a Marks jeho rozdělení přebírají a ten závěr **odmítají**: podle nich je velká část dnešní složitosti akcidentální, a hlavní podezřelý má jméno.

---

## Složitost je jediná příčina

Brooks jmenoval čtyři vlastnosti, kterými je software těžký: složitost, konformita, měnitelnost a neviditelnost. Moseley a Marks z nich škrtají tři:

> „Of these we believe that **Complexity is the only significant one** — the others can either be classified as forms of complexity, or be seen as problematic solely because of the complexity in the system.
>
> **Complexity is the root cause of the vast majority of problems with software today.** Unreliability, late delivery, lack of security — often even poor performance in large-scale systems can all be seen as deriving ultimately from unmanageable complexity."

Argument je jednoduchý a těžko se s ním polemizuje: **porozumět systému je podmínkou všeho ostatního** — a to je přesně to, co složitost ničí.

### Jejich definice jsou přísnější než Brooksovy

> **Essential Complexity** is inherent in, and the essence of, **the problem (as seen by the users)**.
>
> **Accidental Complexity** is all the rest — complexity with which the development team would not have to deal in the ideal world.

A hned k tomu dodávají, jak přísně to myslí:

> „Note that the definition of essential is **deliberately more strict than common usage**. […] bits, bytes, transistors, electricity and computers themselves are not in any way essential (because they have nothing to do with the users' problem)."

To je ostřejší nůž, než se běžně používá. **Esenciální je jen to, co by zůstalo, kdyby software vůbec neexistoval** — tedy pravidla problému, ne pravidla našeho řešení. Podle téhle definice je databáze akcidentální. Framework taky. A dokonce i to, že je něco objekt.

---

## Tři zdroje složitosti

### 1. Stav — ten hlavní

Článek začíná obrázkem, který zná každý:

> „Anyone who has ever telephoned a support desk for a software system and been told to **„try it again", or „reload the document", or „restart the program", or „reboot your computer"** […] has direct experience of the problems that state causes."

A pak vysvětlí, proč ty rady tak často fungují: protože **systém je ve stavu, ve kterém neměl být** — a restart ho z něj dostane. Jejich závěr:

> „…it is our belief that the **single biggest remaining cause of complexity** in most contemporary large systems **is state**, and the more we can do to limit and manage state, the better."

Nejtvrdší část argumentu je o testování:

> „…even though the number of possible inputs may be very large, **the number of possible states the system can be in is often even larger**."

Testování je podle nich **vzorkování, ne důkaz** — a stav ten vzorek zmenšuje do bezvýznamnosti. Demo k tomuhle dokumentu to počítá na čtyřech operacích nad košíkem:

```
                                      měnitelný           neměnný
možných pořadí                        24                  24
různých výsledků                      8                   1
pořadí se správnou částkou            2 z 24              24 z 24
```

**Dvě pořadí z dvaceti čtyř dávají správnou částku.** Kdo napíše jeden test, má 92% šanci, že netrefí — a každá z těch metod je přitom sama o sobě správně.

### 2. Řízení — pořadí, které nás nemá zajímat

> „Control is basically about the order in which things happen. The problem with control is that **very often we do not want to have to be concerned with this**."

Někde pořadí samozřejmě záleží. Problém je, že běžné jazyky nás nutí se jím zabývat **i tam, kde na něm nezáleží** — protože pořadí řádků v souboru je zároveň pořadím vykonávání.

V demu je to vidět na druhé variantě košíku: metody jen sbírají fakta a částka se spočítá až na konci. **Pořadí přestalo být informací**, a tím zmizelo 23 z 24 věcí, o kterých by se muselo přemýšlet.

### 3. Objem kódu — násobič

> „This cause is basically in many ways a secondary effect — **much code is simply concerned with managing state or specifying control**."

Objem kódu není samostatná příčina, ale zesiluje obě předchozí. Autoři ho zmiňují ze dvou důvodů: je to **jediná z těch tří věcí, která se dá snadno změřit**, a špatně se snáší se zbylými dvěma.

---

## Co z toho plyne pro kód, který píšeš

Článek je akademický, ale tyhle tři věci se dají použít zítra:

| Doporučení | Jak to vypadá v PHP |
| ---------- | ------------------- |
| **Omez stav** | `readonly` třídy, [value objecty](../DDD/ValueObject/), metody `withX()` místo setterů |
| **Omez řízení** | Spočítej výsledek z faktů na konci místo průběžného přepisování mezivýsledku |
| **Odděl esenciální stav od akcidentálního** | Stav domény zvlášť, cache a mezivýsledky zvlášť — a ať je poznat který je který |

Třetí řádek je ta myšlenka, která z článku přežila nejdál. **Ne „žádný stav", ale „vědět, který stav je nutný".** Objednávka nějaký stav mít musí; to, že si ho někdo drží i v session, v cache a ve třech proměnných, už nutné není.

> [!NOTE]
> Tohle není argument pro funkcionální programování v PHP. Je to argument pro **méně míst, kde se něco mění** — a ten platí v jakémkoli jazyce. [Value Object](../DDD/ValueObject/) a [neměnná kolekce](../ObjectCalisthenics/FirstClassCollection/) jsou v PHP obvyklá cesta.

---

## A část, která se neujala

Druhá polovina článku navrhuje **Functional Relational Programming** — architekturu, kde je aplikace rozdělená na esenciální stav (relace), esenciální logiku (funkce) a akcidentální části (výkon, uložení, uživatelské rozhraní). Relační model si berou od Codda, s poznámkou, že *„has — despite its origins — nothing intrinsically to do with databases"*.

**Tenhle návrh se v praxi nerozšířil.** Neexistuje jazyk ani framework, který by ho naplnil, a článek sám zůstal u návrhu. Je poctivé to říct — i proto, že to nic neubírá na první polovině.

Co se ujalo, je rozebrané po kouskách:

| Myšlenka z článku | Kde ji dnes potkáš |
| ----------------- | ------------------ |
| Oddělit esenciální stav od odvozeného | [CQRS](../Architecture/CQRS/) — zápisový model zvlášť, čtecí odvozený |
| Neměnná data místo přepisování | [Value Object](../DDD/ValueObject/), `readonly` v PHP 8.2+ |
| Logika jako funkce bez vedlejších efektů | Evansovy *Side-Effect-Free Functions*, [CQS](ObjectDesign.md#cqs--command-query-separation) |
| Stav jako posloupnost faktů, ne jako snímek | [Event Sourcing](../Architecture/EventSourcing/) |

---

## Kde s tím nesouhlasit

Článek je dobrý, ale není písmo svaté a stojí za to znát jeho slabiny:

- **Definice esenciálního je tak přísná, že se s ní nedá pracovat.** Když je databáze akcidentální, je akcidentální i většina toho, co reálně píšeš — a rozdělení přestává pomáhat při rozhodování.
- **Návrh řešení nebyl nikdy ověřen v praxi.** Autoři sami píšou, že jde o *possible approach*.
- **O stavu na hranicích systému nemluví.** Platby, e-maily a cizí systémy stav mají a odstranit se nedá — což je přesně ta část, kde se dnes dělá nejvíc chyb.

Užitečné je to i tak. **Diagnóza je lepší než recept** — a ta první polovina se za dvacet let nezměnila.

---

## Demo

```bash
php SoftwareDesign/Principles/demo/OutOfTheTarPit/run.php
```

Čtyři operace nad košíkem — dvě položky, kupon a doprava. Demo projde **všech 24 pořadí** ve dvou variantách a spočítá, kolik různých částek z nich vyleze.

Košík, který si drží mezivýsledek, jich dá **osm**; ten, který počítá až na konci, **jednu**. Každá metoda je přitom v obou případech správně — liší se jen tím, jestli mezi voláními něco zůstává.

Závěrem tabulka, jak počet pořadí roste s počtem operací: osm operací nad jedním objektem je 40 320 kombinací. Testy jich projdou hrst.

---

## Souvisí s

| Dokument | Vztah |
| -------- | ----- |
| [Jednoduchost](Simplicity.md#esenciální-a-akcidentální-složitost) | Brooksovo rozdělení, ze kterého článek vychází a jehož závěr odmítá. |
| [Value Object](../DDD/ValueObject/) (DDD) | Nejpřímější odpověď na „omez stav" v objektovém jazyce. |
| [First Class Collection](../ObjectCalisthenics/FirstClassCollection/) | Totéž pro skupinu — neměnná varianta je tam rozebraná. |
| [CQS](ObjectDesign.md#cqs--command-query-separation) | Oddělení „mění stav" od „vrací hodnotu" — menší verze téhož nápadu. |
| [CQRS](../Architecture/CQRS/) | Oddělení esenciálního stavu od odvozeného, dotažené na architekturu. |
| [Soudržnost a provázanost](CohesionAndCoupling.md) | Druhé měřítko složitosti — to strukturální, kdežto tenhle článek měří stavové. |

---

## Původ

| | |
| --- | --- |
| **Autoři** | Ben Moseley, Peter Marks |
| **Rok** | 2006 |
| **Rozsah** | 66 stran |
| **Navazuje na** | Fred Brooks: *No Silver Bullet* (1986) |

Jméno je z Brooksovy *The Mythical Man-Month*, která začíná obrazem prehistorických zvířat zapadlých v dehtovém jezeře — čím víc se zmítají, tím hlouběji klesají. Moseley a Marks si ten obraz půjčili a ptají se, **jak z něj ven**.

Článek nevyšel v recenzovaném sborníku a autoři nejsou z akademické obce. Přesto patří k nejcitovanějším textům o složitosti softwaru — hlavně proto, že pojmenoval něco, co většina lidí zná z vlastní práce a neuměla to vyslovit.

---

## Zdroje

- Ben Moseley, Peter Marks: [*Out of the Tar Pit*](https://curtclifton.net/papers/MoseleyMarks06a.pdf), 2006
- Fred Brooks: [*No Silver Bullet*](https://worrydream.com/refs/Brooks_1986_-_No_Silver_Bullet.pdf), 1986 — text, na který navazují
- Fred Brooks: *The Mythical Man-Month*, Addison-Wesley, 1975 — odkud je ten dehet
