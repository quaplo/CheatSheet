# Ubiquitous Language (Jednotný jazyk)

> [← zpět na DDD](../)

> **V jedné větě:** Jedno slovo pro jednu věc — v rozhovoru, v dokumentaci i v kódu — protože každý překlad mezi nimi je místo, kde se ztrácí význam.

> [!IMPORTANT]
> Tohle je **nejzákladnější myšlenka celého DDD** a zároveň ta, která se nejčastěji vynechá. Ostatní vzory se dají zavést i bez ní; **výsledek pak ale bude technicky správný a doménově němý.** Evans jí věnuje druhou kapitolu, dřív než popíše jediný stavební blok.

---

## Problém

Doménový expert říká „storno". Analytik napíše „zrušení objednávky". V kódu je `abortOrder()`, vedle toho `revokeOrder()` a na třetím místě `setStatusToInactive()`. Všechno je to totéž — nebo možná ne, a nikdo si není jistý.

Evans:

> „**Domain experts use their jargon while technical team members have their own language** tuned for discussing the domain in terms of design. The terminology of day-to-day discussions **is disconnected from the terminology embedded in the code** (ultimately the most important product of a software project)."

A dodává postřeh, který se často přehlédne:

> „**And even the same person uses different language in speech and in writing**, so that the most incisive expressions of the domain often emerge in a transient form that is never captured in the code or even in writing."

Následek pojmenovává jednou větou:

> „**Translation blunts communication and makes knowledge crunching anemic.**"

**Poznáš to podle:**

- doménový expert řekne pojem a vývojář si ho **v hlavě překládá**
- pro jednu věc má kód **tři různá jména** a nikdo neví, jestli jsou to tři věci
- v kódu jsou pojmy jako `process`, `handle`, `manage`, `issue` — **slova, která doména nezná**
- doménový expert **nedokáže přečíst** ani názvy tříd, natož jim rozumět
- na schůzce se půl hodiny **vyjasňuje, o čem se mluví**
- v analýze je jiné slovo než v ticketu a v ticketu jiné než v kódu

Demo to měří na slovníku e-shopu:

```
              nalezeno      chybí
Before        1 z 6         Storno, Expedice, Reklamace, Dobropis, Rezervace

pokrytí:      Before 17 %
```

**Z šesti doménových pojmů je v kódu k nalezení jeden.**

---

## Řešení

> „Therefore: **Use the model as the backbone of a language.** Commit the team to **exercising that language relentlessly** in all communication within the team and in the code. Within a bounded context, use the same language in diagrams, writing, **and especially speech.** **Recognize that a change in the language is a change to the model.** Iron out difficulties by experimenting with alternative expressions, which reflect alternative models. **Then refactor the code, renaming classes, methods, and modules to conform to the new model.**"

Pět pokynů a každý je nepohodlný jinak:

| Pokyn | Co znamená v praxi |
| ----- | ------------------ |
| **Model jako páteř jazyka** | Model není diagram pro vývojáře; je to slovník, kterým mluví všichni |
| **Relentlessly** | I když je to zdlouhavé, i na schůzce, i v ticketu |
| **Especially speech** | Nejtěžší část — psaný text se dá opravit, řeč ne |
| **Změna jazyka = změna modelu** | Nové slovo není přejmenování, je to nové zjištění o doméně |
| **Then refactor the code** | Pojem se nezmění, dokud se nepřejmenují třídy |

```
              nalezeno      chybí
Before        1 z 6         Storno, Expedice, Reklamace, Dobropis, Rezervace
After         6 z 6         —

pokrytí:      Before 17 %  ·  After 100 %
```

### Překlad smí být na jednom místě, ne uvnitř

Čeština a anglický kód nejsou v rozporu — pokud je převod **jednoznačný a jediný**:

| Doména (česky) | Kód (anglicky) |
| -------------- | -------------- |
| Objednávka | `Order` |
| Storno | `Cancellation` |
| Expedice | `Dispatch` |
| Reklamace | `Complaint` |
| Dobropis | `CreditNote` |
| Rezervace | `Reservation` |

Tabulka je **jediné povolené místo, kde se překládá**. Uvnitř kódu se pak už nepřekládá nic — `Cancellation` je `Cancellation` všude.

