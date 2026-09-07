# Introduce Parameter Object

> [← zpět na Refaktoring kódu](../)

> **V jedné větě:** Skupinu parametrů, které spolu pořád cestují, nahraď jedním objektem — a pak do něj přestěhuj pravidla, která se o nich pořád dokola opisují.

> [!IMPORTANT]
> Samotná výměna podpisu je jen úklid. **Vyplatí se to až tím, co se do toho objektu potom přistěhuje** — kontrola platnosti a chování, které předtím nemělo kde bydlet. Když se nepřistěhuje nic, zůstala jen jedna třída navíc.

---

## Kdy po tom sáhnout

**Poznáš to podle:**

- tatáž dvojice nebo trojice parametrů se opakuje ve třech a víc metodách
- při volání si musíš ověřovat pořadí — `($from, $to)`, nebo `($to, $from)`?
- pravidlo o těch parametrech („`from` nesmí být po `to`") **nikde není**, protože není kam ho napsat
- když přidáváš čtvrtý parametr do skupiny, měníš pět podpisů
- v testech pořád opisuješ tutéž dvojici hodnot

Ten druhý bod má vlastní jméno — **data clump**, shluk dat. Když spolu tři hodnoty chodí všude, obvykle mají v doméně jméno, které ještě nikdo nenapsal.

---

## Předtím

```php
final class SalesReport
{
    public function totalFor(int $fromTs, int $toTs): int
    {
        foreach ($this->orders as $order) {
            if ($order['paidAt'] >= $fromTs && $order['paidAt'] <= $toTs) {
                // …
    }

    public function countFor(int $fromTs, int $toTs): int
    {
        foreach ($this->orders as $order) {
            if ($order['paidAt'] >= $fromTs && $order['paidAt'] <= $toTs) {
                // …
    }

    public function averageFor(int $fromTs, int $toTs): int { /* … */ }
}
```

Dvě věci, které tam chybí a nemají kam: **co znamená „spadá do období"** (je to napsané dvakrát) a **jestli to období vůbec dává smysl** (to se neptá nikdo).

---

## Mechanika

### 1. Vytvoř třídu s těmi parametry, zatím prázdnou

```php
final readonly class DateRange
{
    public function __construct(public int $fromTs, public int $toTs)
    {
    }
}
```

**Po tomhle kroku platí:** nic se nezměnilo, nic ji nepoužívá, testy procházejí. Tenhle krok jde commitnout sám.

### 2. Přidej ji jako parametr, staré nech být

```php
public function totalFor(int $fromTs, int $toTs, ?DateRange $period = null): int
```

**Po tomhle kroku platí:** volající se nemusí měnit. Tenhle krok je nutný jen tehdy, když metodu volá **kód mimo tvůj repozitář** — jinak ho přeskoč a jdi rovnou na krok 3.

> U veřejného rozhraní je tohle [Expand–Contract](../../System/ExpandContract/) v malém.

### 3. Přepiš volající a starý podpis zahoď

```php
public function totalFor(DateRange $period): int
```

**Po tomhle kroku platí:** podpisy jsou kratší, ale hodnota zatím žádná. Kdyby ses zastavil tady, byla to jen třída navíc.

### 4. Přestěhuj do objektu pravidlo, které se opisovalo

```php
public function includes(int $timestamp): bool
{
    return $timestamp >= $this->fromTs && $timestamp <= $this->toTs;
}
```

**Po tomhle kroku platí:** „spadá do období" je na jednom místě a dá se otestovat bez sestavování reportu.

### 5. Přidej kontrolu, která předtím neměla kde být

```php
public function __construct(public int $fromTs, public int $toTs)
{
    if ($fromTs > $toTs) {
        throw new \InvalidArgumentException(/* … */);
    }
}
```

**Po tomhle kroku platí:** nesmyslné období nelze sestavit. **Tenhle krok mění chování** — dřív se tiše vrátila nula, teď letí výjimka. Patří proto do vlastního commitu a s vlastním testem; viz [dva klobouky](../../TwoHats/).

> [!NOTE]
> **Kde se dá zastavit:** po kroku 3 máš hotový refaktoring, který nic nemění — ten je bezpečný vždycky. Kroky 4 a 5 jsou to, kvůli čemu se to dělá, ale krok 5 už není refaktoring.

---

## Jak ověřit, že to funguje

Kroky 1–4 chování nemění, takže stačí stávající testy. **Krok 5 je jiný případ** a potřebuje vlastní test: nesmyslné období musí selhat.

Užitečné je při tom projít, co dělaly volající s neplatným vstupem dosud. Demo ukazuje, že se tiše vracela nula — takže je potřeba zjistit, jestli na té nule někdo nestaví.

---

## Co to stojí

| Cena | Kdy se vyplatí |
| ---- | -------------- |
| Třída navíc | Jakmile ta skupina parametrů je ve třech a víc metodách |
| Sestavení objektu na každém volání | Když se objekt dá vytvořit jednou a předávat dál |
| Krok 5 mění chování | Když tiché ignorování nesmyslného vstupu je horší než výjimka |

Demo to měří:

```
                                      předtím     potom
parametrů v týchž třech metodách      6           3
míst, která znají „spadá do období"   2 místa     1 místo
```

---

## Kdy to nedělat

- ❌ **Parametry spolu chodí náhodou** — `($userId, $limit)` není období, je to dva nesouvisející údaje.
- ❌ **Skupina je jen ve dvou metodách a víc jich nebude** — [pravidlo tří](../../../SoftwareDesign/Principles/Simplicity.md#pravidlo-tří) platí i tady.
- ❌ **Objekt by neměl co dělat** — když se do něj nedá přestěhovat žádné pravidlo, je to jen jiný způsob zápisu téhož.
- ❌ **Skupina cestuje mezi procesy a nic neumí** — pak to není parameter object, ale [DTO](../../../SoftwareDesign/Glossary.md#dto--data-transfer-object), a platí pro něj jiná pravidla.

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| Skončí se po kroku 3 | Zůstala třída navíc a nic víc | Kroky 4 a 5 jsou ten důvod |
| Objekt zůstane měnitelný | Období se dá po předání změnit a volající o tom neví | `readonly`, jako [Value Object](../../../SoftwareDesign/DDD/ValueObject/) |
| Do objektu se nacpe i to, co k němu nepatří | Z období se stane `ReportParams` se šesti nesouvisejícími poli | Jen to, co spolu skutečně cestuje |
| Validace se přidá ve stejném commitu jako refaktoring | Mění se chování a nejde to vrátit zvlášť | Krok 5 = vlastní commit |
| Objekt se jmenuje podle použití | `ReportPeriod` v jedné třídě, `Interval` v druhé — a jsou to titíž | Jméno z domény, jedno pro všechny |
| Původní podpis se nechá „pro jistotu" | Dvě cesty do téhož a jedna z nich bez kontroly | Zahoď ho; u veřejného API [Expand–Contract](../../System/ExpandContract/) |

---

## Nejzajímavější efekt: prohozené argumenty

Tohle je důvod, proč se ten refaktoring vyplatí i tam, kde podpisy nikoho netrápí. Demo se v call site „uklepne" a zamění pořadí:

```
předtím: totalFor($to, $from)         vrátí 0, bez chyby
potom: new DateRange($to, $from)      InvalidArgumentException hned při sestavení
```

Správný výsledek je 155 000. **Verze bez objektu vrátila nulu a nikde se nic nestalo** — ta chyba by odešla do reportu.

> [!IMPORTANT]
> Nezachrání to typový systém — obě hodnoty jsou `int` a PHP je klidně prohodí. Zachrání to **konstruktor**: je to jediné místo, kde se dá ta podmínka zkontrolovat, a proto se tam vejde. Než objekt existoval, žádné takové místo nebylo.

---

## Kam to vede

Po dokončení máš [Value Object](../../../SoftwareDesign/DDD/ValueObject/) — neměnnou hodnotu bez identity, která zná pravidla o sobě samé. `DateRange` se dvěma metodami a kontrolou v konstruktoru **už jím je**.

Rozdíl proti sousednímu refaktoringu je v tom, s čím začínáš:

| | [Replace Primitive with Object](../ReplacePrimitiveWithObject/) | Introduce Parameter Object |
| --- | --- | --- |
| Výchozí stav | **jedna** hodnota špatného typu (`string $email`) | **skupina** hodnot, které chodí spolu |
| Spouštěč | typ nic negarantuje | podpisy se opakují, pravidlo nemá kde být |
| Výsledek | `Email` | `DateRange` |

Oba končí u téhož vzoru a často se doplňují: nejdřív skupinu sloučíš, pak zjistíš, že jedna z jejích částí si zaslouží vlastní typ.

**Než tam půjdeš, přečti si u Value Objectu i sekci „kdy nepoužít".** Ne každá skupina parametrů má být hodnota.

---

## Demo

```bash
php Refactoring/Code/IntroduceParameterObject/demo/run.php
```

Report o třech metodách se stejnou dvojicí parametrů. Demo ověří, že se **chování pro platné vstupy nezměnilo**, spočítá, co se zmenšilo, a pak předvede prohozené argumenty — tiše špatný výsledek proti výjimce při sestavení.

Nakonec ukáže, co se do objektu přistěhovalo (`includes()`, `days()`) a co to umožnilo: metodu `dailyAverageFor()`, která má tři řádky, **protože `days()` už existuje**. Ve verzi bez objektu by byla čtvrtou metodou, která zná vnitřek období.

---

## Související

| Dokument | Vztah |
| -------- | ----- |
| [Value Object](../../../SoftwareDesign/DDD/ValueObject/) (DDD) | **Kam to vede.** Neměnná hodnota, která zná pravidla o sobě. |
| [Replace Primitive with Object](../ReplacePrimitiveWithObject/) | Sousední refaktoring: jedna hodnota místo skupiny. |
| [Extract Class](../ExtractClass/) | Totéž pro pole třídy místo parametrů metody. |
| [Expand–Contract](../../System/ExpandContract/) | Když ty podpisy volá i někdo mimo tvůj repozitář. |
| [Dva klobouky](../../TwoHats/) | Proč krok 5 (validace) patří do vlastního commitu. |
| [Pravidlo tří](../../../SoftwareDesign/Principles/Simplicity.md#pravidlo-tří) | Kdy je opakování ještě náhoda. |
| [DTO](../../../SoftwareDesign/Glossary.md#dto--data-transfer-object) | Když skupina jen cestuje a nic neumí — jiný případ, jiná pravidla. |

---

## Původ

|             |                    |
| ----------- | ------------------ |
| **Autor**   | Martin Fowler      |
| **Rok**     | 1999               |
| **Zdroj**   | *Refactoring*, katalog |
| **Náročnost** | ●●○○○            |

Patří k původnímu katalogu z *Refactoringu* (1999) a je i v druhém vydání. Fowlerův příklad je tentýž jako ten náš — tři metody nad obdobím:

```
function amountInvoiced(startDate, endDate) {...}
function amountReceived(startDate, endDate) {...}
function amountOverdue(startDate, endDate) {...}
```

```
function amountInvoiced(aDateRange) {...}
function amountReceived(aDateRange) {...}
function amountOverdue(aDateRange) {...}
```

Dvojka na náročnosti není za mechaniku — ta je přímočará a IDE ji z velké části udělá. Je za dvě rozhodnutí:

- **Poznat, co spolu skutečně cestuje.** Dva parametry vedle sebe ještě nejsou skupina.
- **Nepřidat validaci ve stejném commitu.** Krok 5 mění chování, a je proto potřeba vědět, jestli na tichém selhání někdo nestaví.

---

## Zdroje

- Martin Fowler: [*Introduce Parameter Object*](https://refactoring.com/catalog/introduceParameterObject.html), katalog refaktoringů
- Martin Fowler: [*Refactoring: Improving the Design of Existing Code*](https://martinfowler.com/books/refactoring.html), 2. vydání, 2018

---

<details>
<summary>Metadata techniky</summary>

```yaml
name: Introduce Parameter Object
level: code
author: Martin Fowler
year: 1999
duration: minuty až hodiny
reversible: ano — kroky 1–4 nic nemění
requires_tests: ano
difficulty: 2
tags: [parametry, data clump, value object, validace]
leads_to: [ValueObject]
related: [ReplacePrimitiveWithObject, ExtractClass, ExpandContract, TwoHats]
status: done
```

</details>
