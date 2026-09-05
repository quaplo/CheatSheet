# Core Domain (Jádro domény)

> [← zpět na DDD](../)

> **V jedné větě:** Pojmenuj tu část systému, kvůli které firma vydělává — a zařiď, aby na ní dělali nejlepší lidé a nesplývala se vším ostatním.

> [!NOTE]
> Tenhle vzor se **nedělá v kódu**. Je to rozhodnutí o tom, kam směřovat pozornost, a nedá se odvodit ze zdrojáků — musí ho udělat někdo, kdo ví, čím firma vydělává. Ostatní [destilační vzory](../#destilace) jsou pak způsoby, jak to rozhodnutí promítnout do struktury.

---

## Problém

V každém systému je spousta částí a všechny jsou potřeba. Fakturace musí fungovat, e-maily musí odcházet, uživatelé se musí přihlásit. Jenže **ne všechno je stejně cenné** — a když se s tím tak zachází, ta nejcennější část se v tom ztratí.

Evans:

> „In a large system, there are so many contributing components, all complicated and all absolutely necessary to success, that **the essence of the domain model, the real business asset, can be obscured and neglected.** It is harsh reality that not all parts of the design are going to be equally refined. Priorities must be set."

A hned dodává postřeh, ve kterém se pozná skoro každý tým:

> „But **scarce, highly skilled developers tend to gravitate to technical infrastructure** or neatly definable domain problems that can be understood without specialized domain knowledge."

Nejlepší lidé přirozeně tíhnou k tomu, co je technicky zajímavé a co jde pochopit bez ptaní se doménového experta. **Tedy přesně k tomu, co jádrem není.**

**Poznáš to podle:**

- na otázku „čím se lišíme od konkurence" dostaneš v týmu **pět různých odpovědí**
- nejzkušenější člověk v týmu dělá na **cache vrstvě a nasazování**
- fakturace má **trojnásobek kódu** oproti tomu, čím se produkt živí
- do integrace s platební bránou šly **měsíce**, do vlastního algoritmu týdny
- nový člověk se učí systém **odshora dolů** a nikdo mu neřekne, kde má začít
- „nemáme čas" se říká hlavně u té části, která je nejcennější

---

## Řešení

> „Therefore: **Boil the model down.** Define a core domain and provide a means of easily distinguishing it from the mass of supporting model and code. Bring the most valuable and specialized concepts into sharp relief. **Make the core small. Apply top talent to the core domain**, and recruit accordingly. Spend the effort in the core to find a deep model and develop a supple design—sufficient to fulfill the vision of the system. **Justify investment in any other part by how it supports the distilled core.**"

Čtyři pokyny, každý nepohodlný jinak:

| Pokyn | Co znamená v praxi |
| ----- | ------------------ |
| **Boil the model down** | Vybrat. A tím i říct o zbytku, že tak důležitý není. |
| **Make the core small** | Jádro není „naše doména". Je to malá část, kterou se lišíš. |
| **Apply top talent** | Nejlepší lidé nemají dělat infrastrukturu. |
| **Justify investment by how it supports the core** | Vše ostatní se poměřuje tím, jak jádru slouží. |

### Tři druhy podoblastí

Rozdělení, které z toho plyne a bez kterého se vzor nedá použít:

| | **Core Domain** | **Supporting Subdomain** | [**Generic Subdomain**](../GenericSubdomains/) |
| --- | --- | --- | --- |
| Odlišuje nás | **ano** | ne | ne |
| Dělá to jiná firma stejně | ne | ne — je to naše specifikum | **ano** |
| Dá se koupit | i kdyby ano, nekupuj | obvykle ne | **ano** |
| Kdo na tom dělá | **nejzkušenější** | kdokoli z týmu | nikdo z jádrových vývojářů |
| Jak stavět | hledat hluboký model | jednoduše, bez ambicí | koupit, stáhnout, zadat ven |

Demo klasifikuje podoblasti e-shopu, který se živí doporučováním zboží a dynamickými cenami:

```
Podoblast               Klasifikace             Co s tím
Doporučování zboží      Core Domain             stavět sami, nejlepšími lidmi
Dynamické ceny          Core Domain             stavět sami, nejlepšími lidmi
Katalog produktů        Supporting Subdomain    stavět sami, ale jednoduše
Objednávkový proces     Supporting Subdomain    stavět sami, ale jednoduše
Platby                  Generic Subdomain       koupit, stáhnout, zadat ven
Fakturace a DPH         Generic Subdomain       koupit, stáhnout, zadat ven
Odesílání e-mailů       Generic Subdomain       koupit, stáhnout, zadat ven
Správa uživatelů        Generic Subdomain       koupit, stáhnout, zadat ven
```

**Všimni si prvního řádku.** Doporučování zboží *jde* koupit — hotových systémů jsou desítky. A přesto je to jádro, protože je to důvod, proč k nám zákazník chodí. **Kdo si koupí to, čím se má lišit, přestane se lišit.**

### Diagnóza, kterou to umožní

Jakmile je rozdělení hotové, dá se změřit něco, co se jinak měřit nedá — **kam skutečně teče úsilí**:

```
Core Domain             █████░░░░░░░░░░░░░░░░░░░  21.0 %    65 dní
Supporting Subdomain    █████████░░░░░░░░░░░░░░░  37.1 %   115 dní
Generic Subdomain       ██████████░░░░░░░░░░░░░░  41.9 %   130 dní

do jádra jde 21 % úsilí, do zbytku 79 %
```

Firma, která se živí doporučováním a cenotvorbou, do nich dává pětinu času — a **čtyři pětiny do věcí, které dělá stejně jako všichni ostatní.**

Tohle číslo je celý přínos vzoru. Nedá se získat jinak než tím, že někdo řekne, co je jádro; a jakmile je na stole, rozhovor o prioritách se vede úplně jinak.

---

## Účastníci

| Role | Odpovědnost |
| ---- | ----------- |
| **Doménový expert / produkt** | Řekne, čím firma vydělává. Bez něj to nejde. |
| **Tým** | Rozdělení zná a řídí se jím při odhadech i při volbě, co stavět sami |
| **Core Domain** | Malá, pojmenovaná část, na kterou míří pozornost |
| **Supporting a [Generic](../GenericSubdomains/)** | Vše ostatní — obsluhuje jádro, nesoutěží s ním o lidi |

---

## Implementace v PHP

Vzor sám se nekóduje, ale rozhodnutí se dá **zviditelnit** — a to už kód je.

### Napsat to tam, kde to lidé uvidí

Nejlevnější krok. Evans na to má samostatný vzor *Domain Vision Statement* — jednu stránku o tom, co je jádro a jakou hodnotu přináší:

```
src/
    Recommendation/     ← CORE: proč k nám zákazník chodí
    Pricing/            ← CORE: dynamické ceny
    Catalog/            supporting
    Ordering/           supporting
    Payment/            generic — Stripe SDK
    Invoicing/          generic — knihovna
```

Komentář u složky nebo řádek v README stojí minutu a **odstraní nejčastější zdroj nedorozumění**: že každý má v hlavě jiné jádro.

### Promítnout to do struktury

Když rozdělení existuje, další destilační vzory ho udělají viditelným:

| Krok | Vzor |
| ---- | ---- |
| Vytěsnit obecné části | [Generic Subdomains](../GenericSubdomains/) |
| Vytáhnout složité výpočty | [Cohesive Mechanism](../CohesiveMechanism/) |
| Strukturálně oddělit jádro | [Segregated Core](../SegregatedCore/) |

**Pořadí je Evansovo a stojí za dodržení** — první dva kroky jsou levné, třetí je zásah do celého modelu.

### Kde se rozhodnutí projeví v kódu

| Rozhodnutí | Důsledek v kódu |
| ---------- | --------------- |
| Tohle je jádro | Vlastní model, [value objecty](../ValueObject/), testy na pravidla, žádné zkratky |
| Tohle je supporting | Jednoduchá implementace, klidně [Active Record](../../PoEAA/ActiveRecord/) |
| Tohle je generic | Knihovna nebo služba za [adaptérem](../AnticorruptionLayer/) |

Poslední řádek je důležitý: **generickou podoblast si pouštíš do systému za překladovou vrstvou**, ne přímo — jinak si její model naimportuješ do svého.

---

## Kdy použít

- ✅ **Systém má víc podoblastí** a není zřejmé, která je ta důležitá.
- ✅ **Rozhoduje se, co stavět a co koupit.**
- ✅ **Tým roste** a je potřeba vědět, kam dát zkušené lidi.
- ✅ **Chystá se velká investice** a je potřeba ji zdůvodnit.
- ✅ Cítíš, že se **energie rozpouští** do věcí, které nikoho nezajímají.

## Kdy nepoužít

- ❌ **Aplikace je celá CRUD** a žádné jádro nemá. Pak je poctivější to přiznat než si ho vymýšlet.
- ❌ **Jsi na projektu sám** a všechno stejně děláš ty.
- ❌ **Nikdo z byznysu není k dispozici.** Klasifikace udělaná programátory podle toho, co je zajímavé, je horší než žádná.
- ❌ **Produkt teprve hledá, čím bude.** Pak se jádro mění každý měsíc a značkovat ho nemá smysl.

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| Jádrem je „celá naše doména" | Když je jádro všechno, není to k ničemu | Evans: **make the core small** |
| Klasifikaci dělají programátoři sami | Vyjde z toho, co je technicky zajímavé | Rozhoduje ten, kdo ví, čím firma vydělává |
| Jádro se koupí, protože to jde | Koupíš si to, čím ses měl lišit | Odlišující část se staví, i když existuje hotová |
| Nejzkušenější člověk dělá infrastrukturu | Přesně past, na kterou Evans upozorňuje | Nejlepší lidé do jádra |
| Rozdělení nikde není zapsané | Za měsíc má každý v hlavě jiné | Napsat to tam, kde to lidé uvidí |
| Generická podoblast se dostane do modelu napřímo | Naimportuješ si cizí model do svého | Za [překladovou vrstvou](../AnticorruptionLayer/) |
| Jádro se určí jednou a nikdy nereviduje | Byznys se mění, jádro s ním | Revidovat, když se mění strategie |
| Do supporting částí se investuje jako do jádra | Peníze a lidé odtékají tam, kde se to nevrátí | Supporting stavět jednoduše |

---

## V praxi

- **„Build vs. buy" rozhodnutí** — tenhle vzor je jeho doménová verze a dává mu jasnější kritérium než cena.
- **Modulární monolit** — moduly pojmenované podle podoblastí; u každého je poznat, do které kategorie patří.
- **Onboarding** — nováčka pustíš nejdřív do supporting části, jádro si nechá na později. Nefunguje to naopak.
- **Stripe, Mailchimp, účetní systémy** — typické generické podoblasti, kde má nákup přednost.
- **Team topologies a vlastnictví modulů** — kdo vlastní jádro, bývá stabilní tým; generické části se předávají snáz.

---

## Související patterny

| Pattern | Vztah |
| ------- | ----- |
| [Generic Subdomains](../GenericSubdomains/) | **Druhá strana téže mince** — co jádrem není a jak s tím naložit. |
| [Cohesive Mechanism](../CohesiveMechanism/) | Druhý destilační krok: vytáhnout z jádra složité výpočty. |
| [Segregated Core](../SegregatedCore/) | Poslední krok: strukturálně oddělit jádro, když předchozí nestačily. |
| [Bounded Context](../BoundedContext/) | Jiné dělení: kontext podle **jazyka a významu**, jádro podle **hodnoty pro byznys**. Nemusí se krýt. |
| [Context Map](../ContextMap/) | Mapa vztahů mezi kontexty; jádro bývá to, čemu se ostatní přizpůsobují. |
| [Anticorruption Layer](../AnticorruptionLayer/) | Čím se hotové generické řešení pouští do systému, aniž by prosáklo do modelu. |
| [Active Record](../../PoEAA/ActiveRecord/) | Legitimní volba pro supporting podoblasti — tam, kde model je tabulka. |
| [Domain Vision Statement](../DomainVisionStatement/) · [Highlighted Core](../HighlightedCore/) (DDD) | Dva nejlevnější způsoby, jak toto rozhodnutí zviditelnit — stránka textu a značky v kódu. |
| [Conwayův zákon](../../Principles/ConwaysLaw.md) | Evansovo „apply top talent to the core“ je organizační rozhodnutí s architektonickým důsledkem. |

---

## Vztah k principům

| Princip | Jak souvisí |
| ------- | ----------- |
| [YAGNI](../../Principles/Simplicity.md#yagni--you-arent-gonna-need-it) | Do supporting a generických částí se nestaví nic dopředu. Ambice patří do jádra. |
| [KISS](../../Principles/Simplicity.md#kiss--keep-it-simple) | Mimo jádro je jednoduché řešení to správné, ne to nouzové. |
| [Zviditelni implicitní](../../Principles/ObjectDesign.md#zviditelni-implicitní) | „Tohle je ta důležitá část" přestane být ústní tradicí. |
| [Vysoká soudržnost](../../Principles/CohesionAndCoupling.md#stupnice-soudržnosti) | Jádro drží pohromadě to, co spolu souvisí hodnotou, ne technologií. |

---

## Demo

```bash
php SoftwareDesign/DDD/CoreDomain/demo/run.php
```

Klasifikátor podoblastí e-shopu podle Evansových kritérií — společný pro tenhle vzor i pro [Generic Subdomains](../GenericSubdomains/). Ukáže rozdělení na Core, Supporting a Generic i s doporučením, co s každou částí dělat, a pak **změří, kam teče úsilí**: 21 % do jádra, 79 % do zbytku. Spočítá, kolik dní by uvolnilo koupení poloviny generických částí, připomene Evansovu past s nejlepšími lidmi na infrastruktuře a končí pěti otázkami, kterými se jádro pozná — **žádná z nich není technická.**

---

## Původ

|               |                                                   |
| ------------- | ------------------------------------------------- |
| **Zdroj**     | *Domain-Driven Design: Tackling Complexity in the Heart of Software* |
| **Autor**     | Eric Evans                                        |
| **Rok**       | 2003                                              |
| **Kategorie** | Strategický návrh — destilace (kapitola 15)       |
| **Obtížnost** | ●●○○○                                             |

Vzor otevírá kapitolu **Distillation** a je jejím předpokladem — ostatní destilační vzory se bez něj nedají použít, protože všechny odpovídají na otázku „co udělat s tím, co jádro **není**".

Za povšimnutí stojí, jak Evans formuluje důvod. Nejde o čistotu návrhu ani o architekturu, ale o **ekonomii**: doménový model je „the real business asset" a jádro je ta jeho část, která tvoří hodnotu. Proto je i doporučení ekonomické — dej tam nejlepší lidi a investici do všeho ostatního zdůvodni tím, jak jádru slouží.

Obtížnost je dvojka, protože technicky není co dělat. Cena je jinde a je nepříjemná:

- **Rozhodnutí musí udělat byznys, ne tým**, a ten často nemá jasno sám.
- **Vybrat znamená říct o zbytku, že tak důležitý není** — a to se v týmu poslouchá špatně.
- **„Make the core small" jde proti instinktu.** Většina týmů označí za jádro polovinu systému a tím vzor vyprázdní.

Nejrozšířenější způsob, jak vzor minout, je nakreslit rozdělení na jeden workshop a nikam ho nezapsat. Za měsíc má každý v hlavě jiné jádro a je to zpátky tam, kde to začalo.

---

## Zdroje

- Eric Evans: *Domain-Driven Design*, Addison-Wesley, 2003 — kapitola 15, *Distillation*
- Eric Evans: [*Domain-Driven Design Reference*](https://www.domainlanguage.com/wp-content/uploads/2016/05/DDD_Reference_2015-03.pdf) (PDF, 2015) — souhrn definic, pod licencí CC BY 4.0

---

<details>
<summary>Metadata patternu</summary>

```yaml
name: Core Domain
name_cs: Jádro domény
category: Strategický návrh — destilace
source: DDD – Domain-Driven Design
authors: Eric Evans
year: 2003
difficulty: 2
tags: [destilace, jádro, priority, build vs buy, hodnota]
principles: [YAGNI, KISS, MakeImplicitExplicit, CohesionAndCoupling]
related: [GenericSubdomains, CohesiveMechanism, SegregatedCore, BoundedContext, ContextMap, AnticorruptionLayer, ActiveRecord]
status: done
```

</details>
