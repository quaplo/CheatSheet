# Objektový návrh: Tell Don't Ask, Demeter, kompozice a spol.

> [← zpět na Principy](README.md)

> **V jedné větě:** Šest pravidel o tom, **jak spolu objekty mluví** — a proč se kód rozpadá, když se nedodržují.

Zatímco [SOLID](SOLID.md) řeší, jak rozdělit odpovědnosti, [KISS/YAGNI/DRY](Simplicity.md), kolik toho psát, a [soudržnost s provázaností](CohesionAndCoupling.md) je měřítkem nad tím vším, tyhle principy řeší **komunikaci**: kdo se koho na co smí ptát, kdo o kom smí vědět a co se má stát, když je něco špatně.

| Princip | V jedné větě |
| ------- | ------------ |
| [Tell, Don't Ask](#tell-dont-ask) | Neptej se objektu na stav a nerozhoduj za něj — řekni mu, co má udělat. |
| [Zákon Demeter](#zákon-demeter-law-of-demeter) | Mluv jen se svými nejbližšími sousedy. |
| [Kompozice před dědičností](#kompozice-před-dědičností) | Skládej objekty, neděď je — dokud dědičnost není opravdu na místě. |
| [CQS](#cqs--command-query-separation) | Metoda buď mění stav, nebo vrací hodnotu. Nikdy obojí. |
| [Fail Fast](#fail-fast) | Spadni hned a hlasitě, ne o tři vrstvy dál a potichu. |
| [Zviditelni implicitní](#zviditelni-implicitní) | Co je v kódu skryté v podmínkách a pořadí, má dostat jméno. |

---

## Tell, Don't Ask

> Neptej se objektu na jeho stav, abys pak sám rozhodl, co s ním. **Řekni mu, co chceš, a nech rozhodnutí na něm.**

Pojmenoval to **Alec Sharp** v *Smalltalk by Example* (**1997**), rozšířili Pragmatic Programmers. Důvod je prostý: když se ptáš na stav a rozhoduješ venku, přesunul jsi logiku objektu mimo něj — a příště ji tam přesune někdo znovu, jen o kus jinak.

**Poznáš porušení podle:**

- getter, hned za ním podmínka, a v ní rozhodnutí o tom objektu
- táž podmínka nad týmž objektem na několika místech
- objekt má samé gettery a žádné chování (anemický model)
- musíš znát vnitřní strukturu objektu, abys s ním mohl pracovat

```php
// Ptáme se a rozhodujeme za objednávku
if ($order->getStatus() === 'paid' && $order->getTotalInCents() >= 150000) {
    $shipping = 0;
}

// Řekneme jí, co potřebujeme vědět — a rozhodnutí zůstane u ní
if ($order->qualifiesForFreeShipping()) {
    $shipping = 0;
}
```

**Kde to neplatí.** U **čtecích modelů a DTO** — ty jsou schválně jen data. Řádek tabulky v administraci nemá mít chování, protože to není doménový objekt; viz [CQRS](../Architecture/CQRS/). Tell Don't Ask platí na doménu, ne na všechno, co má property.

**Souvisí s patterny:** [Entity](../DDD/Entity/) — **rozdíl mezi anemickým a doménovým modelem je přesně tenhle princip** · [Domain Service](../DDD/DomainService/) (pozor na obrácení: služba tahající stav z entit je signál, že logika patřila do nich) · [Aggregate](../DDD/Aggregate/) (kořenu se neptáš na položky, řekneš mu `addItem()`) · [Domain Event](../DDD/DomainEvent/) (obrácený směr: agregát ani neptá, ani neříká — **oznamuje**) · [First Class Collection](../ObjectCalisthenics/FirstClassCollection/) (neptáš se na pole, řekneš `total()`) · [Value Object](../DDD/ValueObject/) · [State](../GoF/Behavioral/State/) · [Specification](../DDD/Specification/)

---

## Zákon Demeter (Law of Demeter)

> Metoda smí volat metody jen na: **sobě**, svých **parametrech**, objektech, které **sama vytvořila**, a svých **vlastních fieldech**. Na ničem, co dostane od někoho jiného.

Vznikl v roce **1987** na Northeastern University v projektu Demeter (odtud jméno — je to projekt, ne člověk). V praxi se cituje jako *„jedna tečka na řádek“*, což je zkratka, ale funguje.

**Poznáš porušení podle:**

- řetězů getterů: `$order->getCustomer()->getAddress()->getCity()`
- kódu, který ví o vnitřní struktuře něčeho, co jen dostal
- testu, ve kterém musíš namockovat mock, který vrací mock

```php
// Známe cestu přes tři objekty — a všechny tři nás můžou rozbít
$city = $order->getCustomer()->getAddress()->getCity();

// Ptáme se toho, s kým skutečně mluvíme
$city = $order->deliveryCity();
```

Cena porušení není estetická. Tím řetězem jsi se **připoutal ke struktuře tří tříd naráz**: když kterákoli z nich změní tvar, přestane to fungovat — a přitom s dvěma z nich tvoje třída nemá mít nic společného.

**Kde to neplatí.** U **fluent rozhraní** a builderů, kde každé volání vrací tentýž typ:

```php
$query->select('o')->from(Order::class)->where('o.paid = 1');   // v pořádku
```

Tady žádnou cizí strukturu neodhaluješ — pořád mluvíš s jedním objektem. Stejně tak u kolekcí a value objectů, kde je „průchod“ součástí smyslu.

**Souvisí s patterny:** [First Class Collection](../ObjectCalisthenics/FirstClassCollection/) · [Value Object](../DDD/ValueObject/) · [Ports & Adapters](../Architecture/PortsAndAdapters/) (adaptér tě odstíní od cizí struktury)

---

## Kompozice před dědičností

> *Favor object composition over class inheritance.* Skládej objekty z menších objektů, místo abys stavěl hierarchie tříd.

Jedna z **dvou hlavních zásad, kterými GoF (1994) uvádí svou knihu** — většina jejich vzorů je jejím přímým důsledkem.

Rozdíl je v tom, co ti dědičnost bere:

| | Dědičnost | Kompozice |
| --- | --- | --- |
| Kdy se rozhoduje | Při psaní kódu | **Za běhu** |
| Kolik toho přebíráš | **Všechno** z předka | Jen co si vezmeš |
| Co vidíš z vnitřku | Chráněné fieldy, implementaci | Jen veřejné rozhraní |
| Kolik jich může být | Jedna | Kolik chceš |
| Když se předek změní | Rozbije všechny potomky | Nic |

**Poznáš porušení podle:**

- hierarchie hlubší než dvě úrovně
- `extends`, u kterého věta „potomek **je** předek“ zní divně
- `protected` fieldů, ke kterým sahá pět potomků
- potomka, který přepíše metodu prázdným tělem nebo výjimkou (to je i porušení [LSP](SOLID.md#liskov-substitution-principle-lsp))
- potřeby dědit ze dvou tříd najednou

```php
// Dědičnost kvůli sdílení kódu — a hierarchie roste s každým požadavkem
class Shipping { }
class DiscountedShipping extends Shipping { }
class DiscountedExpressShipping extends DiscountedShipping { }   // …a co teď expres bez slevy?

// Kompozice — varianty se skládají
$shipping = new DiscountedShipping(new ExpressShipping(), $discount);
```

**Kde dědičnost je správně.** Když je vztah opravdu „je to“ **a** kontrakt předka platí pro každého potomka beze zbytku. V tomhle katalogu ji záměrně používají [State](../GoF/Behavioral/State/) (základní třída definuje, že vše je zakázané) a [Chain of Responsibility](../GoF/Behavioral/ChainOfResponsibility/) (`final` metoda drží průchod řetězem). Obojí je dědičnost **kvůli kontraktu**, ne kvůli sdílení kódu — a to je ta hranice.

**Souvisí s patterny:** [Strategy](../GoF/Behavioral/Strategy/) · [Decorator](../GoF/Structural/Decorator/) — **učebnicová ukázka**: 3 vlastnosti znamenají 8 podtříd, ale jen 3 dekorátory · **Bridge**

---

## CQS — Command-Query Separation

> Metoda je buď **příkaz**, který mění stav a nic nevrací, nebo **dotaz**, který vrací hodnotu a nic nemění. Nikdy obojí.

**Bertrand Meyer**, *Object-Oriented Software Construction* (**1988**). Důvod: dotaz, který mění stav, se nedá bezpečně zavolat dvakrát — a nikdo to na něm nepozná, dokud se nespálí.

**Poznáš porušení podle:**

- `get*()` metody, která něco změní, uloží nebo inkrementuje
- metody, po jejímž zavolání v ladicím výpisu se program chová jinak
- toho, že nemůžeš přesunout volání o řádek výš, aniž bys změnil výsledek

```php
// Vypadá jako dotaz, ale mění stav — zavolej to dvakrát a máš dvě čísla
public function getNextInvoiceNumber(): int
{
    return ++$this->counter;
}

// Příkaz a dotaz odděleně
public function generateNextInvoiceNumber(): void { $this->counter++; }
public function currentInvoiceNumber(): int { return $this->counter; }
```

**Kde to neplatí.** Klasická výjimka je `pop()` u zásobníku — vrátí prvek a zároveň ho odebere. Rozdělit to na `top()` a `pop()` jde, ale v souběžném prostředí to vytvoří závod. Meyer tuhle výjimku sám uznával; jde o to porušovat princip **vědomě a výjimečně**, ne ze zvyku.

**Souvisí s patterny:** [CQRS](../Architecture/CQRS/) — je to přesně tenhle princip povýšený z metody na celý model.

---

## Fail Fast

> Když je něco špatně, spadni **hned a hlasitě**. Neschovávej to, nepokračuj s poškozeným stavem, nevracej výchozí hodnotu.

Popsali **Jim Shore a Martin Fowler** v IEEE Software (**2004**). Princip působí kontraintuitivně — vypadá, že aplikace, která nespadne, je odolnější. Opak je pravdou: chyba, která se objeví o tři vrstvy dál, stojí desetinásobek času na dohledání, a chyba, která se neobjeví vůbec, skončí špatnými daty v databázi.

**Poznáš porušení podle:**

- `catch (\Throwable $e) { }` s prázdným tělem
- funkce vracející `null` nebo `0` místo toho, aby řekla, že vstup nedává smysl
- operátoru `@` kdekoli
- výchozích hodnot, které zakrývají chybějící konfiguraci
- řetězu, na jehož konci se tiše nestane nic

```php
// Chyba se schová a projeví se až u zákazníka na faktuře
public function vatRateFor(string $country): float
{
    return $this->rates[$country] ?? 0.0;
}

// Chybějící sazba je chyba, ne nula
public function vatRateFor(string $country): float
{
    return $this->rates[$country]
        ?? throw new InvalidArgumentException(sprintf('Chybí sazba DPH pro zemi %s.', $country));
}
```

**Souvisí s patterny:** [Value Object](../DDD/ValueObject/) (validace v konstruktoru — neplatná instance nevznikne) · [State](../GoF/Behavioral/State/) (zakázaný přechod vyhodí výjimku) · [Chain of Responsibility](../GoF/Behavioral/ChainOfResponsibility/) (ošetřený konec řetězu) · [Repository](../PoEAA/Repository/) (`get()` vyhodí, `find()` vrací `null`) · [Anticorruption Layer](../DDD/AnticorruptionLayer/) (neznámý cizí kód vyhodí výjimku, nespadne do defaultu)

---

## Zviditelni implicitní

> Co je v kódu skryté v pořadí podmínek, v konvenci nebo v hlavě autora, má dostat **jméno a vlastní místo**.

Nemá jednoho autora ani zkratku, ale prochází celým DDD — Eric Evans mu věnoval kapitolu *Making Implicit Concepts Explicit* (**2003**). Je to princip, kterým se dá vysvětlit překvapivě velká část tohohle katalogu.

**Poznáš porušení podle:**

- pravidla, které má jméno na poradě, ale v kódu ho nenajdeš
- toho, že na pořadí `if`ů záleží, ale nikde není napsané proč
- „to se ví“ v odpovědi na otázku, proč je něco takhle
- konstanty `150000` na třech místech bez jména

```php
// Implicitní: pravidlo existuje, ale nemá jméno ani místo
if ($order->isPaid && $order->totalInCents >= 150000 && $order->country === 'CZ') { }

// Explicitní: pojem, o kterém jde mluvit i s produkťákem
if ((new EligibleForFreeShipping())->isSatisfiedBy($order)) { }
```

**Souvisí s patterny:** [Specification](../DDD/Specification/) (pravidlo dostane jméno) · [Rules Engine](../Architecture/RulesEngine/) (pořadí a řešení konfliktů přestanou být skryté) · [Value Object](../DDD/ValueObject/) (hodnota dostane typ) · [State](../GoF/Behavioral/State/) (dovolené přechody přestanou být poskládané z podmínek) · [Context Map](../DDD/ContextMap/) (vztahy mezi týmy existují, ať je nakreslíš, nebo ne)

---

## Původ

| Princip | Autor | Rok |
| ------- | ----- | --- |
| **CQS** | Bertrand Meyer — *Object-Oriented Software Construction* | 1988 |
| **Zákon Demeter** | projekt Demeter, Northeastern University | 1987 |
| **Kompozice před dědičností** | Gamma, Helm, Johnson, Vlissides — *Design Patterns* | 1994 |
| **Tell, Don't Ask** | Alec Sharp — *Smalltalk by Example* | 1997 |
| **Zviditelni implicitní** | Eric Evans — *Domain-Driven Design* | 2003 |
| **Fail Fast** | Jim Shore, Martin Fowler — IEEE Software | 2004 |

---

## Zdroje

- Bertrand Meyer: *Object-Oriented Software Construction*, Prentice Hall, 1988
- Gamma, Helm, Johnson, Vlissides: *Design Patterns*, Addison-Wesley, 1994 — úvodní kapitola
- Eric Evans: *Domain-Driven Design*, Addison-Wesley, 2003 — kapitola 9
- Jim Shore: *Fail Fast*, IEEE Software, 2004
- Martin Fowler: *TellDontAsk*, 2013 — [martinfowler.com/bliki/TellDontAsk.html](https://martinfowler.com/bliki/TellDontAsk.html)
