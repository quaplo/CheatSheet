# Replace Type Code with Subclasses

> [← zpět na Refaktoring kódu](../)

> **V jedné větě:** Hodnotu, která říká *druh* věci (`'premium'`, `STATUS_SHIPPED`), nahraď typem — podtřídou, když se ten druh nemění, a objektem uvnitř, když se mění.

> [!IMPORTANT]
> **Zadání „Replace Type Code with State/Strategy" je jméno z prvního vydání.** Fowler obě techniky v druhém vydání sloučil do *Replace Type Code with Subclasses* a staré jméno vede jako alias (spolu s *Extract Subclass*). Rozhodnutí, které to staré jméno neslo, ale nezmizelo — **jen se přesunulo dovnitř**. Je to celý tenhle dokument.

---

## Čím se to liší od sousedního refaktoringu

Tenhle a [Replace Conditional with Polymorphism](../ReplaceConditionalWithPolymorphism/) končí často stejně. Liší se **tím, co tě k nim přivedlo**:

| | Replace Conditional with Polymorphism | Replace Type Code with Subclasses |
| --- | --- | --- |
| Co tě štve | **tentýž `switch` na pěti místech** | **ta hodnota sama** — druh věci jako řetězec |
| Kolik je podmínek | hodně, a přibývají | klidně jedna, nebo žádná |
| První otázka | kde všude se ten `switch` opakuje | mění se ten druh za života objektu? |

**Mechanika podtříd a továrny je popsaná u toho druhého** a nebudeme ji opisovat. Tenhle dokument je o rozhodnutí, které ta mechanika předpokládá.

---

## Kdy po tom sáhnout

**Poznáš to podle:**

- pole s hodnotami `'standard'` / `'premium'`, na které se někde ptáš
- konstanty `TYPE_A`, `TYPE_B` v třídě, která podle nich mění chování
- z databáze čteš sloupec `type` a hned za ním následuje podmínka
- do `switch`e by šlo přidat šestou variantu, ale nikdo neví, kde všude
- ten druh **má v doméně jméno**, ale v kódu je to text

Poslední bod je ten rozhodující. **Když má věc jméno a chování, má mít i typ.**

---

## Předtím

```php
final class Customer
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        private string $tier,
    ) {
    }

    public function discountPercent(): int
    {
        return $this->tier === 'premium' ? 10 : 0;
    }

    public function freeShippingFromInCents(): int
    {
        return $this->tier === 'premium' ? 0 : 100000;
    }

    public function upgradeToPremium(): void
    {
        $this->tier = 'premium';
    }
}
```

Velký `switch` tu nikde není. **Smell je ta hodnota sama** — nese chování, ale je to jen text. A ta poslední metoda je zároveň celý problém.

---

## Rozhodnutí: podtřídy, nebo objekt uvnitř?

Obě varianty dají totéž rozhraní a v klidovém stavu se chovají shodně. Rozhoduje jediná otázka:

### Mění se ten druh za života objektu?

