# SOLID

> [← zpět na Principy](README.md)

> **V jedné větě:** Pět principů, které drží objektový návrh v takovém stavu, aby se dal měnit bez toho, že se při každé změně rozbije něco jinde.

SOLID nejsou patterny. Patterny jsou **konkrétní řešení konkrétních problémů**; principy jsou **měřítko, podle kterého se pozná, jestli je návrh dobrý**. Většina patternů v tomhle katalogu existuje právě proto, že řeší porušení některého z těchhle pěti bodů.

| Zkratka | Princip | V jedné větě |
| ------- | ------- | ------------ |
| **S** | [Single Responsibility](#single-responsibility-principle-srp) | Třída má mít jediný důvod ke změně. |
| **O** | [Open/Closed](#openclosed-principle-ocp) | Otevřená rozšíření, uzavřená změnám. |
| **L** | [Liskov Substitution](#liskov-substitution-principle-lsp) | Potomka musí jít podstrčit místo předka, aniž se něco rozbije. |
| **I** | [Interface Segregation](#interface-segregation-principle-isp) | Radši víc malých rozhraní než jedno velké. |
| **D** | [Dependency Inversion](#dependency-inversion-principle-dip) | Závis na abstrakcích, ne na konkrétních implementacích. |

---

## Single Responsibility Principle (SRP)

> Třída má mít **jediný důvod ke změně**.

Nejčastěji špatně chápaný princip. Neříká „třída má dělat jednu věc“ — říká, že by ji **neměly chtít měnit dvě různé skupiny lidí z různých důvodů**. Když do jedné třídy sahá účetní kvůli formátu faktury a marketing kvůli textu e-mailu, jsou to dva důvody ke změně.

**Poznáš porušení podle:**

- v názvu třídy je „a“ nebo „Manager“, „Helper“, „Utils“
- třída má závislosti z různých světů současně (databáze + HTTP klient + šablonovač)
- při změně požadavku od jednoho oddělení se bojíš, že rozbiješ funkci jiného

```php
// Špatně: objednávka počítá cenu, generuje PDF a posílá e-mail
final class Order
{
    public function total(): int { /* … */ }
    public function renderInvoicePdf(): string { /* … */ }
    public function sendConfirmationEmail(): void { /* … */ }
}

// Lépe: každá odpovědnost má vlastní třídu
final class Order { public function total(): int { /* … */ } }
final class InvoiceRenderer { public function render(Order $order): string { /* … */ } }
final class OrderConfirmationMailer { public function send(Order $order): void { /* … */ } }
```

**Souvisí s patterny:** [Value Object](../DDD/ValueObject/) (pravidla o jedné hodnotě mají vlastní třídu) · [First Class Collection](../ObjectCalisthenics/FirstClassCollection/) (pravidla o skupině mají vlastní třídu) · Facade (skrývá subsystém, aby jeho části mohly zůstat malé) · Command (jedna operace = jedna třída)

---

## Open/Closed Principle (OCP)

> Kód má být **otevřený rozšíření, ale uzavřený změnám**. Novou funkci přidáš novým kódem, ne editací existujícího.

Důvod je praktický, ne estetický: kód, který neupravuješ, nemůžeš rozbít. Otestovaná a v produkci odladěná třída má zůstat na pokoji.

**Poznáš porušení podle:**

- při přidání nové varianty musíš **editovat existující třídu**, i když se stará chování nemění
- `switch` / `match` / kaskáda `if`ů větvící se podle typu, kódu nebo konfigurace
- do jedné metody přibývá při každém novém požadavku další větev

```php
// Špatně: nový dopravce = zásah do téhle metody
public function shippingCost(Order $order, string $code): int
{
    if ($code === 'pickup_point') { /* … */ }
    if ($code === 'courier')      { /* … */ }
    // …a tady za půl roku přibude další větev
}

// Lépe: nový dopravce = nová třída, tahle metoda se nemění
public function shippingCost(Order $order, string $code): int
{
    return $this->strategies[$code]->calculate($order);
}
```

**Souvisí s patterny:** [Strategy](../GoF/Behavioral/Strategy/) · [Specification](../DDD/Specification/) (nové pravidlo = nová třída) · Decorator · Template Method · Visitor · Chain of Responsibility

---

## Liskov Substitution Principle (LSP)

> Kdekoli kód pracuje s předkem, musí jít **podstrčit libovolného potomka**, aniž by přestal fungovat.

Dědičnost není „sdílení kódu“, ale slib: *„jsem plnohodnotná náhrada za svého předka“*. Když potomek ten slib poruší, každý `instanceof` v aplikaci je jen záplata na špatný návrh.

**Poznáš porušení podle:**

- potomek **vyhazuje výjimku** v metodě, kterou předek normálně plní
- potomek zpřísňuje vstupy nebo rozvolňuje výstupy oproti předkovi
- potomek přepíše metodu prázdným tělem („tohle u nás neplatí“)
- volající si musí ověřovat `instanceof`, aby věděl, co smí zavolat

```php
// Špatně: potomek nesplní slib, který dal předek
class OrderRepository
{
    public function save(Order $order): void { /* … */ }
}

final class ReadOnlyOrderRepository extends OrderRepository
{
    public function save(Order $order): void
    {
        throw new LogicException('Read-only repository.'); // volající tohle nečeká
    }
}

// Lépe: rozděl kontrakty, ať nikdo neslibuje, co nesplní
interface OrderReader { public function find(OrderId $id): ?Order; }
interface OrderWriter { public function save(Order $order): void; }
```

**Souvisí s patterny:** [Repository](../PoEAA/Repository/) (in-memory implementace musí být plnohodnotná náhrada té ostré — jinak testy lžou) · Template Method (kostra v předkovi musí platit pro všechny potomky) · Composite · [Strategy](../GoF/Behavioral/Strategy/) (kompozice místo dědičnosti se LSP vyhne úplně) · [Value Object](../DDD/ValueObject/) (`final` je tu záměr — potomek s jinou rovností poruší kontrakt)

---

## Interface Segregation Principle (ISP)

> Nikdo nemá být nucen záviset na metodách, které nepoužívá. **Několik malých rozhraní je lepší než jedno velké.**

Velké rozhraní zatáhne do každého konzumenta i závislosti, které nepotřebuje — a v testech tě donutí namockovat deset metod kvůli jedné.

**Poznáš porušení podle:**

- rozhraní má víc než pár metod a žádný konzument nepoužívá všechny
- implementace mají metody s prázdným tělem nebo `throw new NotImplementedException()`
- test mockuje osm metod, aby ověřil jednu

```php
// Špatně: konzument, který jen čte, musí znát celé CRUD
interface OrderRepositoryInterface
{
    public function find(OrderId $id): ?Order;
    public function findAll(): array;
    public function save(Order $order): void;
    public function delete(OrderId $id): void;
    public function lockForUpdate(OrderId $id): void;
}

// Lépe: každý konzument dostane jen to, co potřebuje
interface OrderReader { public function find(OrderId $id): ?Order; }
interface OrderWriter { public function save(Order $order): void; }
```

**Souvisí s patterny:** [Specification](../DDD/Specification/) (kontrakt o jediné metodě — menší už neuděláš) · [Ports & Adapters](../Architecture/PortsAndAdapters/) (port popisuje jednu vnější starost, ne celé SDK) · Adapter (zúží cizí rozhraní na to, co náš kód opravdu potřebuje) · Facade · Proxy

---

## Dependency Inversion Principle (DIP)

> Moduly vyšší úrovně nemají záviset na modulech nižší úrovně. **Obě strany mají záviset na abstrakci** — a tu abstrakci vlastní ta vyšší vrstva.

Druhá věta je ta důležitá a nejčastěji přehlížená: rozhraní `OrderRepository` patří do **domény**, ne do infrastruktury. Doména říká, co potřebuje; databázová vrstva se tomu přizpůsobí. Přesně na tomhle stojí hexagonální architektura.

**Poznáš porušení podle:**

- doménová nebo aplikační třída má v konstruktoru konkrétní `DoctrineOrderRepository`, `GuzzleClient`, `PDO`
- rozhraní žije ve stejné složce jako jeho databázová implementace
- nejde napsat unit test bez databáze nebo bez HTTP

```php
// Špatně: use-case je přilepený na Doctrine
final class PlaceOrder
{
    public function __construct(private DoctrineOrderRepository $repository) {}
}

// Lépe: use-case si řekne o kontrakt, který sám vlastní
namespace App\Domain\Order;

interface OrderRepository
{
    public function save(Order $order): void;
}

// App\Infrastructure\Persistence\DoctrineOrderRepository implements OrderRepository
```

**Souvisí s patterny:** [Ports & Adapters](../Architecture/PortsAndAdapters/) (DIP dotažené na úroveň celé aplikace) · [Repository](../PoEAA/Repository/) (rozhraní vlastní doména, infrastruktura se přizpůsobí) · Abstract Factory · [Strategy](../GoF/Behavioral/Strategy/) · Adapter

---

## Jak to používat (a jak ne)

SOLID jsou **heuristiky, ne zákony**. Nejčastější škoda, kterou napáchají, je nadšený junior, který rozseká třířádkovou třídu na pět rozhraní, protože „SRP“.

- Princip je **argument v code review**, ne vstupenka k refaktoringu čehokoli.
- Porušení principu je **varovný signál**, ne chyba. Vědomé porušení s důvodem je v pořádku — nevědomé je problém.
- Cena za dodržení principu (víc tříd, víc souborů, víc skoků při čtení) je reálná. Plať ji tam, kde se změny opravdu dějí.
- Když nevíš, jestli princip porušuješ, zeptej se: **„co se stane, až přijde další požadavek téhle kategorie?“** Když odpověď zní „musím editovat tuhle třídu“, máš odpověď.

---

## Původ

|               |                                                                          |
| ------------- | ------------------------------------------------------------------------ |
| **Autor**     | Robert C. Martin („Uncle Bob“)                                            |
| **Vznik**     | konec 90. let, souhrnně v článku *Design Principles and Design Patterns* (**2000**) |
| **Akronym**   | Michael Feathers, cca **2004**                                            |
| **Knižně**    | *Agile Software Development: Principles, Patterns, and Practices* (2002)   |

Martin principy formuloval postupně v článcích pro *C++ Report* v době, kdy objektové programování v praxi často znamenalo hluboké dědičné hierarchie, které se po pár letech nedaly udržet. Akronym SOLID vznikl až později — Feathers si všiml, že se pět z Martinových principů dá přeuspořádat do zapamatovatelného slova.

---

## Zdroje

- Robert C. Martin: *Design Principles and Design Patterns*, 2000
- Robert C. Martin: *Agile Software Development: Principles, Patterns, and Practices*, Prentice Hall, 2002
- Robert C. Martin: *Clean Architecture*, Prentice Hall, 2017 — část III
