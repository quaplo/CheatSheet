# Replace Superclass with Delegate

> [← zpět na Refaktoring kódu](../)

> **V jedné větě:** Když třída dědí kvůli tomu, co předek *umí*, a ne kvůli tomu, co *je* — zahoď `extends` a předka si drž uvnitř jako pole.

> [!IMPORTANT]
> **Tenhle refaktoring znáš nejspíš pod jménem *Replace Inheritance with Delegation*.** Tak se jmenoval v prvním vydání *Refactoringu* a Fowler ho dnes vede jako alias. V druhém vydání se jmenuje **Replace Superclass with Delegate** a vedle něj existuje **Replace Subclass with Delegate**, což je jiná technika — ta ruší **podtřídu**, tahle ruší **předka**.

---

## Kdy po tom sáhnout

**Poznáš to podle:**

- věta „`PaidOrders` **je** `ArrayObject`" zní divně, ale „`PaidOrders` **má** pole" sedí
- z předka používáš tři metody a zbylých dvacet tě spíš ohrožuje
- musíš přepisovat zděděné metody jen proto, abys je zakázal (`throw new BadMethodCallException`)
- pravidlo, které si třída hlídá, jde obejít metodou, kterou jsi nenapsal
- při čtení podtřídy musíš otevřít předka, abys věděl, co ta třída umí

Ten čtvrtý bod je ten, který nakonec rozhodne. **Dědičnost není jen znovupoužití kódu — je to i zdědění celého veřejného rozhraní**, včetně cest dovnitř, o kterých jsi nevěděl.

---

## Předtím

Kolekce, do které smějí jen zaplacené objednávky:

```php
final class PaidOrders extends \ArrayObject
{
    public function add(array $order): void
    {
        if (!$order['paid']) {
            throw new \InvalidArgumentException('Do PaidOrders patří jen zaplacené objednávky.');
        }

        $this->append($order);
    }

    public function totalInCents(): int { /* … */ }
}
```

Vypadá to úsporně: `count()`, iterace i hranaté závorky jsou zadarmo. Jenže:

```php
$orders->add($unpaid);      // InvalidArgumentException — jak má být
$orders->append($unpaid);   // prošlo bez chyby
```

**Pravidlo platí jen pro tu jednu cestu, kterou jsme napsali.** Demo měří, že jich je celkem sedmadvacet.

---

## Mechanika

### 1. Vytvoř pole pro předka a naplň ho v konstruktoru

```php
final class PaidOrders extends \ArrayObject
{
    private \ArrayObject $orders;

    public function __construct()
    {
        parent::__construct();
        $this->orders = new \ArrayObject();
    }
```

**Po tomhle kroku platí:** dědičnost tam pořád je, delegát zatím nikdo nepoužívá. Testy procházejí, nic se nezměnilo.

### 2. Přesměruj vlastní metody na delegáta, jednu po druhé

```php
public function add(array $order): void
{
    // …kontrola…
    $this->orders->append($order);
}

public function totalInCents(): int
{
    $sum = 0;

    foreach ($this->orders as $order) {
        $sum += $order['totalInCents'];
    }

    return $sum;
}
```

**Po tomhle kroku platí:** data žijí v delegátovi. `extends` je pořád na místě, ale zděděný stav už je prázdný — a to je ta chvíle, kdy se pozná, jestli na něj někdo zvenčí nesahal.

### 3. Zahoď `extends` a doplň, co ti bude chybět

```php
final class PaidOrders implements \Countable, \IteratorAggregate
{
    /** @var list<array{id: int, paid: bool, totalInCents: int}> */
    private array $orders = [];
```

**Po tomhle kroku platí:** třída má jen to, co jsme napsali. Tady se objeví účet — `count()` a `foreach` je potřeba dodat ručně přes `Countable` a `IteratorAggregate`.

> [!NOTE]
> **Kde se dá zastavit:** po kroku 2 už je stav uvnitř a hodně z toho rizika je pryč, ale `extends` pořád nabízí cesty dovnitř. **Krok 3 je ten, kvůli kterému se to dělá** — zastavit se před ním znamená zaplatit cenu a nedostat výsledek.

---

## Jak ověřit, že to funguje

Kroky 1 a 2 chování nemění. **Krok 3 ho mění**, a to hodně:

- zmizí sedmadvacet veřejných metod
- objekt přestane být `instanceof ArrayObject`

Proto před krokem 3 patří průchod voláními. Hledáš dvě věci: kdo používá zděděné metody a **kdo tu kolekci předává někam, kde se čeká předek** (typehint, `instanceof`, serializace).

