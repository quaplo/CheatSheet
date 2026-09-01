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
| ...co znamená pojem ze sekce „U nás“ | [Slovníček](Glossary.md) |

---

## Principy

Patterny jsou konkrétní řešení; **principy jsou měřítko, podle kterého se pozná, jestli je návrh dobrý**. Většina patternů tady existuje proto, že řeší porušení některého z nich — proto na ně odkazují a nevysvětlují si je pokaždé znovu.

| Soubor | Co obsahuje | Stav |
| ------ | ----------- | ---- |
| [**SOLID**](Principles/SOLID.md) | Jak rozdělit odpovědnosti — SRP, OCP, LSP, ISP, DIP | ✅ |
| [**Jednoduchost**](Principles/Simplicity.md) | Kolik kódu psát a kdy — KISS, YAGNI, DRY, pravidlo tří | ✅ |
| [**Objektový návrh**](Principles/ObjectDesign.md) | Jak spolu objekty mluví — Tell Don't Ask, Demeter, kompozice před dědičností, CQS, Fail Fast | ✅ |

<sub>Rozcestník i s vysvětlením členění: [Principles/README.md](Principles/)</sub>

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

Vzory pro aplikace nad databází a s doménovou logikou — Repository, Unit of Work, Data Mapper, Service Layer, Identity Map. Tohle je vrstva, ve které se v našich službách pohybujeme denně. Většinu z nich ti dnes dává ORM hotové; znát je ale musíš, jinak nepochopíš, co pod tebou dělá.

| Pattern | K čemu to je | Obtížnost |
| ------- | ------------ | --------- |
| [Repository](PoEAA/Repository/) | Kolekcí se tvářící rozhraní nad persistencí | ●●●○○ |

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
| [Specification](DDD/Specification/) | Taktický | Doménové pravidlo jako objekt — pojmenovatelné, testovatelné, skládatelné | ●●●○○ |
| [Bounded Context](DDD/BoundedContext/) | Strategický | Model platí jen uvnitř hranice; totéž slovo smí za ní znamenat jinou věc | ●●●●○ |
| [Context Map](DDD/ContextMap/) | Strategický | Vztahy mezi kontexty — kdo se komu musí přizpůsobit | ●●●○○ |
| [Anticorruption Layer](DDD/AnticorruptionLayer/) | Strategický | Překladová vrstva, která nepustí cizí model do tvojí domény | ●●●○○ |

<sub>Taktické i strategické stavební bloky: [DDD/README.md](DDD/)</sub>

### EIP — Integrační patterny ⬜

> Hohpe, Woolf · **2003** · *Enterprise Integration Patterns*

Vzory pro messaging a komunikaci mezi službami — Message Router, Publish-Subscribe, Idempotent Receiver, Dead Letter Channel.

### [Architecture](Architecture/)

> různí autoři, různé roky

Vzory bez jedné mateřské knihy, které se netýkají jedné třídy, ale **tvaru celé aplikace** — kudy vedou závislosti a kde jsou hranice. Společné mají pozorování, že byznys logika stárne mnohem pomaleji než frameworky a databáze okolo ní.

| Pattern | Autor, rok | K čemu to je | Obtížnost |
| ------- | ---------- | ------------ | --------- |
| [Ports & Adapters](Architecture/PortsAndAdapters/) | Cockburn, 2005 | Jádro nezávislé na okolí; závislosti míří dovnitř | ●●●●○ |
| [Rules Engine](Architecture/RulesEngine/) | Forgy 1979, Fowler 2009 | Byznysová pravidla jako seznam objektů se strategií a auditní stopou | ●●●●○ |
| [CQRS](Architecture/CQRS/) | Meyer 1988, Young 2010 | Oddělený model pro zápis a pro čtení — každý optimalizovaný na své | ●●●●○ |

<sub>Plánované (Clean Architecture, Event Sourcing, Saga): [Architecture/README.md](Architecture/)</sub>

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
- Sekce **„U nás“** spojuje obecný pattern s konkrétními věcmi na naší platformě. Pojmy, které v ní zaznívají (DX zpráva, SDK balíček, read-model služba), vysvětluje **[slovníček](Glossary.md)**.
