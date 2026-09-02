# Design Patterns — týmová příručka

Praktický katalog návrhových vzorů s ukázkami v PHP.

**Pro koho:**

- **Juniorům** — vysvětlení od problému, ne od definice: co pattern řeší, kdy po něm sáhnout a kdy ne.
- **Seniorům** — rychlé připomenutí struktury, účastníků a typických chyb. Když si po roce nejste jistí, jestli je to Strategy nebo State.

**Co tady najdeš u každého patternu:** původ (odkud pochází, kdo ho popsal, kdy) · problém, který řeší · řešení a diagram · funkční PHP kód · kdy použít a **kdy nepoužít** · časté chyby · odkazy na příbuzné patterny.

---

## Jak v tom hledat

| Vím... | Jdi na |
| ------ | ------ |
| ...jaký mám problém | [Index podle problému](#index-podle-problému) |
| ...že začínám a nevím, kde píchnout | [Kudy začít](#kudy-začít) |
| ...jak se pattern jmenuje | [Sbírky](#sbírky) — u každé je tabulka jejích patternů |
| ...že chci vědět, proč patterny vůbec existují | [Principy](#principy) |
| ...co znamená nějaký pojem | [Slovníček](Glossary.md) |

---

## Principy

Patterny jsou konkrétní řešení; **principy jsou měřítko, podle kterého se pozná, jestli je návrh dobrý**. Většina patternů tady existuje proto, že řeší porušení některého z nich — proto na ně odkazují a nevysvětlují si je pokaždé znovu.

| Soubor | Co obsahuje | Stav |
| ------ | ----------- | ---- |
| [**Soudržnost a provázanost**](Principles/CohesionAndCoupling.md) | Měřítko pod vším ostatním — co spolu souvisí ať je pohromadě, co ne ať na sobě nezávisí | ✅ |
| [**SOLID**](Principles/SOLID.md) | Jak rozdělit odpovědnosti — SRP, OCP, LSP, ISP, DIP | ✅ |
| [**Jednoduchost**](Principles/Simplicity.md) | Kolik kódu psát a kdy — KISS, YAGNI, DRY, pravidlo tří | ✅ |
| [**Objektový návrh**](Principles/ObjectDesign.md) | Jak spolu objekty mluví — Tell Don't Ask, Demeter, kompozice před dědičností, CQS, Fail Fast | ✅ |

<sub>Rozcestník i s vysvětlením členění: [Principles/README.md](Principles/)</sub>

---

## Slovníček

Pojmy, které se v katalogu opakují napříč patterny, ale nemají vlastní dokument — **[Glossary.md](Glossary.md)**.

[idempotence](Glossary.md#idempotence) · [neměnnost](Glossary.md#neměnnost-immutability) · [invariant](Glossary.md#invariant) · [eventuální konzistence](Glossary.md#eventuální-konzistence) · [DTO](Glossary.md#dto--data-transfer-object) · [bezstavovost](Glossary.md#bezstavovost-stateless) · [N+1](Glossary.md#n1) · [persistence](Glossary.md#persistence) · [hydratace a rekonstrukce](Glossary.md#hydratace-a-dehydratace) · [časová vazba](Glossary.md#časová-vazba-temporal-coupling)

<sub>Pojmy, které mají vlastní pattern (agregát, port, kompenzace…), se vysvětlují tam — slovníček na ně [odkazuje](Glossary.md#pojmy-které-mají-vlastní-dokument).</sub>

---

## Sbírky

Patterny jsou uspořádané podle **původu** — podle knihy nebo autora, kde byly poprvé popsané. Sbírka, která má vlastní členění (jako GoF), ho má i tady ve složkách; ostatní jsou plocho.

### [GoF — Design Patterns](GoF/)

> Gamma, Helm, Johnson, Vlissides · **1994** · *Design Patterns: Elements of Reusable Object-Oriented Software*

23 základních objektových vzorů. Kánon, na který odkazuje skoro všechno ostatní. Dělí se na [Creational](GoF/Creational/) (vytváření objektů), [Structural](GoF/Structural/) (skládání objektů) a [Behavioral](GoF/Behavioral/) (komunikace mezi objekty).

| Pattern | Kategorie | K čemu to je | Obtížnost |
| ------- | --------- | ------------ | --------- |
| [Chain of Responsibility](GoF/Behavioral/ChainOfResponsibility/) | Behavioral | Požadavek putuje řetězem, dokud ho někdo nevyřídí; základ middleware | ●●●○○ |
| [Strategy](GoF/Behavioral/Strategy/) | Behavioral | Zaměnitelné algoritmy za jedním rozhraním — místo `if`/`switch` na typ | ●●○○○ |
| [State](GoF/Behavioral/State/) | Behavioral | Objekt mění chování podle svého stavu; zakázané přechody nejde přehlédnout | ●●●○○ |
| [Adapter](GoF/Structural/Adapter/) | Structural | Překlad cizího rozhraní na to, které očekává tvůj kód | ●○○○○ |
| [Decorator](GoF/Structural/Decorator/) | Structural | Přidání chování obalením — cache, log, měření bez zásahu do původní třídy | ●●○○○ |
| [Composite](GoF/Structural/Composite/) | Structural | Strom, kde se s listem zachází stejně jako s celou větví | ●●○○○ |
| [Factory Method](GoF/Creational/FactoryMethod/) | Creational | Vytvoření objektu má jméno a hlídá si pravidla | ●○○○○ |
| [Observer](GoF/Behavioral/Observer/) | Behavioral | Objekt oznámí změnu všem, kdo o to stáli — a nezná je | ●●○○○ |
| [Singleton](GoF/Creational/Singleton/) | Creational | Jediná instance dostupná odkudkoli — **a proč ho skoro nikdy nechceš** | ●○○○○ |

<sub>Kompletní katalog všech 23 patternů včetně nezpracovaných: [GoF/README.md](GoF/)</sub>

### [Object Calisthenics](ObjectCalisthenics/)

> Jeff Bay · **2008** · *The ThoughtWorks Anthology*

Devět záměrně přísných pravidel objektového návrhu, původně jako **cvičení**, ne jako předpis pro produkci. Dvě z nich se osamostatnila a dnes fungují jako plnohodnotné patterny.

| Pattern | K čemu to je | Obtížnost |
| ------- | ------------ | --------- |
| [First Class Collection](ObjectCalisthenics/FirstClassCollection/) | Pole zabalené do vlastní třídy s doménovými metodami a pravidly skupiny | ●●○○○ |

<sub>Všech devět pravidel a co se z nich uchytilo: [ObjectCalisthenics/README.md](ObjectCalisthenics/)</sub>

### [PoEAA — Enterprise patterny](PoEAA/)

> Martin Fowler · **2002** · *Patterns of Enterprise Application Architecture*

Vzory pro aplikace nad databází a s doménovou logikou — Repository, Unit of Work, Data Mapper, Service Layer, Identity Map. Většinu z nich ti dnes dává ORM hotové; znát je ale musíš, jinak nepochopíš, co pod tebou dělá.

| Pattern | K čemu to je | Obtížnost |
| ------- | ------------ | --------- |
| [Repository](PoEAA/Repository/) | Kolekcí se tvářící rozhraní nad persistencí | ●●●○○ |
| [Service Layer](PoEAA/ServiceLayer/) | Aplikační vrstva — jedna třída na jeden use-case, orchestrace bez rozhodování | ●●○○○ |
| [Data Mapper](PoEAA/DataMapper/) | Překlad objekt ↔ řádek; doména ani schéma o sobě nevědí | ●●●○○ |
| [Optimistic Offline Lock](PoEAA/OptimisticOfflineLock/) | Souběžné změny se poznají podle verze — místo aby se jim předcházelo | ●●○○○ |
| [Unit of Work](PoEAA/UnitOfWork/) | Změny se sbírají v paměti a zapíší najednou — co dělá Doctrine `flush()` | ●●●○○ |

<sub>Celý katalog (Data Mapper, Unit of Work, Identity Map, …): [PoEAA/README.md](PoEAA/)</sub>

### [DDD — Domain-Driven Design](DDD/)

> Eric Evans · **2003** · *Domain-Driven Design* (+ Vaughn Vernon, 2013)

Aggregate, Entity, Value Object, Domain Event, Bounded Context. Ne úplně „design patterny“ v gangofourském smyslu, ale stejný typ znalosti — pojmenované řešení opakujícího se problému.

| Pattern | Kategorie | K čemu to je | Obtížnost |
| ------- | --------- | ------------ | --------- |
| [Entity](DDD/Entity/) | Taktický | Objekt s identitou, která přežije změnu všech atributů | ●●○○○ |
| [Value Object](DDD/ValueObject/) | Taktický | Hodnota bez identity — vlastní typ místo `string` a `int` | ●●○○○ |
| [Aggregate](DDD/Aggregate/) | Taktický | Hranice konzistence — jediný vstup a pravidla platná pro celek | ●●●●○ |
| [Domain Event](DDD/DomainEvent/) | Taktický | Fakt, který se stal — reakce se přihlašují samy, use-case o nich neví | ●●●●○ |
| [Domain Service](DDD/DomainService/) | Taktický | Doménová operace, která nepatří žádné entitě — bez transakcí a databáze | ●●○○○ |
| [Specification](DDD/Specification/) | Taktický | Doménové pravidlo jako objekt — pojmenovatelné, testovatelné, skládatelné | ●●●○○ |
| [Bounded Context](DDD/BoundedContext/) | Strategický | Model platí jen uvnitř hranice; totéž slovo smí za ní znamenat jinou věc | ●●●●○ |
| [Context Map](DDD/ContextMap/) | Strategický | Vztahy mezi kontexty — kdo se komu musí přizpůsobit | ●●●○○ |
| [Anticorruption Layer](DDD/AnticorruptionLayer/) | Strategický | Překladová vrstva, která nepustí cizí model do tvojí domény | ●●●○○ |

<sub>Taktické i strategické stavební bloky: [DDD/README.md](DDD/)</sub>

### [EIP — Integrační patterny](EIP/) ⬜

> Hohpe, Woolf · **2003** · *Enterprise Integration Patterns*

Vzory pro messaging a komunikaci mezi službami — Scatter-Gather, Message Router, Publish-Subscribe, Idempotent Receiver, Dead Letter Channel. Zatím žádný nemá vlastní dokument, ale **několik z nich už v katalogu popsané je** — dostaly se tam z jiné strany a [EIP/README.md](EIP/) říká kudy.

### [Architecture](Architecture/)

> různí autoři, různé roky

Vzory bez jedné mateřské knihy, které se netýkají jedné třídy, ale **tvaru celé aplikace** — kudy vedou závislosti a kde jsou hranice. Společné mají pozorování, že byznys logika stárne mnohem pomaleji než frameworky a databáze okolo ní.

| Pattern | Autor, rok | K čemu to je | Obtížnost |
| ------- | ---------- | ------------ | --------- |
| [Ports & Adapters](Architecture/PortsAndAdapters/) | Cockburn, 2005 | Jádro nezávislé na okolí; závislosti míří dovnitř | ●●●●○ |
| [Rules Engine](Architecture/RulesEngine/) | Forgy 1979, Fowler 2009 | Byznysová pravidla jako seznam objektů se strategií a auditní stopou | ●●●●○ |
| [CQRS](Architecture/CQRS/) | Meyer 1988, Young 2010 | Oddělený model pro zápis a pro čtení — každý optimalizovaný na své | ●●●●○ |
| [Service Composition](Architecture/ServiceComposition/) | Peltz 2003, Erl 2009 | Poskládá **čtení** z víc kontextů do jednoho celku | ●●●○○ |
| [Saga](Architecture/Saga/) | Garcia-Molina & Salem 1987 | **Zápis** přes víc kontextů — kroky s kompenzacemi místo transakce | ●●●●○ |

<sub>Plánované (Clean Architecture, Onion, Event Sourcing): [Architecture/README.md](Architecture/)</sub>

<sub>⬜ plánováno · 🚧 rozpracováno · ✅ hotovo</sub>

---


## Index podle problému

Obrácený rejstřík: začni u toho, co tě pálí.

| Mám problém | Zvaž |
| ----------- | ---- |
| Rozrostlý `if`/`switch`, který se větví podle typu, a přibývají do něj další větve | [Strategy](GoF/Behavioral/Strategy/) |
| Potřebuju za běhu měnit chování objektu podle konfigurace nebo vstupu | [Strategy](GoF/Behavioral/Strategy/) |
| Chci algoritmus otestovat izolovaně, ale je zadrátovaný uvnitř velké třídy | [Strategy](GoF/Behavioral/Strategy/) |
| Entita má veřejné pole a stejný `array_map` nad ním najdu na pěti místech | [First Class Collection](ObjectCalisthenics/FirstClassCollection/) |
| Z typu `array` nepoznám, co je uvnitř — věřím jen PHPDoc komentáři | [First Class Collection](ObjectCalisthenics/FirstClassCollection/) |
| Pravidlo o skupině (limit počtu, žádné duplicity) se hlídá na jednom místě a jinde se zapomnělo | [First Class Collection](ObjectCalisthenics/FirstClassCollection/) |
| Stejnou validaci (e-mail, IČO, PSČ) mám na třech místech a pokaždé trochu jinak | [Value Object](DDD/ValueObject/) |
| V signatuře jde prohodit dva argumenty stejného typu a nikdo si toho nevšimne | [Value Object](DDD/ValueObject/) |
| Počítám s penězi přes `float` nebo sčítám částky v různých měnách | [Value Object](DDD/ValueObject/) |
| Tutéž podmínku o třech částech mám na třech místech a jedna z kopií je zastaralá | [Specification](DDD/Specification/) |
| Pravidlo má jméno na poradě, ale v kódu ho nenajdu — je rozpuštěné v `if` | [Specification](DDD/Specification/) |
| Musím uživateli říct, **proč** neprošel, a z `&&` nezjistím která část selhala | [Specification](DDD/Specification/) |
| Unit test doménové logiky potřebuje běžící databázi | [Ports & Adapters](Architecture/PortsAndAdapters/) |
| Doménová třída importuje `Doctrine\…` nebo `GuzzleHttp\…` | [Ports & Adapters](Architecture/PortsAndAdapters/) |
| Vím o repository interface, ale netuším, co je „řídicí“ a „řízený“ port | [Ports & Adapters](Architecture/PortsAndAdapters/#dvě-strany-na-jednu-se-zapomíná) |
| Výměna knihovny znamená sáhnout do dvaceti souborů napříč aplikací | [Ports & Adapters](Architecture/PortsAndAdapters/) |
| Tatáž operace existuje dvakrát — jednou pro HTTP, podruhé pro frontu | [Ports & Adapters](Architecture/PortsAndAdapters/) |
| SQL nebo `createQueryBuilder()` mám v use-case, v controlleru i ve službě | [Repository](PoEAA/Repository/) |
| Tentýž dotaz je na pěti místech a jedna kopie zapomněla na podmínku | [Repository](PoEAA/Repository/) |
| Entita má `id === null`, dokud neproběhne `flush()`, a půlka kódu s tím počítá | [Repository](PoEAA/Repository/) |
| Na otázku „jaké vlastně máme slevy?“ neumí nikdo odpovědět bez čtení kódu | [Rules Engine](Architecture/RulesEngine/) |
| Na pořadí `if`ů záleží, ale nikde není napsané proč | [Rules Engine](Architecture/RulesEngine/) |
| Musím zpětně zdůvodnit, proč konkrétní zákazník dostal konkrétní cenu | [Rules Engine](Architecture/RulesEngine/) |
| Odesílatel obsahuje `if`, kterým vybírá zpracovatele — a tím zná všechny | [Chain of Responsibility](GoF/Behavioral/ChainOfResponsibility/) |
| Na začátku metody se hromadí kontroly: validace, oprávnění, limity, pak teprve práce | [Chain of Responsibility](GoF/Behavioral/ChainOfResponsibility/) |
| Potřebuju obalit zpracování logováním, měřením času nebo transakcí | [Chain of Responsibility](GoF/Behavioral/ChainOfResponsibility/) |
| Nevím, v jakém pořadí mají v API běžet CORS, rate limit, autentizace a validace | [Chain of Responsibility](GoF/Behavioral/ChainOfResponsibility/) |
| `switch ($this->status)` mám v každé metodě objektu a na jednu se vždy zapomene | [State](GoF/Behavioral/State/) |
| Nikde není napsané, jaké přechody stavů jsou vlastně dovolené | [State](GoF/Behavioral/State/) |
| Frontend nabídne tlačítko pro operaci, která pak na backendu spadne | [State](GoF/Behavioral/State/) |
| Výpis dvaceti řádků v administraci načte stovky agregátů a 99 % dat zahodí | [CQRS](Architecture/CQRS/) |
| Přidání sloupce do tabulky v administraci mě nutí sáhnout do doménového modelu | [CQRS](Architecture/CQRS/) |
| Entita má gettery, které v doméně nikdo nepoužívá — jsou tam jen pro šablonu | [CQRS](Architecture/CQRS/) |
| Entita `Customer` má čtyřicet vlastností a většina je `nullable` | [Bounded Context](DDD/BoundedContext/) |
| Dvě oddělení říkají „zákazník“ a myslí tím něco jiného | [Bounded Context](DDD/BoundedContext/) |
| Chystáme dělit monolit a nevíme kudy | [Bounded Context](DDD/BoundedContext/) |
| Na otázku „když tohle změním, koho rozbiju?“ neumí odpovědět nikdo | [Context Map](DDD/ContextMap/) |
| Existuje sdílená knihovna, na které závisí všichni a nikdo ji nevlastní | [Context Map](DDD/ContextMap/) |
| Cizí datový model prosákl do naší domény a nikdo neví kdy | [Context Map](DDD/ContextMap/) |
| Doménové třídy mají pole pojmenovaná podle sloupců cizího systému | [Anticorruption Layer](DDD/AnticorruptionLayer/) |
| Mám v kódu `if ($status === '03')` s komentářem, co to znamená u nich | [Anticorruption Layer](DDD/AnticorruptionLayer/) |
| Cizí systém nejde vyměnit, protože je propletený úplně vším | [Anticorruption Layer](DDD/AnticorruptionLayer/) |
| Třída má jen gettery a settery a logika k ní bydlí v `XService` | [Entity](DDD/Entity/) |
| Uložená úroveň / stav se rozešel s hodnotou, ze které se počítá | [Entity](DDD/Entity/) |
| Každá entita má vlastní repository, i ta, co bez „matky“ nedává smysl | [Aggregate](DDD/Aggregate/) |
| Pravidlo „součet položek nesmí přesáhnout limit“ nejde nikde vynutit | [Aggregate](DDD/Aggregate/) |
| Nikdo neví, co má obalit transakce | [Aggregate](DDD/Aggregate/) |
| Use-case má šest závislostí: mailer, sklad, statistiky, audit, cache… | [Domain Event](DDD/DomainEvent/) |
| E-mail se posílá uvnitř transakce a odejde i po jejím rollbacku | [Domain Event](DDD/DomainEvent/) |
| Potřebuju změnit jiný agregát, ale nesmím ho měnit ve stejné transakci | [Domain Event](DDD/DomainEvent/) |
| `OrderService` má osm závislostí a čtrnáct metod, žádná nepotřebuje víc než tři | [Service Layer](PoEAA/ServiceLayer/) |
| V controlleru mám transakci, oprávnění i rozhodování o doméně | [Service Layer](PoEAA/ServiceLayer/) |
| Nevím, jestli pravidlo patří do use-case, nebo do entity | [Service Layer](PoEAA/ServiceLayer/) |
| Nevím, jestli má dotaz dostat vlastní handler, nebo volat čtecí službu rovnou | [Service Layer](PoEAA/ServiceLayer/) |
| Máme command bus a nikdo neví, kam z controlleru vede volání | [Service Layer](PoEAA/ServiceLayer/) |
| Pravidlo se týká dvou agregátů a ani jeden není jeho vlastníkem | [Domain Service](DDD/DomainService/) |
| Vznikají mi třídy `XManager`, `XHelper`, `XUtils` a nikdo neví, co je uvnitř | [Domain Service](DDD/DomainService/) |
| Entita sahá do cizího agregátu a mění ho | [Domain Service](DDD/DomainService/) |
| Nevím, jestli koordinace patří do domény, nebo do aplikační vrstvy | [Domain Service](DDD/DomainService/) |
| Jedna změna si vynutí úpravu v pěti souborech | [Soudržnost a provázanost](Principles/CohesionAndCoupling.md) |
| Mám třídu `Utils` / `Helper` / `Manager` a nikdo neví, co je uvnitř | [Soudržnost a provázanost](Principles/CohesionAndCoupling.md#stupnice-soudržnosti) |
| Metoda má `bool` parametr, který mění, co uvnitř dělá | [Soudržnost a provázanost](Principles/CohesionAndCoupling.md#řídicí-provázanost-protože-ta-je-nejzákeřnější) |
| Doménová třída má `save()` nebo drží spojení do databáze | [Data Mapper](PoEAA/DataMapper/) |
| Model má tvar tabulky, ne domény — samé skaláry, žádné hodnoty | [Data Mapper](PoEAA/DataMapper/) |
| Nevím, jestli zvolit Doctrine, nebo Eloquent | [Data Mapper](PoEAA/DataMapper/#data-mapper-vs-active-record) |
| „Někomu zmizely změny“ a nejde to reprodukovat | [Optimistic Offline Lock](PoEAA/OptimisticOfflineLock/) |
| Dva lidé editují týž záznam a druhý přepíše prvního beze stopy | [Optimistic Offline Lock](PoEAA/OptimisticOfflineLock/) |
| Formulář se vyplňuje pět minut a transakce tak dlouho držet nejde | [Optimistic Offline Lock](PoEAA/OptimisticOfflineLock/) |
| Nechápu, co vlastně dělá Doctrine `flush()` a proč tam není `save()` | [Unit of Work](PoEAA/UnitOfWork/) |
| Jeden objekt se během operace uloží třikrát, protože se třikrát změnil | [Unit of Work](PoEAA/UnitOfWork/) |
| Operace spadla v půlce a část změn už je v databázi | [Unit of Work](PoEAA/UnitOfWork/) |
| Do fungující třídy přibývá cache, logování a měření, které s její prací nesouvisí | [Decorator](GoF/Structural/Decorator/) |
| Mám `CachedFooRepository`, `LoggedFooRepository` a teď potřebuju obojí naráz | [Decorator](GoF/Structural/Decorator/) |
| Chci rozšířit třídu, která je `final` nebo z cizí knihovny | [Decorator](GoF/Structural/Decorator/) |
| Dvě knihovny dělají totéž, ale každá jinak — nejde je porovnat | [Adapter](GoF/Structural/Adapter/) |
| V kódu mám `if ($provider === '…')` a pod ním převod jednotek | [Adapter](GoF/Structural/Adapter/) |
| Doménový kód ví, že „ten druhý dodavatel počítá v dolarech“ | [Adapter](GoF/Structural/Adapter/) |
| Při procházení stromu mám všude `if ($node instanceof Category)` | [Composite](GoF/Structural/Composite/) |
| Hloubka kategorií je zadrátovaná a přidat čtvrtou úroveň znamená přepsat metody | [Composite](GoF/Structural/Composite/) |
| Z `new Money(129000)` nepoznám, jestli jsou to koruny nebo haléře | [Factory Method](GoF/Creational/FactoryMethod/) |
| Konstruktor má šest nepovinných parametrů a půlku z nich předávám `null` | [Factory Method](GoF/Creational/FactoryMethod/) |
| Objekt jde vytvořit v neplatném stavu, protože validace je jinde než konstruktor | [Factory Method](GoF/Creational/FactoryMethod/) |
| Objekt, který se mění, zná mailer, cache i statistiky | [Observer](GoF/Behavioral/Observer/) |
| Nevím, jestli použít Observer, nebo doménovou událost | [Observer](GoF/Behavioral/Observer/#observer-nebo-doménová-událost) |
| V kódu mám `Config::getInstance()` a nejde napsat test s jinou konfigurací | [Singleton](GoF/Creational/Singleton/) |
| Testy mi selhávají podle pořadí, ve kterém běží | [Singleton](GoF/Creational/Singleton/) |
| Třída má prázdný konstruktor, ale závislosti si tahá zevnitř metod | [Singleton](GoF/Creational/Singleton/) |
| Frontend volá pět endpointů a skládá si z nich jednu stránku sám | [Service Composition](Architecture/ServiceComposition/) |
| Mám kód, který koordinuje víc domén, a nepatří do žádné z nich | [Service Composition](Architecture/ServiceComposition/) |
| Operace přes tři kontexty spadla v půlce a nikdo první dva kroky nevrátil | [Saga](Architecture/Saga/) |
| Opakované doručení zprávy vyrobilo zákazníkovi druhý dobropis | [Saga](Architecture/Saga/) |
| Nevím, v jakém stavu je rozpracovaný proces přes víc služeb | [Saga](Architecture/Saga/) |

---

## Kudy začít

Doporučené pořadí, pokud je pro tebe téma nové. Každý další pattern staví na předchozím.

1. [First Class Collection](ObjectCalisthenics/FirstClassCollection/) — nejjednodušší vstup: jedna třída navíc a hned je vidět, co je zapouzdření dobré.
2. [Value Object](DDD/ValueObject/) — tatáž myšlenka o úroveň níž: co zapouzdření udělá s jedinou hodnotou.
3. [Strategy](GoF/Behavioral/Strategy/) — kompozice a polymorfismus na malém kódu; odsud vede cesta k většině ostatních patternů.

---

## Přidání nového patternu

Postup, šablona a checklist: **[`_template/README.md`](_template/README.md)**.

Ve zkratce: zkopíruj `_template/PATTERN.md` jako `README.md` do složky patternu, vyplň, a **aktualizuj přehledové tabulky v tomhle souboru** i v README nadřazených složek.

---

## Konvence

- **Obsah česky** (texty, popisy, komentáře v kódu), **kód anglicky** (názvy tříd, metod, proměnných, složek).
- **PHP 8.3+**, `declare(strict_types=1)`, bez frameworků — ukázky mají jít zkopírovat a spustit.
- Složitější implementace mají složku `demo/` se spustitelným příkladem: `php <cesta>/demo/run.php`.
- Kde to jde, používáme **jeden doménový příklad napříč patterny** (e-shop / objednávky), aby se nemusel při každém patternu chytat nový kontext.
- Pojmy, které se opakují napříč patterny a nemají vlastní dokument, vysvětluje **[slovníček](Glossary.md)**.
