# Decompose Conditional

> [← zpět na Refaktoring kódu](../)

> **V jedné větě:** Podmínku i obě její větve vytáhni do pojmenovaných metod — kód dělá pořád totéž, ale hlavní metoda se dá přečíst nahlas.

> [!IMPORTANT]
> Tenhle refaktoring **neubírá větvení**. Kdo čeká, že po něm klesne složitost, bude zklamaný. Ubírá to, kolik toho musíš přečíst, **než se rozhodneš, jestli tě to zajímá**.

---

## Kdy po tom sáhnout

**Poznáš to podle:**

- podmínka má tři a víc částí a musíš si ji přečíst dvakrát
- větev `if`u je delší než pět řádků a končí `return`em, který jsi mezitím ztratil z očí
- ptáš se „co se stane, když je to na dobírku" a musíš projít celou metodu
- v code review napíšeš „co tahle podmínka znamená?" a autor odpoví jednou větou — **kterou v kódu nikde nenajdeš**
- metoda má jméno, které popisuje výsledek (`amountFor`), ale její tělo popisuje postup

Ten čtvrtý bod je nejspolehlivější příznak. **Když se dá podmínka vysvětlit jednou větou, ta věta má být jméno metody.**

---

## Předtím

Kolik vrátit za zrušenou objednávku:

```php
public function amountFor(array $order, int $now): int
{
    if ($order['shippedAt'] === null
        && $now - $order['paidAt'] < 14 * 24 * 3600
        && $order['paymentMethod'] !== 'dobirka') {
        $amount = $order['totalInCents'];

        if ($order['giftWrapped']) {
            $amount -= 2500;
        }

        return $amount;
    }

    if ($order['shippedAt'] !== null
        && $now - $order['shippedAt'] < 14 * 24 * 3600
        && $order['returnedAt'] !== null) {
        $amount = $order['totalInCents'] - $order['shippingInCents'];

        if ($order['giftWrapped']) {
            $amount -= 2500;
        }

        if ($amount < 0) {
            $amount = 0;
        }

        return $amount;
    }

    return 0;
}
```

**Ten kód je správně.** Projde všechny testy. Jen abys věděl, co dělá, musíš přečíst všech dvaatřicet řádků.

---

## Mechanika

Fowlerův postup má dva kroky. Rozepsáno na to, co se u nich děje:

### 1. Vytáhni podmínku do metody, jejíž jméno ji vysvětluje

```php
if ($this->isWithdrawalBeforeShipping($order, $now)) {
    $amount = $order['totalInCents'];
    // …tělo zatím beze změny
```

```php
private function isWithdrawalBeforeShipping(array $order, int $now): bool
{
    return $order['shippedAt'] === null
        && $now - $order['paidAt'] < 14 * 24 * 3600
        && $order['paymentMethod'] !== 'dobirka';
}
```

**Po tomhle kroku platí:** hlavní metoda je stejně dlouhá, ale na prvním řádku `if`u už stojí, o co jde. Testy procházejí — nic se nezměnilo.

> Jméno hledej v doméně, ne v implementaci. `isShippedAtNullAndPaidRecently` je popis kódu; `isWithdrawalBeforeShipping` je popis pravidla.

### 2. Vytáhni každou větev do metody, jejíž jméno ji vysvětluje

```php
public function amountFor(array $order, int $now): int
{
    if ($this->isWithdrawalBeforeShipping($order, $now)) {
        return $this->fullRefund($order);
    }

    if ($this->isReturnAfterDelivery($order, $now)) {
        return $this->refundWithoutShipping($order);
    }

    return 0;
}
```

**Po tomhle kroku platí:** hlavní metoda je celá vidět naráz a čte se jako tři věty. Detaily jsou o patro níž a nemusíš do nich chodit.

### 3. Vytáhni konstanty, které ta jména odhalila

```php
private const int WITHDRAWAL_PERIOD_SECONDS = 14 * 24 * 3600;
private const int GIFT_WRAP_PRICE = 2500;
```

**Po tomhle kroku platí:** `14 * 24 * 3600` se nemusí luštit ani na dvou místech udržovat.

