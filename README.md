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
| ...jak se pattern jmenuje | [Sbírky](#sbírky) — u každé je tabulka jejích patternů |
| ...že začínám a nevím, kde píchnout | [Kudy začít](#kudy-začít) |
| ...proč vlastně patterny existují | [Principy](#principy) |

---

## Sbírky

Patterny jsou uspořádané podle **původu** — podle knihy nebo autora, kde byly poprvé popsané. Sbírka, která má vlastní členění (jako GoF), ho má i tady ve složkách; ostatní jsou plocho.

### [GoF — Design Patterns](GoF/)

> Gamma, Helm, Johnson, Vlissides · **1994** · *Design Patterns: Elements of Reusable Object-Oriented Software*

23 základních objektových vzorů. Kánon, na který odkazuje skoro všechno ostatní. Dělí se na [Creational](GoF/Creational/) (vytváření objektů), [Structural](GoF/Structural/) (skládání objektů) a [Behavioral](GoF/Behavioral/) (komunikace mezi objekty).

| Pattern | Kategorie | K čemu to je | Obtížnost |
| ------- | --------- | ------------ | --------- |
| [Strategy](GoF/Behavioral/Strategy/) | Behavioral | Zaměnitelné algoritmy za jedním rozhraním — místo `if`/`switch` na typ | ●●○○○ |

<sub>Kompletní katalog všech 23 patternů včetně nezpracovaných: [GoF/README.md](GoF/)</sub>

### [Object Calisthenics](ObjectCalisthenics/)

> Jeff Bay · **2008** · *The ThoughtWorks Anthology*

Devět záměrně přísných pravidel objektového návrhu, původně jako **cvičení**, ne jako předpis pro produkci. Dvě z nich se osamostatnila a dnes fungují jako plnohodnotné patterny.

| Pattern | K čemu to je | Obtížnost |
| ------- | ------------ | --------- |
| [First Class Collection](ObjectCalisthenics/FirstClassCollection/) | Pole zabalené do vlastní třídy s doménovými metodami a pravidly skupiny | ●●○○○ |

<sub>Všech devět pravidel a co se z nich uchytilo: [ObjectCalisthenics/README.md](ObjectCalisthenics/)</sub>

### PoEAA — Enterprise patterny ⬜

> Martin Fowler · **2002** · *Patterns of Enterprise Application Architecture*

Vzory pro aplikace nad databází a s doménovou logikou — Repository, Unit of Work, Data Mapper, Service Layer, Identity Map. Tohle je vrstva, ve které se v našich službách pohybujeme denně.

### [DDD — Domain-Driven Design](DDD/)

> Eric Evans · **2003** · *Domain-Driven Design* (+ Vaughn Vernon, 2013)

Aggregate, Entity, Value Object, Domain Event, Bounded Context. Ne úplně „design patterny“ v gangofourském smyslu, ale stejný typ znalosti — pojmenované řešení opakujícího se problému.

| Pattern | Kategorie | K čemu to je | Obtížnost |
| ------- | --------- | ------------ | --------- |
| [Value Object](DDD/ValueObject/) | Taktický | Hodnota bez identity — vlastní typ místo `string` a `int` | ●●○○○ |
| [Specification](DDD/Specification/) | Taktický | Doménové pravidlo jako objekt — pojmenovatelné, testovatelné, skládatelné | ●●●○○ |

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

<sub>Plánované (Clean Architecture, CQRS, Event Sourcing, Saga): [Architecture/README.md](Architecture/)</sub>

<sub>⬜ plánováno · 🚧 rozpracováno · ✅ hotovo</sub>

---

## Principy

Patterny jsou konkrétní řešení; **principy jsou měřítko, podle kterého se pozná, jestli je návrh dobrý**. Většina patternů tady existuje proto, že řeší porušení některého z nich — proto na ně odkazují a nevysvětlují si je pokaždé znovu.

| Principy | O čem to je | Stav |
| -------- | ----------- | ---- |
| [**SOLID**](Principles/SOLID.md) | Pět principů objektového návrhu — SRP, OCP, LSP, ISP, DIP | ✅ |

<sub>Kompletní seznam včetně plánovaných (DRY, KISS, YAGNI, Law of Demeter): [Principles/README.md](Principles/)</sub>

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
