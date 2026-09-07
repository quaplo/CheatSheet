# Replace Constructor with Factory Method

> [← zpět na Refaktoring kódu](../)

> **V jedné větě:** Nahraď `new` s nečitelnými parametry pojmenovanými továrnami — jedna na každou situaci, ve které objekt vzniká.

> [!NOTE]
> V PHP má tenhle refaktoring **jeden důvod navíc**, který v Javě ani C# neplatí: jazyk nezná přetěžování, takže **statické továrny jsou jediný způsob, jak mít víc pojmenovaných cest k objektu**. [Podrobněji níž](#proč-to-v-php-nejde-jinak).

---

## Kdy po tom sáhnout

**Poznáš to podle:**

- na místě volání **nepoznáš, co se děje** — `new Order($n, $i, $cid, null, false, false)`
- v podpisu jsou **boolean parametry**, které přepínají chování
- polovina parametrů je `null`, protože „v tomhle případě se nepoužije"
- objekt vzniká v **různých situacích** a každá potřebuje jiné parametry
- validace je **až u volajícího**, protože konstruktor bere všechno
- objekt jde vytvořit ve **stavu, který v doméně neexistuje**

```php
public function __construct(
    public readonly string $number,
    public readonly array $items,
    public readonly ?string $customerId,
    public readonly ?string $partnerCode,
    public readonly bool $isPaid,
    public readonly bool $skipStockCheck,
) {}
```

```
new Order($n, $i, $customerId, null, false, false)
new Order($n, $i, null, $partnerCode, true, true)
new Order($n, $i, $cid, $pc, (bool) $row[…], false)
```

**Tři volání téhož konstruktoru, tři úplně jiné situace.** Rozdíl je v pořadí nullů a booleanů — a ten se při čtení nedá udržet v hlavě.

Fowlerův příklad v katalogu je totéž v miniatuře:

```javascript
// před — co znamená 'E'?
leadEngineer = new Employee(document.leadEngineer, 'E');

// po
leadEngineer = createEngineer(document.leadEngineer);
```

---

## Co je na tom skutečně špatně

Nečitelnost je ten menší problém. Ten větší se dá spočítat:

```
parametrů konstruktoru          6
z toho bool                     2
z toho nullable                 2
kombinací jen z těchhle čtyř    16
smysluplných situací            3
```

**Šestnáct kombinací, ze kterých dávají smysl tři.** Zbylých třináct jde vytvořit a nic je nezastaví:

```
zákazník            alice
partner             PARTNER-X
vytvořeno?          ano — a nikdo se neptal
```

Objednávka od zákazníka **i** od partnera současně. Takový stav v doméně neexistuje, ale konstruktor ho vyrobí — a taky prázdnou objednávku bez jediné položky.

**Konstruktor, který bere všechno, nemůže nic hlídat.** Každá situace má jiná pravidla a jeden podpis je nepojme.

---

## Mechanika

Tenhle refaktoring má jednu příjemnou vlastnost: **dá se dělat úplně postupně**, protože nová cesta může existovat vedle staré libovolně dlouho.

### 0. Testy

Na chování, které se má zachovat.

### 1. Přidej první továrnu vedle konstruktoru

```php
public static function placedByCustomer(string $number, array $items, string $customerId): self
{
    return new self($number, $items, $customerId, null, isPaid: false, skipStockCheck: false);
}
```

Konstruktor zůstává veřejný. **Nic se zatím nerozbilo.**

**Po tomhle kroku platí:** existuje pojmenovaná cesta; starou pořád jde použít.

### 2. Přepni volající, jednoho po druhém

```php
// bylo
new Order($number, $items, $customerId, null, false, false);

// je
Order::placedByCustomer($number, $items, $customerId);
```

**Po tomhle kroku platí:** čím dál míň míst volá konstruktor přímo.

### 3. Přidej zbylé továrny

Jedna na každou situaci. **Pojmenuj je podle toho, co se stalo** — ne podle parametrů:

```php
Order::placedByCustomer(...)      // ne createWithCustomer()
Order::importedFromPartner(...)   // ne createFromPartner()
Order::reconstitute($row)         // ne fromArray()
```

**Po tomhle kroku platí:** pro každou situaci existuje cesta.

### 4. Přesuň pravidla do továren

Až teď, když každá továrna ví, o jakou situaci jde:

```php
public static function placedByCustomer(string $number, array $items, string $customerId): self
{
    if ($items === []) {
        throw new DomainException('Objednávka musí mít aspoň jednu položku.');
    }
    // …
}
```