> [!NOTE]
> **Kde se dá zastavit:** po kterémkoli kroku. Každý z nich je samostatné zlepšení a žádný nemění chování. Nejvíc se přitom vyplatí krok 1 — pojmenovaná podmínka pomůže i tehdy, když tělo zůstane, jak bylo.

---

## Jak ověřit, že to funguje

Refaktoring nemění chování, takže stačí spustit testy, které už existují. Když neexistují, patří sem [charakterizační testy](../../CharacterizationTests/) — a hodí se pro ně sada, která projde **všemi větvemi včetně okrajů** (v demu 25 kombinací).

Užitečná pomůcka: vytažená podmínka je od téhle chvíle **samostatně testovatelná**. `isReturnAfterDelivery()` se dá ověřit bez toho, abys počítal částku.

---

## Co to stojí

| Cena | Kdy se vyplatí |
| ---- | -------------- |
| Víc metod v jedné třídě (v demu 1 → 6) | Jakmile metodu čte někdo jiný než ten, kdo ji psal |
| Skákání po souboru při čtení detailu | Když tě detail zajímá, ale ne vždycky — a ne celý |
| Nutnost vymyslet jména | Vždycky, protože ta jména stejně existují — jen nejsou napsaná |

Demo to čísluje poctivě:

```
otázka                                        předtím       potom
„co ta metoda vlastně dělá?“                  32 řádků      12 řádků
„kolik dostane, kdo vrátil doručené zboží?“   32 řádků      28 řádků
```

**Na druhou otázku vyjde skoro totéž.** Decompose Conditional nezkracuje čtení — umožňuje si vybrat, co číst nemusíš.

---

## Kdy to nedělat

- ❌ **Podmínka je jednoduchá a krátká** (`if ($order->isPaid())`) — jméno by jen zopakovalo, co už je vidět.
- ❌ **Větev je jeden výraz** — `return 0;` se do metody vytahovat nemusí.
- ❌ **Nejsou testy a nedají se rychle udělat** — začni [charakterizačními testy](../../CharacterizationTests/).
- ❌ **Podmínek je pět a všechny rozhodují o typu objednávky** — pak nemáš problém s čitelností, ale s návrhem, a patří sem rovnou [Replace Conditional with Polymorphism](../ReplaceConditionalWithPolymorphism/).

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| Jméno podle implementace | `isShippedAtNull()` neřekne víc než ta podmínka sama | Jméno podle **pravidla**, ne podle kódu |
| Při vytahování se „opraví" i chování | Přestává to být refaktoring a nejde to vrátit | [Dva klobouky](../../TwoHats/) — oprava do vlastního commitu |
| Vytáhne se podmínka, větve zůstanou | Půl práce: hlavní metoda je pořád dlouhá | Krok 2 patří k tomu |
| Metody dostanou příznak (`process($isFull)`) | Vznikne nová podmínka uvnitř, jen schovaná | Dvě metody se dvěma jmény |
| Vytahuje se všechno, i jednořádkové větve | Soubor se rozpadne na drobky a čte se hůř | Vytahuj to, co má jméno |
| Jméno se nedá vymyslet, tak se to vzdá | Právě to je ta informace | Nepojmenovatelná podmínka obvykle **dělá dvě věci** — rozděl ji |

Poslední řádek je nejcennější. Když jméno nejde vymyslet, není to nedostatek slovní zásoby — je to nález.

---

## Kam to vede

Rozklad není cíl, je to první krok. Dvě různá pokračování podle toho, **co ti na té podmínce vadilo**:

| Když se ukáže, že… | Pokračuj | Získáš |
| ------------------ | -------- | ------ |
| totéž pravidlo potřebuješ jinde, chceš ho skládat nebo se ptát „která část selhala" | [Specification](../../../SoftwareDesign/DDD/Specification/) | Pravidlo jako objekt, samostatně testovatelný a skladatelný |
| větví je víc a vybírají chování podle typu | [Replace Conditional with Polymorphism](../ReplaceConditionalWithPolymorphism/) → [Strategy](../../../SoftwareDesign/GoF/Behavioral/Strategy/) | Nová varianta jako nová třída, bez zásahu do `if`u |

