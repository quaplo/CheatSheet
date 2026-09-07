# Extract Class

> [← zpět na Refaktoring kódu](../)

> **V jedné větě:** Když se ve třídě usadí skupina polí a metod, která žije vlastním životem, přestěhuj ji do vlastní třídy.

---

## Kdy po tom sáhnout

Klasická odpověď zní „když třída dělá moc věcí" — jenže to je pocit, ne kritérium. **Na tuhle otázku existuje měřítko** a dá se spočítat z kódu.

**Poznáš to podle:**

- třída se nedá popsat **jednou větou** bez „a taky"
- část polí používá jedna skupina metod, **jinou část jiná** — a nepřekrývají se
- při čtení se dá říct „odsud až sem je to o něčem jiném"
- konstruktor bere parametry, které spolu nesouvisejí
- část třídy by se hodila **i jinde**, ale nejde vytáhnout

```php
final class Order
{
    private array $items = [];
    private string $status = 'nová';

    // …a od téhle chvíle je to o něčem jiném:
    private string $street = '';
    private string $city = '';
    private string $postalCode = '';
    private string $countryCode = 'CZ';
}
```

---

## Měřítko: LCOM4

Metrika **LCOM4** (*lack of cohesion of methods*) staví graf, kde uzly jsou metody a hrana vede mezi dvěma, které **sahají na totéž pole** nebo jedna volá druhou. Výsledkem je počet **nezávislých komponent**.

Demo ji počítá na výchozí třídě:

```
metoda                  pole, na která sahá
addItem()               items
totalInCents()          items
itemCount()             items
confirm()               status
status()                status
setAddress()            street, city, postalCode, countryCode
formattedAddress()      street, postalCode, city, countryCode
isDomestic()            countryCode
postalCodeDigits()      postalCode
```

```
Before\Order              3   3 nezávislé skupiny — kandidáti k prozkoumání

komponenty, které metrika našla:
    1. addItem(), itemCount(), totalInCents()
    2. confirm(), status()
    3. formattedAddress(), isDomestic(), postalCodeDigits(), setAddress()
```

**Tohle není odhad — je to spočítané z toho, kdo sahá na co.** A metrika neříká jen *kolik*, ale i *kudy*: každá komponenta je hotový seznam metod a polí, které by šly ven společně.

### Metrika dává otázku, ne odpověď

Tady je ale místo, kde se LCOM snadno použije špatně. **Ne každá komponenta je kandidát na vytažení:**

```
komponenta            pojem                 existuje samostatně?
addItem, itemCount,   položky objednávky    ne — bez objednávky nedávají smysl
confirm, status       stav objednávky       ne — je to vlastnost objednávky
setAddress, formatte  doručovací adresa     ANO — adresu má i dodavatel a pobočka
```

Metrika našla tři komponenty; vytáhnout se má **jedna**. Položky i stav jsou legitimní součástí objednávky — **že spolu technicky nesouvisejí, ještě neznamená, že mají bydlet jinde.**

> [!IMPORTANT]
> **Rozhodovací otázka není „je LCOM vysoké?"** ale: *dá se ta skupina pojmenovat a existuje i bez téhle třídy?* Adresa ano — má ji i dodavatel a pobočka. Stav objednávky ne. **LCOM4 = 1 není cíl**; je to ukazatel, který upozorní, kam se podívat.

---

## Mechanika

### 0. Testy

Na chování, které se má zachovat.

**Po tomhle kroku platí:** změna chování by se poznala.

### 1. Rozhodni, co je nový pojem

Vezmi komponentu z metriky a **pojmenuj ji**. Když to nejde jedním podstatným jménem, není to samostatný pojem a refaktoring nemá cíl.

**Po tomhle kroku platí:** víš, jak se nová třída bude jmenovat — a to je půlka práce.

### 2. Vytvoř prázdnou novou třídu

```php
final readonly class DeliveryAddress
{
}
```

**Po tomhle kroku platí:** nic se nezměnilo.

### 3. Přesuň pole

Jedno po druhém. Stará třída si zatím drží instanci nové a deleguje:

```php
final class Order
{
    private ?DeliveryAddress $address = null;

    public function formattedAddress(): string
    {
        return $this->address->format();   // ← zatím jen předává dál
    }
}
```

**Po tomhle kroku platí:** volající se nezměnili; data jsou už jinde.

### 4. Přesuň metody

Také po jedné. Po každé spusť testy.

**Po tomhle kroku platí:** nová třída umí to, co má.

### 5. Přepni volající a smaž delegující metody

Volající místo `$order->formattedAddress()` použije `$order->address()->format()`.

