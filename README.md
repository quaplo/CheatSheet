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

### PoEAA — Enterprise patterny ⬜

> Martin Fowler · **2002** · *Patterns of Enterprise Application Architecture*

Vzory pro aplikace nad databází a s doménovou logikou — Repository, Unit of Work, Data Mapper, Service Layer, Identity Map. Tohle je vrstva, ve které se v našich službách pohybujeme denně.

### DDD — Domain-Driven Design ⬜

> Eric Evans · **2003** · *Domain-Driven Design* (+ Vaughn Vernon, 2013)

Aggregate, Entity, Value Object, Domain Event, Bounded Context. Ne úplně „design patterny“ v gangofourském smyslu, ale stejný typ znalosti — pojmenované řešení opakujícího se problému.

### EIP — Integrační patterny ⬜

> Hohpe, Woolf · **2003** · *Enterprise Integration Patterns*

Vzory pro messaging a komunikaci mezi službami — Message Router, Publish-Subscribe, Idempotent Receiver, Dead Letter Channel.

### Architecture ⬜

> různí autoři, různé roky

Vzory bez jedné mateřské knihy: Hexagonal Architecture (Cockburn, 2005), Clean Architecture (Martin, 2012), CQRS (Young, 2010), Event Sourcing, Saga.

<sub>⬜ plánováno · 🚧 rozpracováno · ✅ hotovo</sub>

---

## Index podle problému

Obrácený rejstřík: začni u toho, co tě pálí.

| Mám problém | Zvaž |
| ----------- | ---- |
| Rozrostlý `if`/`switch`, který se větví podle typu, a přibývají do něj další větve | [Strategy](GoF/Behavioral/Strategy/) |
| Potřebuju za běhu měnit chování objektu podle konfigurace nebo vstupu | [Strategy](GoF/Behavioral/Strategy/) |
| Chci algoritmus otestovat izolovaně, ale je zadrátovaný uvnitř velké třídy | [Strategy](GoF/Behavioral/Strategy/) |

---

## Kudy začít

Doporučené pořadí, pokud je pro tebe téma nové. Každý další pattern staví na předchozím.

1. [Strategy](GoF/Behavioral/Strategy/) — nejlepší první pattern: ukazuje kompozici i polymorfismus na malém kódu.

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