První řádek je ten častější. `isWithdrawalBeforeShipping()` je [Specification](../../../SoftwareDesign/DDD/Specification/) o jeden krok dřív — pravidlo, které už má jméno, ale ještě nemá vlastní objekt.

**Než tam půjdeš, přečti si obojí až po sekci „kdy to nepoužít".** Rozložená podmínka je často dost dobrý konečný stav a další krok už se nemusí vyplatit.

---

## Demo

```bash
php Refactoring/Code/DecomposeConditional/demo/run.php
```

Táž metoda předtím a potom. Demo nejdřív ověří na 25 kombinacích objednávek, že se **chování nezměnilo**, a pak spočítá, co se změnilo:

```
                                  předtím       potom
metod                             1             6
rozhodovacích bodů celkem         9             8
nejvíc v jedné metodě             9             2
```

Měří se **rozhodovací body**, ne součet cyklomatických složitostí — ten by po rozdělení vyšel vyšší, i kdyby se větvení vůbec nezměnilo, protože každá metoda do něj přispívá základní jedničkou.

Nakonec demo ukáže, co rozklad odhalil: **sleva za dárkové balení byla v původní metodě napsaná dvakrát.** Nebylo to vidět, protože obě kopie byly schované uvnitř dvou různých větví.

---

## Související

| Dokument | Vztah |
| -------- | ----- |
| [Specification](../../../SoftwareDesign/DDD/Specification/) | Kam vede pojmenovaná podmínka, když ji potřebuješ i jinde. |
| [Replace Conditional with Polymorphism](../ReplaceConditionalWithPolymorphism/) | Kam vedou pojmenované větve, když jich přibývá. |
| [Comprehension refactoring](../../ComprehensionRefactoring/) | Tentýž pohyb jako způsob, jak cizímu kódu porozumět. |
| [Charakterizační testy](../../CharacterizationTests/) | Když k té metodě testy nejsou. |
| [Dva klobouky](../../TwoHats/) | Proč se při vytahování nesmí nic „při té příležitosti" opravit. |
| [Extract Class](../ExtractClass/) | Když vytažených metod přibude tolik, že tvoří vlastní téma. |
| [DRY](../../../SoftwareDesign/Principles/Simplicity.md#dry--dont-repeat-yourself) | Duplicita, kterou rozklad zviditelnil. |

---

## Původ

|             |                    |
| ----------- | ------------------ |
| **Autor**   | Martin Fowler      |
| **Rok**     | 1999               |
| **Zdroj**   | *Refactoring*, katalog |
| **Náročnost** | ●○○○○            |

Patří k původnímu katalogu z *Refactoringu* (1999) a je i v druhém vydání. Fowlerův příklad je dodnes tentýž — letní a mimosezónní sazba:

```
if (!aDate.isBefore(plan.summerStart) && !aDate.isAfter(plan.summerEnd))
    charge = quantity * plan.summerRate;
else
    charge = quantity * plan.regularRate + plan.regularServiceCharge;
```

```
if (summer())
    charge = summerCharge();
else
    charge = regularCharge();
```

Jednička na náročnosti je poctivá: mechanicky je to dvakrát Extract Method a zvládne to IDE. Těžké je jediné — **vymyslet ta jména**. A to je zároveň celá hodnota toho refaktoringu.

---

## Zdroje

- Martin Fowler: [*Decompose Conditional*](https://refactoring.com/catalog/decomposeConditional.html), katalog refaktoringů
- Martin Fowler: [*Refactoring: Improving the Design of Existing Code*](https://martinfowler.com/books/refactoring.html), 2. vydání, 2018

---

<details>
<summary>Metadata techniky</summary>

```yaml
name: Decompose Conditional
level: code
author: Martin Fowler
year: 1999
duration: minuty
reversible: ano — refaktoring nic nemění
requires_tests: ano
difficulty: 1
tags: [podmínky, pojmenování, čitelnost, extract method]
leads_to: [Specification, ReplaceConditionalWithPolymorphism]
related: [ComprehensionRefactoring, CharacterizationTests, TwoHats]
status: done
```

</details>