```
třída                         LCOM4     verdikt
Before\Order                  3         3 nezávislé skupiny
After\Order                   3         3 nezávislé skupiny
After\DeliveryAddress         1         drží pohromadě
```

**Po tomhle kroku platí:** hotovo. A všimni si, že `Order` má LCOM4 pořád 3 — a je to v pořádku.

> [!NOTE]
> **Kde se dá zastavit:** po kroku 3 nebo 4, kdy stará třída deleguje. Chování je zachované, kód je o kus lepší a volající se nemuseli měnit. Krok 5 se dá dodělávat postupně — a u [publikovaného rozhraní](../../../SoftwareDesign/DDD/BoundedContext/) se dělá přes [Expand–Contract](../../System/ExpandContract/).

---

## Jak ověřit, že to funguje

- **Testy projdou beze změny** až do kroku 5; ten mění podpisy, takže se upraví i testy.
- **Spusť LCOM znovu** na obou třídách. Nová má mít 1; stará se sníží nebo zůstane — podstatné je, že v ní zbyly jen skupiny, které tam pojmově patří.
- **Zkus novou třídu použít jinde.** Když to nejde, nebyla to samostatná věc a vytažení bylo předčasné.

Poslední bod je nejpřísnější test celého refaktoringu.

---

## Co to stojí

| Cena | Kdy se vyplatí |
| ---- | -------------- |
| **Třída navíc** a jedna úroveň nepřímosti | Když je nový pojem opravdu pojem |
| **Volající se musí přepsat** v kroku 5 | Když se ta část dá použít i jinde |
| **Riziko špatného řezu** — rozdělí se, co patřilo k sobě | Když se řez opírá o pojem, ne jen o metriku |
| **Delegující metody** dočasně zdvojují API | Krátkodobě; jsou to lešení, ne výsledek |

Třetí řádek je ten drahý. **Špatně vedený řez se opravuje hůř než původní velká třída** — proto se rozhoduje podle pojmu, a metrika jen ukazuje, kde se dívat.

---

## Kdy to nedělat