Co překlad ničí, je varianta bez tabulky: tři vývojáři přeloží „storno" třemi způsoby a vznikne stav z dema:

```
Doména říká:      „storno"
Before říká:      abortOrder, revokeOrder, setStatusToInactive
After říká:       cancel(Cancellation)
```

### Pojmy, které vznikly u klávesnice

Druhá polovina problému nejsou chybějící doménová slova, ale **slova navíc, která doména nezná**:

```
processOrder()            co je „process“? doména zná expedici, fakturaci, storno…
createIssue()             „issue“ není doménový pojem — je to reklamace
createNegativeInvoice()   doména tomu říká dobropis, ne „záporná faktura“
lockStock()               „lock“ je technický pojem; doména zná rezervaci
```

Každé takové slovo je místo, kde se model **rozešel se skutečností**. `processOrder()` neznamená nic — a právě proto do něj časem přiteče všechno.

Praktický test: **přečti název metody doménovému expertovi.** Když se zeptá „a co to dělá", máš odpověď.

### Změna jazyka je změna modelu

Nejsilnější věta z celé definice a ta, kvůli které tenhle vzor není o pojmenovávání:

> „Recognize that **a change in the language is a change to the model.**"

Demo to hraje na konkrétní situaci — doména zjistí, že „storno" má ve skutečnosti dvě podoby:

```
· storno zákazníkem před expedicí
· zrušení obchodníkem kvůli nedostupnosti

                              důsledek
slovník                       dva pojmy místo jednoho
model                         dvě události, ne jedna
kód                           Cancellation → Cancellation + Withdrawal
databáze, API, dokumentace    přejmenovat všude
```

**Není to přejmenování. Je to zjištění, že model byl chudší než skutečnost** — a kód, který drží jedno slovo pro dvě věci, na tom rozdílu dřív nebo později ztroskotá.

Odtud plyne i to, proč Evans mluví o *„lively experimentation with language"*: hledání lepšího slova je hledání lepšího modelu.

---

## Účastníci

| Role | Odpovědnost |
| ---- | ----------- |
| **Doménoví experti** | Dodávají jazyk; namítají, když je pojem nepřesný |
| **Vývojáři** | Používají tentýž jazyk a hlídají nejednoznačnost, která rozbije návrh |
| **Model** | Páteř jazyka — pojmy modelu **jsou** slovník |
| **Kód** | Poslední místo, kde se jazyk musí projevit; jinak se rozejde |

Evans u toho zdůrazňuje obousměrnost: *„Domain experts should object to terms or structures that are awkward […]; developers should watch for ambiguity or inconsistency that will trip up design."*

---

## Implementace v PHP

### Slovník v repozitáři

```
docs/
    glossary.md        ← pojem, význam, anglický tvar v kódu
src/
    Order/
        Cancellation.php
        Dispatch.php
```

Slovník patří **do repozitáře a do [code review](../../../Processes/CodeReview/)**. Nový pojem je změna jako každá jiná — a když projde review, projde i jazykem.

Tenhle katalog to dělá stejně: [`Glossary.md`](../../Glossary.md) drží pojmy, které se opakují napříč dokumenty.

### Jméno metody je věta domény

```php
// Technický jazyk — doména těmhle slovům nerozumí
$order->setStatus(Status::CANCELLED);
$orderService->processOrder($id);
$stockService->lockStock($sku, 3);

// Doménový jazyk — expert to přečte a rozumí
$order->cancel(new Cancellation('zákazník si to rozmyslel'));
$order->dispatch(new Dispatch(carrier: 'PPL'));
$stock->reserve(new Reservation($sku, 3));
```

Rozdíl není kosmetický. `setStatus()` říká **jak** je to uložené; `cancel()` říká **co se stalo** — a jen to druhé jde ověřit u někoho, kdo doméně rozumí.