**Rekonstrukce z databáze pravidla nekontroluje** — ten stav kdysi platný byl a kdyby se pravidla změnila, nešlo by načíst stará data. Rozdíl mezi vytvořením a rekonstrukcí rozebírá [Factory](../../../SoftwareDesign/DDD/Factory/#vytvoření-není-rekonstrukce).

**Po tomhle kroku platí:** neplatný objekt nevznikne — pokud se nepoužije konstruktor.

### 5. Udělej konstruktor soukromým

```php
private function __construct(/* … */) {}
```

```
                          Before          After
konstruktor               public          private
pojmenovaných cest        0               3
```

**Po tomhle kroku platí:** hotovo. Nesmyslná kombinace se nedá vytvořit — **ne proto, že by ji někdo kontroloval, ale protože k ní nevede cesta.**

> [!NOTE]
> **Kde se dá zastavit:** kdekoli mezi kroky 1 a 4. Každá přidaná továrna je zlepšení sama o sobě a konstruktor může zůstat veřejný libovolně dlouho. Teprve krok 5 je nevratný v tom smyslu, že rozbije zbylá volání `new` — proto se dělá až na konci.

---

## Proč to v PHP nejde jinak

```
Java, C#                přetížení konstruktoru — víc konstruktorů
PHP                     jeden konstruktor, tečka
důsledek pro PHP        statické továrny jsou JEDINÁ cesta
```

V jazycích s přetěžováním je tenhle refaktoring o **čitelnosti** a o možnosti vrátit podtyp. V PHP je navíc **jediný způsob, jak mít víc pojmenovaných způsobů vytvoření** — a proto se v PHP kódu vyskytuje mnohem častěji, často aniž by mu někdo říkal refaktoring.

Ostatně samotné PHP to dělá taky: **`DateTimeImmutable::createFromFormat()`** je pojmenovaná továrna v jádru jazyka a nikdo ji nevnímá jako vzor.

---

## Jak ověřit, že to funguje

- **Testy projdou beze změny**, dokud je konstruktor veřejný. V kroku 5 spadnou ta místa, která ho volají přímo — a je správně, že spadnou.
- **Zkus vytvořit nesmyslnou kombinaci.** Po refaktoringu k ní nesmí vést cesta.
- **Zkontroluj, že rekonstrukce nekontroluje pravidla.** Test: načti data, která by podle dnešních pravidel neprošla; musí se načíst.
- **Grep na `new Order(`** — mimo samotnou třídu nesmí zbýt nic.

---

## Co to stojí

| Cena | Kdy se vyplatí |
| ---- | -------------- |
| **Víc metod na třídě** — jedna na situaci | Když jsou situace opravdu různé |
| **Volající se musí přepsat** | Když je jich míň než kombinací, které konstruktor připouští |
| **Statické metody se hůř nahrazují v testech** | Když je objekt hodnotový a nahrazovat se nepotřebuje |
| **Rozhodnutí, kolik situací vlastně je** | Vždycky — a je to užitečná otázka sama o sobě |

Třetí řádek je jediná reálná námitka. Statická továrna se v testu nedá podstrčit — ale u objektu, který jen drží data a pravidla, to nevadí. **Když se objekt potřebuje v testu nahradit, nepatří sem továrna, ale [rozhraní a injektovaná závislost](../../../SoftwareDesign/Architecture/PortsAndAdapters/).**

---

## Kdy to nedělat

- ❌ **Objekt vzniká jedním způsobem a konstruktor má dva parametry.** `new Money(100, 'CZK')` je čitelné.
- ❌ **Je to DTO nebo read model.** Průchozí data pravidla nemají a továrna nic nepřidá.
- ❌ **Továrna by se jmenovala `create()`.** Když není jak ji pojmenovat, není víc situací — a refaktoring nemá cíl.
- ❌ **Objekt se v testech nahrazuje.** Pak potřebuješ rozhraní, ne továrnu.
- ❌ **Vytvoření vyžaduje závislosti** (generátor ID, kurzovní lístek). Pak to není statická továrna, ale [samostatná třída](../../../SoftwareDesign/DDD/Factory/#kde-továrna-žije).

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| Továrna se jmenuje `create()` nebo `make()` | Neřekne nic víc než `new` | Pojmenovat podle situace |
| Konstruktor zůstane veřejný navždy | Krok 5 se neudělá; nesmyslné stavy jdou pořád vytvořit | Dokončit |
| Všechny továrny kontrolují totéž | Každá situace má jiná pravidla | Pravidla podle situace |
| Rekonstrukce kontroluje invarianty | Nejdou načíst historická data | `reconstitute()` bez kontrol |
| Továrna vrací `null` při chybě | Volající zase musí znát pravidla | Výjimka s doménovým důvodem |
| Vznikne továrna na každou kombinaci parametrů | Deset továren místo tří situací | Situace, ne kombinace |
| Továrna si sahá do databáze | Přestala jít otestovat; je z ní služba | Data na vstupu |

---

## Kam to vede

Po dokončení máš [**Factory**](../../../SoftwareDesign/DDD/Factory/) — pojmenované vytvoření s vynucenými invarianty. Ten dokument navazuje **rozdílem mezi vytvořením a rekonstrukcí** (a proč `reconstitute()` pravidla kontrolovat nesmí) a otázkou, kdy továrna patří na třídu a kdy do samostatné.

Když továrna podle vstupu vrací **různé typy**, jsi u [**Factory Method**](../../../SoftwareDesign/GoF/Creational/FactoryMethod/) z GoF — a to je jiný problém: ne platný stav, ale polymorfismus.

| Když továrna | Cíl |
| ------------ | --- |
| vytváří jeden typ v platném stavu | [Factory](../../../SoftwareDesign/DDD/Factory/) (DDD) |
| vybírá podle vstupu mezi typy | [Factory Method](../../../SoftwareDesign/GoF/Creational/FactoryMethod/) (GoF) |
| skládá objekt po částech | [Builder](../../../SoftwareDesign/GoF/Creational/Builder/) (GoF) |

---

## Demo

```bash
php Refactoring/Code/ReplaceConstructorWithFactoryMethod/demo/run.php
```

Objednávka, která vzniká třemi způsoby přes jeden konstruktor o šesti parametrech. Demo **spočítá reflexí, kolik kombinací ten podpis připouští** — šestnáct, z nichž dávají smysl tři — a pak jednu z těch nesmyslných vytvoří: objednávku, která má zákazníka i partnera současně. Po refaktoringu ukáže, že k takové kombinaci **nevede cesta**, a ověří, že konstruktor je soukromý a pojmenované cesty jsou tři.

---

## Související

| Dokument | Vztah |
| -------- | ----- |
| [Factory](../../../SoftwareDesign/DDD/Factory/) (DDD) | **Cíl refaktoringu.** Navazuje rozdílem mezi vytvořením a rekonstrukcí. |
| [Factory Method](../../../SoftwareDesign/GoF/Creational/FactoryMethod/) (GoF) | Jiný cíl — když továrna vybírá mezi typy, ne hlídá stav. |
| [Builder](../../../SoftwareDesign/GoF/Creational/Builder/) (GoF) | Když je parametrů tolik, že je potřeba je sbírat postupně. |
| [Replace Primitive with Object](../ReplacePrimitiveWithObject/) | Často navazuje: `fromString()` je tentýž vzor u hodnoty. |
| [Fail Fast](../../../SoftwareDesign/Principles/ObjectDesign.md#fail-fast) | Čeho se dosáhne — neplatný objekt nevznikne. |
| [Aggregate](../../../SoftwareDesign/DDD/Aggregate/) (DDD) | Nejčastější typ, u kterého se to dělá; invarianty musí platit od první chvíle. |

---

## Původ

|             |                    |
| ----------- | ------------------ |
| **Autor**   | Martin Fowler      |
| **Rok**     | 1999, přepracováno 2018 |
| **Zdroj**   | *Refactoring*, katalog |
| **Náročnost** | ●●○○○            |

V prvním vydání se jmenoval **Replace Constructor with Factory Method**; druhé vydání ho přejmenovalo na **Replace Constructor with Factory Function**, protože příklady jsou v JavaScriptu, kde se používají funkce místo statických metod. V PHP platí původní název.

Náročnost je dvojka a mechanika patří k nejbezpečnějším v [celé složce](../) — nová cesta může existovat vedle staré libovolně dlouho a zastavit se dá kdekoli. Jediné dvě věci, které stojí za pozornost:

- **Krok 5 je ten, který se neudělá.** Dokud je konstruktor veřejný, nesmyslné stavy jdou pořád vytvořit — a refaktoring přinesl jen čitelnost.
- **Pojmenování rozhoduje o užitku.** Továrna jménem `create()` je `new` s delším zápisem. Když se situace nedá pojmenovat, možná to není samostatná situace.

Za zmínku stojí, že v PHP se tenhle vzor používá běžně, aniž by mu někdo říkal refaktoring — `DateTimeImmutable::createFromFormat()` je v jádru jazyka. Tím spíš stojí za to vědět, **proč** se to dělá: ne kvůli hezčímu zápisu, ale proto, že jeden podpis nepojme pravidla víc různých situací.

---

## Zdroje

- Martin Fowler: [*Replace Constructor with Factory Function*](https://refactoring.com/catalog/replaceConstructorWithFactoryFunction.html) — katalog online
- Martin Fowler: *Refactoring*, 2. vydání, Addison-Wesley, 2018

---

<details>
<summary>Metadata techniky</summary>

```yaml
name: Replace Constructor with Factory Method
level: code
author: Martin Fowler
year: 1999
duration: hodiny
reversible: ano
requires_tests: ano
difficulty: 2
tags: [vytvoření, invarianty, pojmenované konstruktory, boolean parametry]
leads_to: [Factory, FactoryMethod]
related: [Factory, FactoryMethod, Builder, ReplacePrimitiveWithObject, Aggregate]
status: done
```

</details>