- ❌ **Skupina se nedá pojmenovat.** `OrderHelper` nebo `OrderData` znamená, že pojem není.
- ❌ **Nová třída by neexistovala bez té staré.** Pak je to jen přesunutá část, ne samostatná věc.
- ❌ **Třída je malá.** Šest metod a tři pole nepotřebují dělit, i kdyby LCOM říkalo dvě.
- ❌ **Řídíš se jen metrikou.** LCOM najde i skupiny, které patří k sobě pojmově — [viz výš](#metrika-dává-otázku-ne-odpověď).
- ❌ **Je to [generická podoblast](../../../SoftwareDesign/DDD/GenericSubdomains/).** Tam se staví jednoduše a neinvestuje.

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| Nová třída se jmenuje `…Helper`, `…Manager`, `…Data` | Není to pojem, jen odkladiště | Když nejde pojmenovat, nedělit |
| Řeže se podle metriky bez rozmyslu | Rozdělí se, co pojmově patří k sobě | Metrika dává otázku, pojem odpověď |
| Pole se přesunou, metody zůstanou | Vznikla anemická třída a `getX()` všude | Přesunout obojí |
| Nová třída dostane zpětný odkaz na starou | Kruhová závislost; nic se nezískalo | Závislost jedním směrem |
| Delegující metody zůstanou navždy | API má dvě cesty ke stejné věci | Krok 5 dokončit |
| Přesune se všechno naráz | Nedá se poznat, kde se to rozbilo | Pole po poli, metodu po metodě |
| Vytažená třída je měnitelná, i když nemá identitu | Změní se za zády držitele | Zvážit [value object](../../../SoftwareDesign/DDD/ValueObject/) |

Poslední řádek se v demu projeví: `DeliveryAddress` je `readonly`, protože adresa je **hodnota** — nemá identitu a dvě stejné adresy jsou tatáž adresa.

---

## Kam to vede

Extract Class nevede k jednomu vzoru, ale ke **splnění principu** — [**SRP**](../../../SoftwareDesign/Principles/SOLID.md#single-responsibility-principle-srp). Třída má mít jeden důvod ke změně; adresa se mění kvůli doručování, objednávka kvůli obchodu.

Podle toho, co je ta vytažená věc, končíš u konkrétnějšího vzoru:

| Když je vytažená část | Cíl |
| --------------------- | --- |
| hodnota bez identity (adresa, peníze, rozmezí) | [Value Object](../../../SoftwareDesign/DDD/ValueObject/) |
| skupina s vlastními pravidly | [First Class Collection](../../../SoftwareDesign/ObjectCalisthenics/FirstClassCollection/) |
| složitý výpočet | [Cohesive Mechanism](../../../SoftwareDesign/DDD/CohesiveMechanism/) |
| rozhodnutí ano/ne | [Specification](../../../SoftwareDesign/DDD/Specification/) |

Na úrovni celého modelu je totéž [**Segregated Core**](../../../SoftwareDesign/DDD/SegregatedCore/) — ten dělí balíčky podle důležitosti, tenhle refaktoring třídy podle soudržnosti.

---

## Demo

```bash
php Refactoring/Code/ExtractClass/demo/run.php
```

Objednávka, která vedle položek a stavu drží i doručovací adresu. Demo **spočítá LCOM4 přímo ze zdrojáku** — rozebere tokeny, zjistí, která metoda sahá na která pole, a najde nezávislé komponenty.

Nejzajímavější je, že jich najde **tři, ale vytáhnout se má jedna**. Položky a stav k objednávce pojmově patří; adresa ne. Demo proto u každé komponenty ptá, jestli se dá pojmenovat a existuje i samostatně — a ukazuje, že po refaktoringu má `Order` LCOM4 pořád 3, **a je to v pořádku**.

---

## Související

| Dokument | Vztah |
| -------- | ----- |
| [SRP](../../../SoftwareDesign/Principles/SOLID.md#single-responsibility-principle-srp) | Princip, který se refaktoringem naplní. |
| [Soudržnost a provázanost](../../../SoftwareDesign/Principles/CohesionAndCoupling.md) | LCOM je pokus změřit soudržnost číslem; dokument vysvětluje, co se tím vlastně měří. |
| [Value Object](../../../SoftwareDesign/DDD/ValueObject/) (DDD) | Nejčastější cíl, když je vytažená část hodnota bez identity. |
| [Segregated Core](../../../SoftwareDesign/DDD/SegregatedCore/) (DDD) | Totéž o úroveň výš — dělení balíčků místo tříd. |
| [Replace Primitive with Object](../ReplacePrimitiveWithObject/) | Často navazuje: vytažená adresa dostane `PostalCode` místo `string`. |
| [Encapsulate Collection](../EncapsulateCollection/) | Když je vytažená část skupina, ne hodnota. |
| [Expand–Contract](../../System/ExpandContract/) | Jak krok 5 udělat u rozhraní, které používá někdo mimo tvůj kód. |

---

## Původ

|             |                    |
| ----------- | ------------------ |
| **Autor**   | Martin Fowler      |
| **Rok**     | 1999, přepracováno 2018 |
| **Zdroj**   | *Refactoring*, katalog |
| **Náročnost** | ●●●○○            |

V katalogu od prvního vydání; Fowlerův příklad vytahuje z třídy `Person` telefonní číslo do `TelephoneNumber`. Opačný refaktoring se jmenuje **Inline Class** a používá se, když se ukáže, že vytažená třída nedělá dost na to, aby existovala.

Metrika **LCOM4** s Fowlerovým katalogem přímo nesouvisí — pochází z práce Hitze a Montazeriho a je novější variantou původní LCOM od Chidambera a Kemerera. Do dokumentu jsem ji přidal proto, že odpovídá na otázku, kterou katalog nechává na citu: **kde přesně ta třída dělá dvě věci.**

Náročnost je trojka, o stupeň vyšší než u ostatních refaktoringů v [téhle složce](../). Mechanika je stejně jednoduchá, ale **rozhodnutí, kudy řezat, je nevratnější**:

- **Špatný řez se opravuje hůř než původní stav.** Rozdělit, co patřilo k sobě, znamená dvě třídy, které si navzájem lezou do vnitřku.
- **Metrika svádí k mechanickému použití.** LCOM4 = 3 neznamená tři třídy; znamená tři místa, kam se podívat.
- **Krok 5 mění podpisy** a u veřejného rozhraní se to bez [Expand–Contract](../../System/ExpandContract/) neobejde.

---

## Zdroje

- Martin Fowler: [*Extract Class*](https://refactoring.com/catalog/extractClass.html) — katalog online
- Martin Fowler: *Refactoring*, 2. vydání, Addison-Wesley, 2018
- [Cohesion metrics](https://www.aivosto.com/project/help/pm-oo-cohesion.html) — přehled variant LCOM včetně LCOM4

---

<details>
<summary>Metadata techniky</summary>

```yaml
name: Extract Class
level: code
author: Martin Fowler
year: 1999
duration: hodiny
reversible: ano — opakem je Inline Class
requires_tests: ano
difficulty: 3
tags: [soudržnost, LCOM, SRP, rozdělení třídy]
leads_to: [ValueObject, FirstClassCollection, SegregatedCore]
related: [ValueObject, SegregatedCore, ReplacePrimitiveWithObject, EncapsulateCollection, ExpandContract]
status: done
```

</details>