Když je volajících moc, je to [Expand–Contract](../../System/ExpandContract/) v malém: nové rozhraní přidej, staré nech chvíli žít, pak zahoď.

---

## Co to stojí

| Cena | Kdy se vyplatí |
| ---- | -------------- |
| Ruční dopsání toho, co předek dával zadarmo | Jakmile si třída hlídá pravidlo, které jde obejít |
| Delegující metody, které jen předávají dál | Když jich je pár; když jich je dvacet, je dědičnost možná na místě |
| Objekt přestane projít tam, kde se čeká předek | **To je většinou ten cíl**, ne cena |

Demo to čísluje:

```
                                  dědičnost       delegace
veřejných metod celkem            27 metod        4 metody
z toho jsme jich napsali          2 metody        2 metody
řádků                             38              48
```

**Deset řádků navíc** za kolekci, u které je celé rozhraní naše.

---

## Kdy to nedělat

- ❌ **Podtřída opravdu *je* předek** a dá se všude zaměnit — pak dědičnost sedí a [LSP](../../../SoftwareDesign/Principles/SOLID.md#liskov-substitution-principle-lsp) neporušuje.
- ❌ **Delegovalo by se dvacet metod beze změny** — vznikne třída, která jen přeposílá.
- ❌ **Předek je framework a počítá s dědičností** (bázový kontroler, entita ORM) — jdeš proti nástroji.
- ❌ **Nikdo tu třídu nepředává ven a pravidlo obcházet nemá kdo** — pak je to riziko na papíře.

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| Zděděné metody se přepíšou na `throw` | Rozhraní pořád lže — metoda je vidět a nefunguje | Zahodit `extends`, ne zakazovat po jedné |
| Skončí se po kroku 2 | Zaplatil jsi cenu a cesty dovnitř zůstaly | Krok 3 je ten důvod |
| Delegát se vystaví getterem | `getOrders()->append()` obejde totéž co dřív | Ven jen kopie nebo iterátor |
| Refaktoring a změna chování v jednom commitu | Krok 3 mění rozhraní; nejde vrátit zvlášť | [Dva klobouky](../../TwoHats/) |
| Doplní se všechna rozhraní předka „pro jistotu" | `ArrayAccess` vrátí zpátky přesně tu díru, kterou jsi zavřel | Doplň jen to, co volající skutečně používají |

Poslední řádek stojí za zdůraznění. **`Countable` a `IteratorAggregate` jsou v pořádku — jsou jen ke čtení.** `ArrayAccess` je ta, která zápis vrátí zpátky.

---

## Nejzajímavější efekt: pravidlo, které nikdo neobcházel schválně

```
dědičnost: append($unpaid)        prošlo bez chyby
totalInCents() se změnil z        174000 → 1173000
delegace: append() vůbec není     ano, ta metoda neexistuje
```

Nikdo tu nebyl zlomyslný. **`append()` je na té kolekci vidět** — v našeptávači, v dokumentaci `ArrayObject`, v každém tutoriálu. Vypadá jako naprosto legitimní způsob, jak něco přidat.

A druhá, horší polovina:

```
dědičnost instanceof ArrayObject    ano
delegace instanceof ArrayObject     ne
```

Verze s dědičností **projde všude, kde se čeká `ArrayObject`** — včetně cizího kódu, který do ní bez ptaní zapíše. To je porušení [LSP](../../../SoftwareDesign/Principles/SOLID.md#liskov-substitution-principle-lsp): podtřída slibuje víc, než dokáže dodržet.

---

## Kam to vede

Po dokončení máš naplněnou [kompozici před dědičností](../../../SoftwareDesign/Principles/ObjectDesign.md#kompozice-před-dědičností) — třídu, jejíž rozhraní je celé její vlastní.

Kam pokračovat, závisí na tom, co je uvnitř:

| Když je delegát… | Pokračuj | Získáš |
| ---------------- | -------- | ------ |
| kolekce | [First Class Collection](../../../SoftwareDesign/ObjectCalisthenics/FirstClassCollection/) | Kolekce s doménovými metodami místo pole s pravidly kolem |
| objekt téhož rozhraní, který chceš obalit chováním | [Decorator](../../../SoftwareDesign/GoF/Structural/Decorator/) (GoF) | Přidané chování bez zásahu do původní třídy |
| zaměnitelný algoritmus | [Strategy](../../../SoftwareDesign/GoF/Behavioral/Strategy/) (GoF) | Varianta jako samostatná třída |

První řádek je náš případ. `PaidOrders` po refaktoringu **už First Class Collection je** — zbývá jí dát doménové metody místo těch, co jen odpovídají poli.

---

## Demo

```bash
php Refactoring/Code/ReplaceSuperclassWithDelegate/demo/run.php
```

Kolekce zaplacených objednávek, jednou zděděná z `ArrayObject`, podruhé s polem uvnitř. Demo nejdřív ověří, že **zamýšlené chování je v obou stejné**, a pak spočítá, co se zdědilo navíc: 27 veřejných metod, z nichž jsme napsali dvě.

Pak předvede, jak se pravidlo obejde jedním zavoláním `append()` — a že po něm `totalInCents()` vrátí 1 173 000 místo 174 000. Nakonec porovná `instanceof` a spočítá cenu: deset řádků navíc.

---

## Související

| Dokument | Vztah |
| -------- | ----- |
| [Kompozice před dědičností](../../../SoftwareDesign/Principles/ObjectDesign.md#kompozice-před-dědičností) | **Princip, který se tím naplní.** |
| [LSP](../../../SoftwareDesign/Principles/SOLID.md#liskov-substitution-principle-lsp) | Co porušovala ta dědičnost. |
| [First Class Collection](../../../SoftwareDesign/ObjectCalisthenics/FirstClassCollection/) | Kam to vede, když je delegát kolekce. |
| [Encapsulate Collection](../EncapsulateCollection/) | Sourozenec: totéž pro pole uvnitř třídy, která nic nedědí. |
| [Decorator](../../../SoftwareDesign/GoF/Structural/Decorator/) (GoF) | Kam to vede, když delegát a obal mají totéž rozhraní. |
| [Expand–Contract](../../System/ExpandContract/) | Když zděděné metody volá i kód mimo repozitář. |
| [Dva klobouky](../../TwoHats/) | Krok 3 mění rozhraní — patří do vlastního commitu. |
| [Extract Class](../ExtractClass/) | Když se při tom ukáže, že ta třída dělá dvě věci. |
| [Replace Type Code with Subclasses](../ReplaceTypeCodeWithSubclasses/) | Tentýž pohyb od dědičnosti k delegaci, jen u druhu věci. |

---

## Původ

|             |                    |
| ----------- | ------------------ |
| **Autor**   | Martin Fowler      |
| **Rok**     | 1999               |
| **Zdroj**   | *Refactoring*, katalog |
| **Náročnost** | ●●○○○            |

Patří k původnímu katalogu z *Refactoringu* (1999), kde se jmenoval **Replace Inheritance with Delegation**. V druhém vydání ho Fowler přejmenoval na *Replace Superclass with Delegate* a staré jméno vede jako alias.

Jeho příklad je učebnicový a starší než kniha samotná — `Stack`, který dědí ze seznamu:

```
class List {...}
class Stack extends List {...}
```

```
class Stack {
  constructor() {
    this._storage = new List();
  }
}
```

`Stack` seznam **není** — jen ho potřebuje. Rozdíl je vidět na tom, že seznam umí vložit doprostřed, a zásobník to umět nesmí.

Dvojka na náročnosti je za krok 3. Kroky 1 a 2 jsou mechanické, ale zahození `extends` je **změna veřejného rozhraní** — a u třídy, která se předává ven, je potřeba vědět kam.

---

## Zdroje

- Martin Fowler: [*Replace Superclass with Delegate*](https://refactoring.com/catalog/replaceSuperclassWithDelegate.html), katalog refaktoringů
- Martin Fowler: [*Replace Subclass with Delegate*](https://refactoring.com/catalog/replaceSubclassWithDelegate.html) — jiná technika, snadno se plete
- Martin Fowler: [*Refactoring: Improving the Design of Existing Code*](https://martinfowler.com/books/refactoring.html), 2. vydání, 2018

---

<details>
<summary>Metadata techniky</summary>

```yaml
name: Replace Superclass with Delegate
aliases: [Replace Inheritance with Delegation]
level: code
author: Martin Fowler
year: 1999
duration: hodiny
reversible: ano — kroky 1 a 2 nic nemění
requires_tests: ano
difficulty: 2
tags: [dědičnost, delegace, kompozice, LSP, veřejné rozhraní]
leads_to: [FirstClassCollection, Decorator]
related: [EncapsulateCollection, ExtractClass, ExpandContract, TwoHats]
status: done
```

</details>
