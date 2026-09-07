# Replace Conditional with Polymorphism

> [← zpět na Refaktoring kódu](../)

> **V jedné větě:** Když se stejný `switch` na typ objevuje na víc místech, udělej z každé větve třídu — a rozhodnutí přesuň na jediné místo.

---

## Kdy po tom sáhnout

Není to o jednom `if`. Je to o **tomtéž rozhodnutí, které se opakuje**.

**Poznáš to podle:**

- tentýž `switch` nebo `match` na **stejnou hodnotu** ve víc metodách
- přidání nové varianty znamená **projít celý projekt a najít všechna místa**
- na jedno místo se **zapomnělo** a chyba se ukázala až u zákazníka
- ve větvích jsou `default: throw new InvalidArgumentException('Neznámý…')`
- kód se větví podle **řetězce nebo konstanty**, která popisuje **druh věci**

```php
final class ShippingService
{
    public function priceInCents(string $carrier, Shipment $s): int
    {
        return match ($carrier) { 'ppl' => …, 'dhl' => …, 'pickup' => … };
    }

    public function deliveryDays(string $carrier): int
    {
        return match ($carrier) { 'ppl' => 2, 'dhl' => 4, 'pickup' => 1 };
    }

    public function requiresAddress(string $carrier): bool { /* …a znovu… */ }
    public function label(string $carrier): string          { /* …a znovu… */ }
}
```

```
Before — větvení na typ       4
```

**Čtyřikrát totéž rozhodnutí.** Přidat dopravce znamená najít všechna čtyři místa.

