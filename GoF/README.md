# GoF — Design Patterns

> Erich Gamma, Richard Helm, Ralph Johnson, John Vlissides („**Gang of Four**“) · **1994**
> *Design Patterns: Elements of Reusable Object-Oriented Software*

## Původ

Čtveřice autorů v roce 1994 sesbírala a pojmenovala 23 řešení, která se v objektově orientovaných programech opakovaně objevovala — v Smalltalku a C++, tedy dávno před PHP. Přínos knihy nebyl v tom, že by ty vzory vymyslela; byl v tom, že jim dala **jména a společný slovník**. Když dnes v code review napíšeš „udělej z toho Strategy“, funguje to jen díky téhle knize.

Kniha je místy poplatná době (C++ bez generik, žádné first-class funkce, statické jazyky bez closures) — část vzorů je v moderním PHP triviální nebo zbytečná. U každého patternu proto explicitně řešíme, jestli a jak dává v dnešním PHP smysl.

## Kategorie

Autoři vzory rozdělili podle toho, čeho se týkají:

| Kategorie | O čem to je | Patterny |
| --------- | ----------- | -------- |
| [**Creational**](Creational/) | Jak vznikají objekty — oddělení „co se vytváří“ od „kdo to používá“ | 5 |
| [**Structural**](Structural/) | Jak se objekty skládají do větších celků | 7 |
| [**Behavioral**](Behavioral/) | Jak spolu objekty komunikují a dělí si odpovědnost | 11 |

## Katalog

### Creational

| Pattern | K čemu | Stav |
| ------- | ------ | ---- |
| Abstract Factory | Rodiny souvisejících objektů bez vazby na konkrétní třídy | ⬜ |
| Builder | Postupné sestavení složitého objektu | ⬜ |
| [**Factory Method**](Creational/FactoryMethod/) | Vytvoření objektu delegované na potomka; pojmenované konstruktory | ✅ |
| Prototype | Nový objekt klonováním existujícího | ⬜ |
| [**Singleton**](Creational/Singleton/) | Jediná instance v aplikaci — a proč ho skoro nikdy nechceš | ✅ |

### Structural

| Pattern | K čemu | Stav |
| ------- | ------ | ---- |
| [**Adapter**](Structural/Adapter/) | Přizpůsobení cizího rozhraní tomu, co očekává náš kód | ✅ |
| Bridge | Oddělení abstrakce od implementace, aby šly měnit nezávisle | ⬜ |
| [**Composite**](Structural/Composite/) | Strom objektů, se kterým se pracuje jako s jedním prvkem | ✅ |
| [**Decorator**](Structural/Decorator/) | Přidání chování obalením objektu, bez dědičnosti | ✅ |
| Facade | Jednoduché rozhraní před složitým subsystémem | ⬜ |
| Flyweight | Sdílení paměťově náročných dat mezi instancemi | ⬜ |
| Proxy | Zástupce objektu, který řídí přístup k němu | ⬜ |

### Behavioral

| Pattern | K čemu | Stav |
| ------- | ------ | ---- |
| [**Chain of Responsibility**](Behavioral/ChainOfResponsibility/) | Řetěz zpracovatelů, požadavek putuje k tomu, kdo ho umí obsloužit | ✅ |
| Command | Operace zabalená do objektu — jde předat, zařadit do fronty, vrátit zpět | ⬜ |
| Interpreter | Vyhodnocení vět jednoduchého jazyka | ⬜ |
| Iterator | Průchod kolekcí bez znalosti její vnitřní struktury | ⬜ |
| Mediator | Prostředník, přes kterého objekty komunikují místo napřímo | ⬜ |
| Memento | Uložení a obnovení stavu objektu bez porušení zapouzdření | ⬜ |
| [**Observer**](Behavioral/Observer/) | Objekt informuje odběratele o své změně | ✅ |
| [**State**](Behavioral/State/) | Objekt mění chování podle vnitřního stavu | ✅ |
| [**Strategy**](Behavioral/Strategy/) | Zaměnitelné algoritmy za jedním rozhraním | ✅ |
| Template Method | Kostra algoritmu v předkovi, kroky v potomcích | ⬜ |
| Visitor | Nová operace nad strukturou objektů bez zásahu do jejich tříd | ⬜ |

<sub>⬜ plánováno · 🚧 rozpracováno · ✅ hotovo</sub>

## Zdroje

- Gamma, Helm, Johnson, Vlissides: *Design Patterns: Elements of Reusable Object-Oriented Software*, Addison-Wesley, 1994