**Když ne**, jdi do podtříd. `StandardCustomer` a `PremiumCustomer`, továrna v místě, kde se zákazník načítá — mechanika je [tady](../ReplaceConditionalWithPolymorphism/#mechanika).

**Když ano**, podtřídy nepoužívej. Důvod je prostý a nedá se obejít: **PHP neumí objektu změnit třídu.** Povýšení proto znamená postavit nový objekt a přenést do něj stav:

```php
public static function toPremium(Customer $customer): PremiumCustomer
{
    $upgraded = new PremiumCustomer($customer->id, $customer->name);
    $upgraded->addPurchase($customer->lifetimeValueInCents());

    return $upgraded;
}
```

A tím se rozbijí dvě věci naráz.

---

## Co se tím rozbije

### 1. Identita

Demo si zákazníka uloží do košíku — úplně běžná situace — a pak ho povýší:

```
                                  podtřídy        delegace
jak se povýší                     nový objekt     výměna pole
je to týž objekt?                 NE              ano
sleva po povýšení                 10 %            10 %
sleva u reference v košíku        0 %             10 %
```

**Zákaznice je prémiová a v košíku má pořád slevu 0 %.** Košík si drží starý objekt, protože povýšení vyrobilo nový. Nic nespadlo, nic se nenahlásilo.

### 2. Stav se stěhuje ručně

```
polí, která nese zákazník               3
přenesených ručně v Upgrade             3
lifetimeValue po povýšení               250000 — přeneseno správně
```

Tady to vyšlo, protože pole jsou tři. **U entity s dvaceti poli je ten kopírovací konstruktor místo, kam se chodí zapomínat** — a nic ti neřekne, že jsi na jedno zapomněl.

---

## Druhá podoba: druh jako objekt uvnitř

```php
interface CustomerTier
{
    public function discountPercent(): int;

    public function freeShippingFromInCents(): int;
}
```

```php
final class Customer
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        private CustomerTier $tier,
    ) {
    }

    public function discountPercent(): int
    {
        return $this->tier->discountPercent();
    }

    public function upgradeTo(CustomerTier $tier): void
    {
        $this->tier = $tier;
    }
}
```

Povýšení je **výměna jednoho pole**. Objekt zůstává týž, takže všechny reference na něj vidí novou skutečnost — a žádný stav se nikam nestěhuje.

Tomuhle se v prvním vydání říkalo *Replace Type Code with State/Strategy*, protože přesně to z toho vznikne.

---

## Mechanika

Předpokládá se, že už víš, kterou variantu chceš. Pro podtřídy jdi na [mechaniku u sousedního refaktoringu](../ReplaceConditionalWithPolymorphism/#mechanika); pro variantu s objektem uvnitř:

### 1. Vytvoř rozhraní pro druh a jednu implementaci pro současnou hodnotu

```php
interface CustomerTier { /* … */ }

final class StandardTier implements CustomerTier { /* … */ }
```

**Po tomhle kroku platí:** nic to nepoužívá, testy procházejí. Samostatně commitnutelné.

### 2. Nahraď pole s řetězcem polem s objektem, chování zatím nech

```php
public function __construct(/* … */ private CustomerTier $tier) { }

public function discountPercent(): int
{
    return $this->tier->discountPercent();
}
```

**Po tomhle kroku platí:** podmínky na `'premium'` jsou pryč z `Customer`. Volající se museli změnit — sestavují teď `new StandardTier()` místo řetězce.

### 3. Doplň zbylé varianty

```php
final class PremiumTier implements CustomerTier { /* … */ }
```

**Po tomhle kroku platí:** každá varianta má vlastní třídu a přidání šesté se nikoho jiného nedotkne — to je [OCP](../../../SoftwareDesign/Principles/SOLID.md#openclosed-principle-ocp).

### 4. Nahraď změnu druhu výměnou objektu

```php
public function upgradeTo(CustomerTier $tier): void
{
    $this->tier = $tier;
}
```

**Po tomhle kroku platí:** povýšení nemění identitu a nestěhuje stav.

> [!NOTE]
> **Kde se dá zastavit:** po kroku 2 už je chování mimo `Customer` a to je většina zisku. Kroky 3 a 4 jsou dokončení.

---

## Jak ověřit, že to funguje

Kroky 1–4 chování nemění, takže stačí stávající testy. **Přidej ale jeden, který tam skoro jistě chybí:** dvě reference na téhož zákazníka, změna druhu, a kontrola, že ji vidí obě. Přesně to demo dělá — a s podtřídami ten test neprojde.

Když je varianta s podtřídami už nasazená a zjistíš, že se druh mění, hledej **místa, která si objekt drží déle než jeden request**: košík v session, kolekce v paměti, cache.

---

## Co to stojí

| Cena | Kdy se vyplatí |
| ---- | -------------- |
| Rozhraní a třída pro každou variantu | Jakmile se druh liší chováním, ne jen popiskem |
| Volající musí druh sestavit (`new PremiumTier()`) | Skoro vždy — sestavení patří na jedno místo, do továrny |
| Perzistence: do databáze pořád ukládáš řetězec | Vždycky; převod tam a zpět patří do mapperu |

Ten poslední řádek je častý zdroj zklamání. **Type code v databázi nezmizí** — jen se přestane používat jako podmínka v doméně.

---

## Kdy to nedělat

- ❌ **Varianty se liší jen hodnotou, ne chováním** (`'CZK'`, `'EUR'` jako popisek) — pak stačí **enum**, což je v moderním PHP obvykle to správné řešení.
- ❌ **Je to jedna podmínka na jednom místě a další nebude** — [pravidlo tří](../../../SoftwareDesign/Principles/Simplicity.md#pravidlo-tří).
- ❌ **Druh přichází z konfigurace a mění se za běhu bez omezení** — pak nechceš typ, ale data.
- ❌ **Přechody mezi druhy mají pravidla** (nová → potvrzená → odeslaná, a zpátky ne) — to už není jen druh, to je [State](../../../SoftwareDesign/GoF/Behavioral/State/), který navíc hlídá dovolené přechody.

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| Podtřídy u druhu, který se mění | Povýšení vyrobí nový objekt; staré reference lžou | Objekt uvnitř, výměna pole |
| Enum se použije i tam, kde se chování liší hodně | `match` uvnitř enumu je týž `switch`, jen jinde | Enum na hodnoty, třídy na chování |
| Type code zůstane v doméně „pro jistotu" | Dvě pravdy o tomtéž a jedna z nich zastará | Řetězec žije jen v mapperu |
| Sestavení druhu se rozeteče po projektu | `new PremiumTier()` na dvaceti místech | [Factory](../../../SoftwareDesign/DDD/Factory/) na jednom |
| Do rozhraní se dá i to, co k druhu nepatří | Z `CustomerTier` se stane druhý `Customer` | Jen to, co se mezi druhy skutečně liší |
| Zapomene se na test dvou referencí | Právě ta chyba, kterou refaktoring řeší, projde | Test z demo části výš |

---

## Kam to vede

Podle toho, co ten druh znamená, končíš u jednoho ze dvou vzorů — a rozdíl je popsaný [u sousedního refaktoringu](../ReplaceConditionalWithPolymorphism/#kam-to-vede):

| Když druh znamená | Máš |
| ----------------- | --- |
| **způsob, jak něco počítat** (úroveň zákazníka, tarif) | [Strategy](../../../SoftwareDesign/GoF/Behavioral/Strategy/) |
| **stav, ve kterém objekt je** a přechody mají pravidla | [State](../../../SoftwareDesign/GoF/Behavioral/State/) |

V demu je to Strategy: úroveň zákazníka je způsob, jak se počítá sleva, ne stav s dovolenými přechody. Kdyby povýšení šlo jen jedním směrem a bylo potřeba to hlídat, je to State.

**Než tam půjdeš, přečti si u obou sekci „kdy nepoužít".** Když se varianty liší jen hodnotou, cíl je jinde — a je jím **enum s metodou**.

---

## Demo

```bash
php Refactoring/Code/ReplaceTypeCodeWithSubclasses/demo/run.php
```

Zákazník s úrovní jako řetězcem, nahrazený dvakrát: podtřídami a objektem uvnitř. Demo nejdřív ukáže, že se **v klidovém stavu obě varianty chovají shodně**, a pak zákazníka povýší — přičemž si ho předtím uloží do košíku, jako to dělá skutečná aplikace.

S podtřídami má košík po povýšení **slevu 0 %**, protože drží starý objekt. S delegací 10 %.

Nakonec demo spočítá, kolik polí se při povýšení stěhuje ručně, a shrne rozhodnutí do tabulky — tři z pěti řádků říkají „podtřídy ne".

---

## Související

| Dokument | Vztah |
| -------- | ----- |
| [Replace Conditional with Polymorphism](../ReplaceConditionalWithPolymorphism/) | **Mechanika podtříd a továrny.** Sourozenec se stejným cílem a jiným spouštěčem. |
| [Strategy](../../../SoftwareDesign/GoF/Behavioral/Strategy/) (GoF) | Kam to vede, když druh znamená způsob výpočtu. |
| [State](../../../SoftwareDesign/GoF/Behavioral/State/) (GoF) | Kam to vede, když druh znamená stav — a přechody mají pravidla. |
| [Replace Superclass with Delegate](../ReplaceSuperclassWithDelegate/) | Tentýž pohyb od dědičnosti k delegaci, jen z opačné strany. |
| [Replace Primitive with Object](../ReplacePrimitiveWithObject/) | Když ta hodnota nenese chování, ale jen typovou nejistotu. |
| [Factory](../../../SoftwareDesign/DDD/Factory/) (DDD) | Kam patří sestavení druhu, ať zvolíš cokoli. |
| [OCP](../../../SoftwareDesign/Principles/SOLID.md#openclosed-principle-ocp) | Čeho se tím dosáhne: nová varianta = nová třída. |

---

## Původ

|             |                    |
| ----------- | ------------------ |
| **Autor**   | Martin Fowler      |
| **Rok**     | 1999               |
| **Zdroj**   | *Refactoring*, katalog |
| **Náročnost** | ●●○○○            |

V prvním vydání *Refactoringu* (1999) to byly **tři samostatné techniky** podle toho, co s type codem šlo udělat: *Replace Type Code with Class*, *Replace Type Code with Subclasses* a *Replace Type Code with State/Strategy*. Ta poslední byla pro případ, kdy se type code **mění za života objektu** nebo kdy třída už dědí z něčeho jiného.

Ve druhém vydání zůstala **jedna** — *Replace Type Code with Subclasses* — a ostatní jsou vedené jako aliasy. Fowlerův příklad je zaměstnanec:

```
function createEmployee(name, type) {
  return new Employee(name, type);
}
```

```
function createEmployee(name, type) {
  switch (type) {
    case "engineer": return new Engineer(name);
    case "salesman": return new Salesman(name);
    case "manager":  return new Manager(name);
  }
}
```

Dvojka na náročnosti není za mechaniku, ale za to rozhodnutí na začátku. **Zvolit podtřídy u druhu, který se mění, je chyba, která se pozná až na produkci** — a to na místě, kde si někdo objekt držel déle, než čekal.

---

## Zdroje

- Martin Fowler: [*Replace Type Code with Subclasses*](https://refactoring.com/catalog/replaceTypeCodeWithSubclasses.html), katalog refaktoringů
- Martin Fowler: [*Refactoring: Improving the Design of Existing Code*](https://martinfowler.com/books/refactoring.html), 1. vydání 1999, 2. vydání 2018

---

<details>
<summary>Metadata techniky</summary>

```yaml
name: Replace Type Code with Subclasses
aliases: [Replace Type Code with State/Strategy, Extract Subclass]
level: code
author: Martin Fowler
year: 1999
duration: hodiny
reversible: ano — refaktoring nic nemění
requires_tests: ano
difficulty: 2
tags: [type code, podtřídy, delegace, identita, State, Strategy]
leads_to: [Strategy, State]
related: [ReplaceConditionalWithPolymorphism, ReplaceSuperclassWithDelegate, Factory]
status: done
```

</details>
