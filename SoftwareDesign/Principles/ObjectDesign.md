# Objektový návrh: Tell Don't Ask, Demeter, kompozice a spol.

> [← zpět na Principy](README.md)

> **V jedné větě:** Sedm pravidel o tom, **jak spolu objekty mluví** — a proč se kód rozpadá, když se nedodržují.

Zatímco [SOLID](SOLID.md) řeší, jak rozdělit odpovědnosti, [KISS/YAGNI/DRY](Simplicity.md), kolik toho psát, a [soudržnost s provázaností](CohesionAndCoupling.md) je měřítkem nad tím vším, tyhle principy řeší **komunikaci**: kdo se koho na co smí ptát, kdo o kom smí vědět a co se má stát, když je něco špatně.

| Princip | V jedné větě |
| ------- | ------------ |
| [Tell, Don't Ask](#tell-dont-ask) | Neptej se objektu na stav a nerozhoduj za něj — řekni mu, co má udělat. |
| [Zákon Demeter](#zákon-demeter-law-of-demeter) | Mluv jen se svými nejbližšími sousedy. |
| [Kompozice před dědičností](#kompozice-před-dědičností) | Skládej objekty, neděď je — dokud dědičnost není opravdu na místě. |
| [CQS](#cqs--command-query-separation) | Metoda buď mění stav, nebo vrací hodnotu. Nikdy obojí. |
| [Fail Fast](#fail-fast) | Spadni hned a hlasitě, ne o tři vrstvy dál a potichu. |
| [Poka-yoke](#poka-yoke--znemožni-chybu-nebo-ji-nakloň) | Ještě líp: zařiď, aby ta chyba nešla udělat. |
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

**Jak se sem dostat z existujícího kódu:** [Replace Superclass with Delegate](../../Refactoring/Code/ReplaceSuperclassWithDelegate/) (refaktoring) — krok za krokem, včetně toho, co `extends` bere navíc.

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

## Poka-yoke — znemožni chybu, nebo ji nakloň

> Nespoléhej na to, že si někdo dá pozor. **Uprav to tak, aby ta chyba nešla udělat** — a kde to nejde, zařiď, aby následek padl na bezpečnou stranu.

Nejlepší vysvětlení tohohle principu nejsou hodinky náhodou. **Luneta na potápěčských hodinkách se dá otáčet jen jedním směrem** — proti směru hodinových ručiček — a předepisuje to norma [ISO 6425](https://divewatch.com/reference/iso-6425/).

Funguje to takhle: před ponorem potápěč otočí nulu na lunetě k minutové ručičce a od té chvíle čte uplynulý čas z lunety. Kdyby šla otáčet oběma směry a někde o ni zavadil, mohla by ukázat, že uplynulo **méně** času, než ve skutečnosti — a potápěč by zůstal pod hladinou déle, než mu stačí vzduch.

**Jednosměrnost tu dělá dvě věci naráz:**

1. **Chybu v jednom směru znemožní.** Luneta se doprava neotočí, ať děláš, co děláš.
2. **Chybu v druhém směru nakloní na bezpečnou stranu.** Když se posune, ukáže *víc* uplynulého času. Potápěč vyplave dřív, než by musel — což je nepříjemné, ne smrtelné.

Přesně tohle je **poka-yoke**, princip z Toyoty. Zavedl ho **Shigeo Shingo** v roce **1961** a původně se jmenoval *baka-yoke*, tedy „blbuvzdorné"; přejmenoval se poté, co dělnice odmítla u takhle pojmenovaného zařízení pracovat. Podstata je v tom, že **se nespoléhá na pozornost člověka** — ta selže vždycky, stačí dost pokusů.

### Stupně obrany, od nejsilnějšího

| Stupeň | Co znamená | V PHP |
| ------ | ---------- | ----- |
| **1. Nejde to udělat** | Chybný stav se nedá ani zapsat | Typ místo `string`, `enum` místo konstant, `readonly` místo setteru, validace v konstruktoru |
| **1b. Nezáleží na tom** | Chybu nejde zakázat, tak ať nemá následek | [Idempotence](../Glossary.md#idempotence) — druhé spuštění nic nepřidá |
| **2. Pozná se to hned** | Chyba jde udělat, ale okamžitě křičí | [Fail Fast](#fail-fast) — výjimka místo `?? 0` |
| **3. Padne to na bezpečnou stranu** | Ani to nejde, tak ať následek nebolí | Výchozí hodnota, která nic nepovolí a nic neutratí |

**Sahej po nich v tomhle pořadí.** První stupeň je jediný, který funguje i po noční směni.

```php
// Stupeň 0: spoléhá se na pozornost
public function charge(int $amount, string $currency): void

// Stupeň 1: takhle to nejde zavolat špatně
public function charge(Money $amount): void
```

`charge(1000, 'CZK')` a `charge(1000, 'EUR')` vypadají stejně a spletou se; `Money::fromCents(1000, Currency::CZK)` ne. Je to táž myšlenka jako [Introduce Parameter Object](../../Refactoring/Code/IntroduceParameterObject/) — a jediné místo, kam se ta kontrola vejde, je konstruktor.

### Stupeň 1b: když se chybě nedá zabránit

Někdy první stupeň k dispozici není, protože **tu chybu nedělá člověk ani tvůj kód** — dělá ji prostředí. Fronta doručuje *aspoň jednou*. Uživatel klikne dvakrát, protože se stránka nehnula. Platební brána neodpoví do timeoutu, tak se volání zopakuje.

Zakázat se to nedá. Zopakované doručení **musí** být povolené, jinak se po výpadku sítě nedoručí nic.

Zbývá tedy jediný pohyb, a je to ten nejsilnější, který v takové situaci existuje: **nechat tu chybu nastat a odebrat jí následek.** Operace, která je [idempotentní](../Glossary.md#idempotence), se dá spustit pětkrát a dopadne to jako po prvním spuštění.

```php
// Kolize je fatální — dva dobropisy
public function refund(string $paymentId, int $amountInCents): void
{
    $this->gateway->refund($paymentId, $amountInCents);
}

// Kolize je nezajímavá — druhé volání nic neudělá
public function refund(string $paymentId, int $amountInCents): void
{
    if ($this->refunds->exists($paymentId)) {
        return;
    }

    $this->gateway->refund($paymentId, $amountInCents);
    $this->refunds->record($paymentId);
}
```

**Proč je to `1b` a ne `4`:** není to slabší varianta. Pro tuhle třídu chyb je to nejlepší dostupný tah, protože stupeň 1 tu neexistuje. Opakované doručení zprávy zakázat nejde — a nemá se zakazovat, jinak se po výpadku sítě nedoručí nic.

#### Tohle dělá i ta luneta — ale jinak

Stojí za to je postavit vedle sebe, protože **oba ty postupy nechají chybu nastat a postarají se, aby nebolela.** Luneta nezakazuje, že o ni potápěč zavadí; jen zařídí, aby to nevadilo.

Rozdíl je v tom, **kolik z toho následku zůstane**:

| | Idempotence | Luneta |
| --- | --- | --- |
| Chyba nastane | ano | ano |
| Co zůstane po ní | **nic** — stav je stejný jako bez ní | údaj je špatně, jen v neškodném směru |
| Co to stojí | nic | **minuty ponoru** |
| Kdo rozhoduje o ceně | nikdo, není co platit | **návrhář** — musí vybrat, co obětuje |

Jedna chybu **smaže**, druhá ji **zlevní**. A poslední řádek je ten, který se v kódu přehlíží: u lunety někdo musel rozhodnout, že **dýchatelný vzduch je důležitější než odkroucený ponor**, a tomu podřídit směr otáčení. To rozhodnutí je součást návrhu, ne jeho vedlejší efekt.

Praktický závěr z toho vychází jednoduchý:

- **Když jde následek smazat, smaž ho.** To je idempotence a nic tě nestojí.
- **Když nejde, zeptej se, kterou vlastnost jsi ochoten obětovat** — a obětuj ji vědomě, ne náhodou.

Druhý bod je celý rozdíl mezi naklonit a nechat padnout. Systém, který při pochybnostech o platbě raději **nepošle zboží**, obětoval rychlost dodání. Ten, který ho pošle, obětoval peníze. Obojí je rozhodnutí — jen v jednom případě ho někdo udělal.

> [!NOTE]
> Obě metody mají ještě jednu společnou vlastnost, a je to ta, která z nich dělá poka-yoke: **po nasazení na ně nikdo nemusí myslet.** Nevyžadují kázeň, dokumentaci ani kontrolu v code review — fungují i pro toho, kdo o nich neví.

### Třetí stupeň se nenavrhuje, a měl by

První dva stupně dělá kdekdo. Ten třetí — **naklonit zbytkovou chybu** — se skoro vždycky nechá náhodě, přestože je to přesně to, co dělá ta luneta.

| Situace | Nakloněné špatně | Nakloněné dobře |
| ------- | ---------------- | --------------- |
| Kontrola oprávnění selže | pustí dál | **nepustí** |
| Nový feature flag bez hodnoty | zapnuto | **vypnuto** |
| Platební brána neodpoví do timeoutu | bereme jako zaplaceno | **bereme jako nezaplaceno** a ověříme |
| Neznámý stav objednávky z cizího systému | spadne do `default` a jede dál | **výjimka**, protože nevíš, co to je |
| Chybí konfigurace limitu | nekonečno | **nula nebo výjimka** |

Poznávací znamení je jednoduché: **zeptej se, co se stane při nejhorší možné shodě okolností** — a jestli je to vratné. Potápěč, který vyplave o pět minut dřív, má nepříjemný den. Ten druhý směr se nedá vzít zpět, a proto ho luneta neumí.

> [!NOTE]
> ISO 6425 kromě jednosměrnosti požaduje i to, aby byla luneta **odolná proti nechtěnému otočení**. To je stupeň 1 a stupeň 3 v jednom výrobku — a je to dobrá připomínka, že se ty stupně nevylučují.

### Kam patří metriky a upozornění

Tohle se s poka-yoke plete často, a rozdíl stojí za vyslovení: **metrika chybě nezabrání, nenakloní ji ani jí neodebere následek. Řekne ti, že už se stala.**

Není to tedy čtvrtý stupeň, je to **[Fail Fast](#fail-fast) roztažený v čase** — mechanismus, kterým „spadni hlasitě" funguje i pro věci, které nevyhodí výjimku: fronta, která roste, konverze, která klesla, podíl duplicit, který se ztrojnásobil. Tyhle chyby nemají moment, ve kterém by šlo vyhodit výjimku, a bez měření by nebyly vidět vůbec.

| | Poka-yoke | Metrika a upozornění |
| --- | --- | --- |
| Kdy zafunguje | **před** chybou, nebo při ní | **po** chybě |
| Co potřebuje | nic — je to v kódu | někoho, kdo se dívá a umí zasáhnout |
| Když si nikdo nevšimne | nevadí, stejně to drží | **nestalo se nic** |

Prostřední řádek je ten podstatný. **Upozornění je návrh, který spoléhá na pozornost člověka** — tedy přesně to, čemu se poka-yoke vyhýbá. To z něj nedělá špatný nástroj; dělá to z něj **poslední** nástroj, ne první.

Poka-yoke z toho ale udělat jde, a to jedním krokem: **když upozornění spouští automatickou akci místo člověka.** Automatické vypnutí feature flagu při skoku chybovosti, [jistič](../Architecture/CircuitBreaker/), který po sérii selhání přestane volat cizí službu, zastavený import při podezřelém počtu duplicit — to všechno je stupeň 3, protože se systém sám přepne do bezpečnějšího režimu, i když se nikdo nedívá.

**Souvisí s patterny:** [Value Object](../DDD/ValueObject/) (neplatná instance nevznikne) · [Factory](../DDD/Factory/) (jediná cesta k sestavenému agregátu) · [State](../GoF/Behavioral/State/) (zakázaný přechod nejde provést) · [Specification](../DDD/Specification/) (pravidlo se dá zeptat předem, ne až po) · [Anticorruption Layer](../DDD/AnticorruptionLayer/) (neznámý cizí kód neprojde do domény) · [Saga](../Architecture/Saga/) a [Batching](../Architecture/Batching/) (obojí stojí a padá s idempotencí)

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
| **Poka-yoke** | Shigeo Shingo — Toyota Production System | 1961 |

---

## Zdroje

- Bertrand Meyer: *Object-Oriented Software Construction*, Prentice Hall, 1988
- Gamma, Helm, Johnson, Vlissides: *Design Patterns*, Addison-Wesley, 1994 — úvodní kapitola
- Eric Evans: *Domain-Driven Design*, Addison-Wesley, 2003 — kapitola 9
- Jim Shore: *Fail Fast*, IEEE Software, 2004
- Martin Fowler: *TellDontAsk*, 2013 — [martinfowler.com/bliki/TellDontAsk.html](https://martinfowler.com/bliki/TellDontAsk.html)
- Shigeo Shingo: *Zero Quality Control: Source Inspection and the Poka-yoke System*, Productivity Press, 1986
- [ISO 6425](https://divewatch.com/reference/iso-6425/) — norma pro potápěčské hodinky, odkud je příklad s lunetou