Souvisí to s [Tell, Don't Ask](../../Principles/ObjectDesign.md#tell-dont-ask): metoda pojmenovaná doménovým slovesem objektu něco říká, kdežto setter mu sahá do útrob.

### Kde se jednotný jazyk končí

Jazyk platí **uvnitř jednoho [ohraničeného kontextu](../BoundedContext/)**, ne napříč celou firmou. Evans to má přímo v definici: *„Within a bounded context, use the same language."*

„Objednávka" ve skladu a „objednávka" v účetnictví jsou dvě různé věci a snaha sjednotit je vyrobí pojem, který nesedí ani jednomu. Na hranici se překládá — a na to je [Anticorruption Layer](../AnticorruptionLayer/).

---

## Kdy použít

- ✅ **Vždy**, když má doména vlastní slovník — což je skoro vždy.
- ✅ Rozhovory s doménovými experty **končí nedorozuměním**.
- ✅ V kódu jsou pojmy, které **nikdo z byznysu nezná**.
- ✅ Chystáš se použít **ostatní vzory DDD** — tenhle je jejich předpokladem.
- ✅ Do týmu přicházejí noví lidé a **učí se dvě slovní zásoby** místo jedné.

## Kdy nepoužít

- ❌ **Doména je triviální.** CRUD nad číselníkem vlastní jazyk nemá a vymýšlet mu ho je práce navíc.
- ❌ **Nemáš přístup k doménovým expertům.** Jazyk vymyšlený vývojáři není jednotný jazyk, jen konzistentní pojmenování — což je fajn, ale něco jiného.
- ❌ **Snažíš se sjednotit jazyk napříč kontexty.** To vzor výslovně nedělá.

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| Jazyk platí v analýze, ale ne v kódu | Kód je „the most important product" — tam se to počítá | Přejmenovat třídy a metody |
| Tři jména pro jednu věc | Nikdo neví, jestli jsou to tři věci | Jedno slovo, jeden pojem |
| Jedno jméno pro dvě věci | Model je chudší než skutečnost | Rozdělit pojem i model |
| Technické pojmy v doméně (`process`, `manage`, `handle`) | Neznamenají nic a přiteče do nich všechno | Doménová slovesa |
| Slovník vznikne u vývojářů | Popisuje, jak je to implementované | Jazyk dodává doména |
| Sjednocování napříč kontexty | Vznikne pojem, který nesedí nikomu | Jazyk platí uvnitř [kontextu](../BoundedContext/) |
| Jazyk se drží v psaní, ne v řeči | Evans: **especially speech** | Používat i na schůzkách |
| Nový pojem se nepromítne do kódu | Za rok jsou dva jazyky znovu | Změna jazyka = refaktoring |
| Slovník mimo repozitář | Zastará dřív, než si toho někdo všimne | Do repozitáře, do review |

---

## V praxi

- **Event Storming** — workshop, jehož hlavním výstupem není diagram, ale právě jednotný jazyk.
- **Glosář v repozitáři** — nejjednodušší podoba; tenhle katalog má [vlastní](../../Glossary.md).
- **Názvy tříd čitelné pro byznys** — nejlevnější test kvality modelu, jaký existuje.
- **Přejmenovávací refaktoring v IDE** — technicky levný; drahé je rozhodnout, jak se to má jmenovat.
- **Doménové události pojmenované minulým časem** (`OrderCancelled`) — jazyk se promítá i do tvaru slov.

---

## Související patterny

| Pattern | Vztah |
| ------- | ----- |
| [Bounded Context](../BoundedContext/) | **Určuje hranice platnosti jazyka.** Jeden jazyk uvnitř jednoho kontextu — ne napříč firmou. |
| [Context Map](../ContextMap/) | Ukazuje, kde se jazyky potkávají a kde se tedy musí překládat. |
| [Anticorruption Layer](../AnticorruptionLayer/) | Místo, kde překlad mezi jazyky legitimně probíhá. |
| [Domain Vision Statement](../DomainVisionStatement/) | První text, ve kterém se jazyk projeví — nebo se ukáže, že tým žádný nemá. |
| [Entity](../Entity/) · [Value Object](../ValueObject/) · [Aggregate](../Aggregate/) | Stavební bloky, jejichž jména mají pocházet z jazyka, ne z technického žargonu. |
| [Domain Event](../DomainEvent/) | Události nesou jazyk nejvíc ze všeho — jejich jména čte i byznys. |
| [Tell, Don't Ask](../../Principles/ObjectDesign.md#tell-dont-ask) | Doménové sloveso místo setteru je totéž pravidlo z druhé strany. |
| [Conwayův zákon](../../Principles/ConwaysLaw.md) | Jazyk se láme přesně na organizačních hranicích — proto platí uvnitř kontextu, ne napříč firmou. |

---

## Vztah k principům

| Princip | Jak souvisí |
| ------- | ----------- |
| [Zviditelni implicitní](../../Principles/ObjectDesign.md#zviditelni-implicitní) | Pojem, který žije jen v hlavách, se stane součástí kódu. |
| [Vysoká soudržnost](../../Principles/CohesionAndCoupling.md#stupnice-soudržnosti) | Pojmy, které patří k sobě jazykově, obvykle patří k sobě i v kódu. |
| [Tell, Don't Ask](../../Principles/ObjectDesign.md#tell-dont-ask) | Jazyk domény se v kódu projeví slovesy, ne gettery a settery. |
| [KISS](../../Principles/Simplicity.md#kiss--keep-it-simple) | Jedno slovo pro jednu věc je nejjednodušší možný slovník. |

---

## Demo

```bash
php SoftwareDesign/DDD/UbiquitousLanguage/demo/run.php
```

Slovník e-shopu o šesti pojmech a dvě verze téhož kódu. Demo **spočítá, kolik doménových pojmů je v kódu k nalezení** — 17 % před, 100 % po. Ukáže, že jedna doménová věc má v původní verzi tři jména, vypíše pojmy, které vznikly u klávesnice a doména je nezná, a nakonec sehraje, co se stane, **když doména zjistí, že jedno slovo znamená dvě věci**: mění se slovník, model, kód i databáze — protože změna jazyka je změna modelu.

---

## Původ

|               |                                                   |
| ------------- | ------------------------------------------------- |
| **Zdroj**     | *Domain-Driven Design: Tackling Complexity in the Heart of Software* |
| **Autor**     | Eric Evans                                        |
| **Rok**       | 2003                                              |
| **Kategorie** | Základ DDD (kapitola 2)                           |
| **Obtížnost** | ●●●○○                                             |

Evans jí věnuje **druhou kapitolu — dřív, než popíše jediný stavební blok.** To pořadí je záměrné: entity, agregáty a repository jsou technické prostředky, kdežto jednotný jazyk je to, co z DDD dělá DDD. Bez něj zbude sada návrhových vzorů.

Kapitolu otevírá citátem z Lewise Carrolla o tom, jak se věta rozstříhá a znovu poskládá, „a na pořadí slov vůbec nezáleží" — ironie mířená právě na představu, že na slovech nesejde.

Obtížnost je trojka a je celá v lidech. Napsat slovník je práce na odpoledne; **udržet jazyk „relentlessly", jak Evans žádá, je práce na každý den** a naráží na tři věci:

- **V řeči se disciplína drží nejhůř.** Evans to zdůrazňuje slovem *especially* — a je to přesně ta část, kterou nelze vynutit nástrojem.
- **Vyžaduje to doménové experty**, kteří na to mají čas a chuť. Bez nich vznikne konzistentní pojmenování, ne jednotný jazyk.
- **Přejmenování v kódu je viditelná práce bez viditelného přínosu**, a proto se odkládá — až se odloží tak dlouho, že jazyk zase existuje ve dvou verzích.

Za povšimnutí stojí, že tenhle vzor jako jediný z celého DDD **nemá žádnou technickou podobu.** Nedá se implementovat, nedá se vygenerovat a nedá se odškrtnout jako hotový. Dá se jen dělat — nebo nedělat.

---

## Zdroje

- Eric Evans: *Domain-Driven Design*, Addison-Wesley, 2003 — kapitola 2, *Communication and the Use of Language*
- Eric Evans: [*Domain-Driven Design Reference*](https://www.domainlanguage.com/wp-content/uploads/2016/05/DDD_Reference_2015-03.pdf) (PDF, 2015) — souhrn definic, pod licencí CC BY 4.0

---

<details>
<summary>Metadata patternu</summary>

```yaml
name: Ubiquitous Language
name_cs: Jednotný jazyk
category: Základ DDD
source: DDD – Domain-Driven Design
authors: Eric Evans
year: 2003
difficulty: 3
tags: [jazyk, model, komunikace, pojmenování, slovník]
principles: [MakeImplicitExplicit, CohesionAndCoupling, TellDontAsk, KISS]
related: [BoundedContext, ContextMap, AnticorruptionLayer, DomainVisionStatement, Entity, ValueObject, Aggregate, DomainEvent]
status: done
```

</details>
