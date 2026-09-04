# DDD — Domain-Driven Design

> Eric Evans · **2003** · *Domain-Driven Design: Tackling Complexity in the Heart of Software*

## Původ

Evans nepsal knihu o patternech, ale o tom, **jak se doménová znalost dostane do kódu a jak tam zůstane**. Tvrdil, že hlavní zdroj složitosti není technologie, ale doména sama — a že většina projektů selhává na tom, že model v hlavách lidí a model v kódu se rozejdou.

Vedle toho ale kniha obsahuje sadu konkrétních stavebních bloků, které se ujaly samostatně a používají se i tam, kde se DDD jako metodika nedělá. Právě ty tady dokumentujeme.

Vaughn Vernon je v *Implementing Domain-Driven Design* (2013) rozpracoval do implementační podoby; kde se s Evansem liší, uvádíme to u konkrétního patternu.

## Členění

Evans dělí knihu na **taktický** návrh (jak vypadá kód uvnitř jednoho modelu) a **strategický** (jak spolu velké modely souvisejí). Ve složkách to nekopírujeme, odlišujeme to jen tady v katalogu.

U strategických vzorů se ukázalo, že demo smysl má — jen jiné: [Bounded Context](BoundedContext/) ukazuje tentýž pojem ve třech modelech a překlad mezi nimi, [Context Map](ContextMap/) drží mapu jako data, ze kterých vygeneruje diagram a upozorní na rizikové vztahy.

### Taktické stavební bloky

| Pattern | K čemu | Obtížnost | Stav |
| ------- | ------ | --------- | ---- |
| [**Value Object**](ValueObject/) | Hodnota bez identity — vlastní typ místo `string` a `int` | ●●○○○ | ✅ |
| [**Entity**](Entity/) | Objekt s identitou, která přežije změnu všech atributů | ●●○○○ | ✅ |
| [**Aggregate**](Aggregate/) | Skupina objektů se společným kořenem a hranicí konzistence | ●●●●○ | ✅ |
| [Repository](../PoEAA/Repository/) | Kolekce agregátů, za kterou se schová persistence. Evans ho popsal rok po Fowlerovi, proto ho vedeme v [PoEAA](../PoEAA/) — rozdíl obou pojetí je rozebraný tam. | ●●●○○ | ✅ |
| [**Domain Event**](DomainEvent/) | Fakt, který se v doméně stal a jiné části na něj reagují | ●●●●○ | ✅ |
| [**Factory**](Factory/) | Vytvoření celého agregátu najednou, s vynucenými invarianty | ●●○○○ | ✅ |
| [Application Service](../PoEAA/ServiceLayer/) | Orchestrace jedné operace aplikace. Evans ji popsal rok po Fowlerově *Service Layer*, proto ji vedeme v [PoEAA](../PoEAA/) — rozdíl proti **domain service** je rozebraný tam. | ●●○○○ | ✅ |
| [**Domain Service**](DomainService/) | Doménová operace, která nepatří žádné entitě | ●●○○○ | ✅ |
| [**Specification**](Specification/) | Doménové pravidlo vytažené do samostatného objektu | ●●●○○ | ✅ |

### Strategický návrh

| Pattern | K čemu | Obtížnost | Stav |
| ------- | ------ | --------- | ---- |
| [**Bounded Context**](BoundedContext/) | Hranice, uvnitř které mají pojmy jediný význam | ●●●●○ | ✅ |
| [**Context Map**](ContextMap/) | Vztahy mezi kontexty — kdo se komu přizpůsobuje | ●●●○○ | ✅ |
| [**Anticorruption Layer**](AnticorruptionLayer/) | Překladová vrstva chránící model před cizím | ●●●○○ | ✅ |
| [**Ubiquitous Language**](UbiquitousLanguage/) | Jeden jazyk pro doménu i kód — základ, na kterém stojí zbytek DDD | ●●●○○ | ✅ |

### Destilace

Kapitola 15 knihy — jak z modelu vydestilovat to, co je na něm cenné, a zbavit to všeho ostatního. **Pořadí v tabulce je Evansovo a je záměrné:** první vzory jsou levné (rozhodnutí, stránka textu, značky), poslední jsou zásahy do celého modelu. Sahej po nich v tomhle pořadí — a jen dokud to předchozí nestačí.

| Pattern | K čemu | Obtížnost | Stav |
| ------- | ------ | --------- | ---- |
| [**Core Domain**](CoreDomain/) | Pojmenování toho, čím se produkt liší — a kam tedy patří nejlepší lidé | ●●○○○ | ✅ |
| [**Generic Subdomains**](GenericSubdomains/) | Vytěsnění obecných částí; nejdřív zvaž, jestli to nejde koupit | ●●○○○ | ✅ |
| [**Domain Vision Statement**](DomainVisionStatement/) | Jedna stránka o tom, co je jádro a jakou hodnotu přináší | ●○○○○ | ✅ |
| [**Highlighted Core**](HighlightedCore/) | Označení prvků jádra přímo v modelu, ať je to poznat na první pohled | ●●○○○ | ✅ |
| [**Cohesive Mechanism**](CohesiveMechanism/) | Složitý výpočet do vlastního rámce — doména říká „co“, mechanismus řeší „jak“ | ●●●○○ | ✅ |
| [**Segregated Core**](SegregatedCore/) | Strukturální oddělení jádra od podpůrných částí | ●●●●○ | ✅ |
| [**Abstract Core**](AbstractCore/) | Abstrakce vyjadřující interakci mezi moduly, ve vlastním modulu | ●●●●○ | ✅ |

<sub>⬜ plánováno · 🚧 rozpracováno · ✅ hotovo</sub>

## Zdroje

- Eric Evans: *Domain-Driven Design: Tackling Complexity in the Heart of Software*, Addison-Wesley, 2003
- Vaughn Vernon: *Implementing Domain-Driven Design*, Addison-Wesley, 2013