> [!NOTE]
> **Jeden `match` na jednom místě je v pořádku** a tenhle refaktoring by ho jen zkomplikoval. Signál není větvení samo, ale jeho **opakování**. [Pravidlo tří](../../../SoftwareDesign/Principles/Simplicity.md#pravidlo-tří) tu platí doslova.

---

## Předtím

Kromě opakování má výchozí stav ještě dvě vlastnosti, které se špatně vidí:

- **Chování jednoho dopravce je rozeseté** po čtyřech metodách. Kdo chce vědět, jak funguje PPL, musí je projít všechny.
- **Nic nehlídá úplnost.** Nový `case` přidaný do tří ze čtyř `match`ů projde překladem i testy — pokud na tu čtvrtou větev zrovna žádný test není.

---

## Mechanika

Postup níž je náš, v PHP; Fowlerův katalog uvádí jen výchozí a cílový stav. Klíčové je pořadí — **každý krok se dá vydat samostatně.**

### 0. Testy

Musí existovat testy, které pokrývají **všechny větve**. Bez nich není jak poznat, že se chování nezměnilo.

**Po tomhle kroku platí:** kdyby se některá větev změnila, poznáš to.

### 1. Vytvoř nadtřídu nebo rozhraní

Metody odvoď z toho, co dnes dělají jednotlivé `match`e — jedna metoda na jeden `match`:

```php
abstract class ShippingMethod
{
    abstract public function code(): string;
    abstract public function priceInCents(Shipment $shipment): int;
    abstract public function deliveryDays(): int;
    abstract public function requiresAddress(): bool;
    abstract public function label(): string;
}
```

**Metody dělej abstraktní, ne s výchozí implementací.** Právě to zajistí, že se na žádnou nedá zapomenout.

**Po tomhle kroku platí:** nic se nezměnilo, jen přibyla nepoužitá třída.

### 2. Vytvoř třídu pro první variantu

Vezmi **jednu** hodnotu — třeba `'ppl'` — a přenes do nové třídy tu větev z každého `match`e:

```php
final class Ppl extends ShippingMethod
{
    public function priceInCents(Shipment $shipment): int
    {
        return $shipment->orderValueInCents >= 250000 ? 0 : 9900;
    }

    public function deliveryDays(): int
    {
        return 2;
    }
    // …
}
```

**Po tomhle kroku platí:** chování PPL je na jednom místě. Původní kód pořád běží beze změny.

### 3. Opakuj pro ostatní varianty

Jedna třída za druhou, každá vlastní commit.

**Po tomhle kroku platí:** existují všechny třídy a duplikují to, co dělá původní `match`.

### 4. Postav továrnu

Někde na hranici se z řetězce musí stát objekt. Tomu se nevyhneš — ale má to být **jedno místo**:

```php
final class ShippingMethods
{
    public function byCode(string $code): ShippingMethod
    {
        return $this->methods[$code]
            ?? throw new InvalidArgumentException('Neznámý dopravce: ' . $code);
    }
}
```

**Po tomhle kroku platí:** z kódu dopravce se dá získat objekt.

### 5. Přepni volající a smaž původní metody

Volající místo `$service->priceInCents($carrier, $shipment)` použije `$method->priceInCents($shipment)`. Až je přepnutý poslední, původní třída se smaže.

```
Before — větvení na typ       4
After — větvení na typ        0
```

**Po tomhle kroku platí:** hotovo, rozhodnutí podle typu je na jediném místě — v továrně.

> [!NOTE]
> **Kde se dá zastavit:** po kroku 3. Tam existují nové třídy vedle starého kódu a nic není rozbité — jen dočasně duplicitní. Zastavit se dřív (po kroku 1 nebo 2) taky nic nerozbije, ale nepřinese to nic.

---

## Jak ověřit, že to funguje

**Testy musí projít beze změny.** Když se musí upravit, změnilo se chování — a to není tenhle refaktoring.

Demo to kontroluje přímo:

```
dopravce      Before          After           shoda
ppl           99,00 Kč        99,00 Kč        ano
dhl           329,00 Kč       329,00 Kč       ano
pickup        0,00 Kč         0,00 Kč         ano
```

U větší změny se hodí nechat obě verze chvíli vedle sebe a porovnávat je na skutečném provozu — to je [Parallel Run](../../System/ParallelRun/).

---

## Co to stojí

| Cena | Kdy se vyplatí |
| ---- | -------------- |
| **Víc tříd** — jedna na variantu místo jedné služby | Když se varianty chovají opravdu různě |
| **Továrna navíc** na hranici | Když je jedna proti čtyřem `match`ům |
| **Hůř se čte celkový přehled** — chování jednoho typu je pohromadě, ale porovnat dva už vyžaduje dva soubory | Když se častěji přidává varianta, než porovnává |

Prostřední řádek je poctivá nevýhoda, o které se moc nemluví. **`match` má tu výhodu, že všechny varianty vidíš vedle sebe.** Polymorfismus je rozdělí — a když je jich pět a liší se v jednom čísle, byl ten `match` čitelnější.

---

## Kdy to nedělat

- ❌ **Rozhodnutí je na jednom místě.** Jeden `match` je jednodušší než pět tříd a továrna.
- ❌ **Varianty se liší jedním číslem.** Tabulka nebo [enum s metodou](../../../SoftwareDesign/GoF/Behavioral/State/) stačí.
- ❌ **Větve nejsou stabilní.** Když se sada variant mění každý měsíc, továrna se bude přepisovat pořád.
- ❌ **Nevětví se podle typu, ale podle stavu jednoho objektu.** To je [State](../../../SoftwareDesign/GoF/Behavioral/State/) a má vlastní mechaniku.
- ❌ **Nejsou testy na všechny větve.** Pak nejde ověřit, že se chování nezměnilo — a refaktoring se stává přepisem.

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| Metody v nadtřídě mají výchozí implementaci | Nová varianta jde napsat neúplná a chyba se ukáže za běhu | Abstraktní metody |
| Refaktoring a změna chování v jednom commitu | V šumu přesunů se ztratí ta jedna změněná řádka | Dva commity |
| Zůstane `instanceof` u volajícího | Větvení podle typu se jen přestěhovalo | Volat metodu, ne se ptát na typ |
| Továrna je na pěti místech | Rozhodnutí podle řetězce se zase rozeselo | Jedna továrna |
| Vytvoří se třída pro variantu, která se liší jen číslem | Pět tříd s jedním rozdílem je horší než `match` | Enum s metodou nebo tabulka |
| Nadtřída dostane metody „pro budoucnost" | Varianty je musí implementovat, i když je nepotřebují — porušení [ISP](../../../SoftwareDesign/Principles/SOLID.md#interface-segregation-principle-isp) | Jen to, co všechny potřebují |
| Refaktoring se udělá bez testů | Není jak poznat, že se chování nezměnilo | Testy jsou krok nula |

---

## Kam to vede

Po dokončení máš jednu ze dvou struktur — a rozdíl je v tom, **co ten typ znamená**:

| Když varianta znamená | Máš | Dokument |
| --------------------- | --- | -------- |
| **způsob, jak něco udělat** (dopravce, výpočet, formát) | **Strategy** | [Strategy](../../../SoftwareDesign/GoF/Behavioral/Strategy/) |
| **stav, ve kterém objekt je** (nová → potvrzená → odeslaná) | **State** | [State](../../../SoftwareDesign/GoF/Behavioral/State/) |

V demu je to Strategy: dopravce je způsob doručení, ne stav zásilky. Kdyby se větvilo podle `$order->status`, výsledkem by byl State — a ten navíc řeší, **které přechody jsou dovolené**.

Když se varianty liší jen hodnotou a ne chováním, cíl je jinde: **enum s metodou**, což je v moderním PHP často to správné řešení a je popsané u [State](../../../SoftwareDesign/GoF/Behavioral/State/).

---

## Demo

```bash
php Refactoring/Code/ReplaceConditionalWithPolymorphism/demo/run.php
```

Čtyři `match`e na typ dopravce převedené na čtyři třídy. Demo **spočítá větvení podle typu** (4 před, 0 po), ověří, že se ceny nezměnily, a vyčíslí, co stojí přidání dalšího dopravce: v původní verzi změna ve čtyřech metodách, po refaktoringu jeden nový soubor a **nula zásahů do existujícího kódu**.

Nejzajímavější je čtvrtá část: pustí v samostatném procesu třídu, které chybí dvě metody, a ukáže, že **ji PHP nedovolí ani vytvořit**. V původní verzi by taková chyba prošla a spadla by až ve chvíli, kdy někdo objedná zrovna tím dopravcem.

---

## Související

| Dokument | Vztah |
| -------- | ----- |
| [Strategy](../../../SoftwareDesign/GoF/Behavioral/Strategy/) (GoF) | **Nejčastější cíl.** Když varianta znamená způsob, jak něco udělat. |
| [State](../../../SoftwareDesign/GoF/Behavioral/State/) (GoF) | Druhý cíl — když varianta znamená stav objektu. Řeší navíc dovolené přechody. |
| [Pravidlo tří](../../../SoftwareDesign/Principles/Simplicity.md#pravidlo-tří) | Kdy je opakování ještě náhoda a kdy už vzor. |
| [OCP](../../../SoftwareDesign/Principles/SOLID.md#openclosed-principle-ocp) | Čeho se refaktoringem dosáhne: nová varianta = nová třída, existující se nemění. |
| [Factory](../../../SoftwareDesign/DDD/Factory/) (DDD) | Co vznikne v kroku 4 — jediné místo, kde zůstane rozhodování podle řetězce. |
| [Parallel Run](../../System/ParallelRun/) | Když je změna velká a chceš ověřit shodu na skutečném provozu. |
| [Code review](../../../Processes/CodeReview/) | Refaktoring a změna chování patří do **oddělených** pull requestů — jinak se v šumu ztratí to podstatné. |
| [Decompose Conditional](../DecomposeConditional/) | Levnější krok, který často stačí — a pokud ne, připraví půdu pro tenhle. |
| [Charakterizační testy](../../CharacterizationTests/) | **Krok nula.** Co dělat, když testy neexistují a zadání, podle kterého by se napsaly, taky ne. |

---

## Původ

|             |                    |
| ----------- | ------------------ |
| **Autor**   | Martin Fowler      |
| **Rok**     | 1999, přepracováno 2018 |
| **Zdroj**   | *Refactoring: Improving the Design of Existing Code* |
| **Náročnost** | ●●○○○            |

Refaktoring je v katalogu od prvního vydání *Refactoringu* (1999) a patří k nejcitovanějším — do velké míry proto, že symptom je snadné poznat a výsledek je vidět hned.

Náročnost je dvojka: mechanika je přímočará a **testy tu skutečně jistí správnost**, což u systémových technik v [sousední složce](../../System/) neplatí. Těžké je jinde — **rozpoznat, kdy se to vyplatí**. Fowlerův katalog uvádí, kdy refaktoring udělat; nezabývá se tolik tím, kdy ho neudělat, a právě to je v praxi častější otázka.

Praktické vodítko: **počítej místa, ne větve.** Jeden `match` o osmi větvích je v pořádku. Čtyři `match`e o třech větvích, které se rozhodují podle téhož, jsou přesně tenhle případ.

---

## Zdroje

- Martin Fowler: [*Replace Conditional with Polymorphism*](https://refactoring.com/catalog/replaceConditionalWithPolymorphism.html) — katalog online
- Martin Fowler: *Refactoring: Improving the Design of Existing Code*, 2. vydání, Addison-Wesley, 2018

---

<details>
<summary>Metadata techniky</summary>

```yaml
name: Replace Conditional with Polymorphism
level: code
author: Martin Fowler
year: 1999
duration: hodiny
reversible: ano
requires_tests: ano
difficulty: 2
tags: [polymorfismus, větvení, duplicita, OCP]
leads_to: [Strategy, State]
related: [Strategy, State, Factory, ParallelRun]
status: done
```

</details>
