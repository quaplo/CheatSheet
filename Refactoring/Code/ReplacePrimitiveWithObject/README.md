# Replace Primitive with Object

> [← zpět na Refaktoring kódu](../)

> **V jedné větě:** Když se u `string` nebo `int` začne opakovat validace, normalizace nebo porovnávání, udělej z něj typ — a všechno to dej dovnitř.

---

## Kdy po tom sáhnout

Rozpoznávací znak není „je to `string`". Je to **chování, které se k té hodnotě lepí a nemá kde bydlet**.

**Poznáš to podle:**

- **stejná hodnota se validuje na víc místech** — a pokaždé trochu jinak
- před porovnáním se volá `trim()`, `strtolower()` nebo obojí — a někde se zapomene
- v podpisech je `string $sku, string $email, string $countryCode` a **dá se je prohodit**
- z názvu parametru se pozná víc než z typu
- opakuje se `substr($sku, 0, 3)` nebo podobné odvozování
- `if ($value === '' || strlen($value) > 20)` na pěti místech

```php
final class Validation
{
    public static function inAdminForm(string $sku): bool { /* jedno pravidlo */ }
    public static function inImport(string $sku): bool    { /* jiné pravidlo */ }
    public static function inApi(string $sku): bool       { /* třetí pravidlo */ }
}
```

Demo pouští sedm vstupů přes všechna tři místa:

```
vstup           formulář    import      API         shoda
„MON-27"        ano         ano         ano         ano
„mon-27"        ano         ne          ano         NE
„MON-270"       ano         ne          ano         NE
„MONITOR-27"    ano         ne          ne          NE

vstupů:                7
neshod mezi místy:     6
```

**Šest ze sedmi vstupů dopadne jinak podle toho, kudy do systému přišel.** Nikdo to nezamýšlel; každé pravidlo psal jiný člověk v jiné době a všechna tři jsou „správná".

### Druhý příznak: chybějící normalizace

```
přidáno třikrát totéž zboží
různých položek v košíku      3

    „MON-27" → 1 ks
    „mon-27" → 1 ks
    „ MON-27 " → 1 ks
```

`===` nad řetězcem nezná normalizaci. **Zákazník má v košíku třikrát tentýž monitor** a myslí si, že jsou to tři různé věci.

---

## Mechanika

Postup níž je náš, v PHP. Tenhle refaktoring má jednu vlastnost, kterou ostatní v [téhle složce](../) nemají: **dotkne se hodně míst**, protože primitivní typ bývá všude. Proto se dělá postupně, od hranic dovnitř.

### 0. Testy

Na chování, které se má zachovat. Pozor: **testy se často opírají o tu nejvolnější validaci** a po refaktoringu spadnou. To není chyba refaktoringu — je to nález.

**Po tomhle kroku platí:** změna chování by se poznala.

### 1. Vytvoř typ s jedním pravidlem

```php
final readonly class Sku
{
    private const string PATTERN = '/^[A-Z]{3}-\d{2}$/';

    private function __construct(public string $value) {}

    public static function fromString(string $value): self
    {
        $normalized = strtoupper(trim($value));

        if (preg_match(self::PATTERN, $normalized) !== 1) {
            throw new InvalidArgumentException(/* … */);
        }

        return new self($normalized);
    }
}
```

Tady se rozhoduje ta nepříjemná otázka: **které ze tří pravidel je to správné?** Odpověď nedá kód — dá ji doména. A dokud se nerozhodne, refaktoring nemá smysl začínat.

**Normalizace patří dovnitř**, do `fromString()`. Právě ona odstraní druhý příznak.

**Po tomhle kroku platí:** existuje typ, nic ho nepoužívá.

### 2. Zaveď ho na hranicích

Nejdřív tam, kde hodnota **vstupuje do systému** — formulář, import, API:

```php
$sku = Sku::fromString($request->get('sku'));
```

**Po tomhle kroku platí:** neplatná hodnota se dovnitř nedostane. Uvnitř se pořád pracuje s řetězcem.

### 3. Postupuj dovnitř, podpis po podpisu

```php
// Bylo
public function add(string $sku, int $quantity): void

// Je
public function add(Sku $sku, int $quantity): void
```

Jedna metoda za druhou, každá vlastní commit. **Kde je to moc naráz, dá se dočasně mít obojí** — přetížení v PHP není, ale druhá metoda s jiným jménem ano.

**Po tomhle kroku platí:** čím dál větší část systému pracuje s typem.

### 4. Přesuň chování dovnitř

Odvozování, které se opakovalo venku, patří na typ:

```php
public function productGroup(): string
{
    return substr($this->value, 0, 3);
}
```

**Po tomhle kroku platí:** typ nese i znalost, nejen hodnotu.

### 5. Smaž staré validace

Až teď. Dřív ne — dokud existuje cesta, kudy se dovnitř dostane řetězec, jsou potřeba.

**Po tomhle kroku platí:** jedno pravidlo, jedno místo.

> [!NOTE]
> **Kde se dá zastavit:** po kroku 2. Zavedení typu na hranicích samo o sobě zabrání, aby do systému vstoupila neplatná hodnota — a to je většina užitku. Kroky 3–5 se dají dodělávat měsíce.

---

## Jak ověřit, že to funguje

```
vstup           platné?     výsledek
„MON-27"        ano         MON-27
„mon-27"        ano         MON-27
„  MON-27  "    ano         MON-27
„MON-270"       ne          —
```

- **Testy na hraniční vstupy** — prázdný řetězec, mezery, jiná velikost písmen.
- **Test na normalizaci**: dvě různě zapsané hodnoty musí být `equals()`.
- **Test, že neplatná hodnota nevznikne** — konstruktor musí vyhodit výjimku.
- **Kde testy spadnou**, podívej se proč. Obvykle se opíraly o volnější pravidlo — a to je nález, ne chyba.

---

## Co to stojí

| Cena | Kdy se vyplatí |
| ---- | -------------- |
| **Dotkne se hodně míst** — primitivum bývá všude | Když se validace opakuje nebo se liší |
| **Někdo musí rozhodnout, které pravidlo platí** | Když je nejednoznačnost skutečný problém |
| **Konverze na hranicích** — do databáze a z ní, do JSONu | Když je jistota uvnitř cennější než pár řádků na okraji |
| **Testy, které se opíraly o volnější pravidlo, spadnou** | Vždycky — je to nález |

Druhý řádek se odbývá a je to chyba. **Refaktoring odhalí, že tři místa mají tři pravidla — ale neřekne, které je správné.** To rozhodnutí patří doméně a bez něj se nedá začít.

---

## Kdy to nedělat

- ❌ **Hodnota nemá žádné pravidlo ani chování.** Poznámka od zákazníka je `string` a nic jiného z ní nebude.
- ❌ **Používá se na jednom místě.** Validace v jedné metodě je jednodušší než typ.
- ❌ **Je to průchozí data.** DTO, které jen přenáší JSON, typy nepotřebuje.
- ❌ **Nevíš, které pravidlo je správné, a nemáš se koho zeptat.** Pak refaktoring jen zabetonuje náhodně vybranou variantu.
- ❌ **Je to v [generické podoblasti](../../../SoftwareDesign/DDD/GenericSubdomains/).** Tam se staví jednoduše.

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| Typ jen obaluje řetězec a nic nehlídá | Přibyla třída, nezískalo se nic | Validace v konstruktoru |
| Veřejný konstruktor vedle továrny | Dá se obejít validace | `private function __construct` |
| Normalizace zůstane venku | Druhý příznak — `MON-27` ≠ `mon-27` — přetrvá | Do `fromString()` |
| Typ je měnitelný | Hodnota se změní za zády toho, kdo ji drží | `readonly` |
| Porovnává se `==` na objektech | Funguje náhodou; u složených typů ne | `equals()` |
| Staré validace se smažou hned | Ještě existují cesty, kudy vstoupí řetězec | Až v posledním kroku |
| Refaktoring naráz přes celý projekt | Stovky změn v jednom PR; nikdo to nezrecenzuje | Od hranic dovnitř, po částech |
| Vybere se nejvolnější pravidlo, „ať nic nespadne" | Zabetonoval jsi ten nejhorší z těch tří stavů | Rozhodnout s doménou |

---

## Kam to vede

Po dokončení máš [**Value Object**](../../../SoftwareDesign/DDD/ValueObject/) — hodnotu s vlastním typem, validací a chováním.

Ten dokument navazuje tam, kde tenhle končí: rozebírá **rovnost podle hodnoty**, neměnnost, kompozitní hodnoty složené z jiných hodnot a invarianty, které žádná složka sama neuhlídá. A ukazuje, že `DateTimeImmutable` v PHP je value object, který používáš každý den.

Když se ukáže, že hodnota má **omezenou sadu variant** (stavy, typy, kódy), cíl je jinde: **enum**, případně [State](../../../SoftwareDesign/GoF/Behavioral/State/). Fowler proto tenhle refaktoring vede i pod starším názvem *Replace Type Code with Class*.

---

## Demo

```bash
php Refactoring/Code/ReplacePrimitiveWithObject/demo/run.php
```

SKU jako řetězec proti SKU jako typu. Demo pustí sedm vstupů přes tři různé validace v projektu a spočítá, že **šest z nich dopadne jinak podle toho, kudy do systému přišly**. Pak ukáže košík, ve kterém `MON-27`, `mon-27` a ` MON-27 ` figurují jako tři různé položky. Po refaktoringu je z toho jedna položka o třech kusech, neplatné SKU nevznikne a typ umí navíc `productGroup()` — znalost, která se dřív odvozovala přes `substr()` všude, kde byla potřeba.

---

## Související

| Dokument | Vztah |
| -------- | ----- |
| [Value Object](../../../SoftwareDesign/DDD/ValueObject/) (DDD) | **Cíl refaktoringu.** Navazuje rovností podle hodnoty, neměnností a kompozitními hodnotami. |
| [Encapsulate Collection](../EncapsulateCollection/) | Druhá půlka téhož problému: tam se zapouzdřuje skupina, tady jednotlivá hodnota. |
| [Factory](../../../SoftwareDesign/DDD/Factory/) (DDD) | `fromString()` je pojmenovaná továrna; proto je konstruktor soukromý. |
| [Fail Fast](../../../SoftwareDesign/Principles/ObjectDesign.md#fail-fast) | Neplatná hodnota nevznikne — chyba se ohlásí při vzniku, ne při použití. |
| [State](../../../SoftwareDesign/GoF/Behavioral/State/) (GoF) | Kam vede varianta *Replace Type Code with Class*, když má hodnota omezenou sadu variant. |
| [Entity](../../../SoftwareDesign/DDD/Entity/) (DDD) | Typ pro identifikátor (`OrderId`) je tentýž refaktoring aplikovaný na klíč. |
| [Replace Constructor with Factory Method](../ReplaceConstructorWithFactoryMethod/) | Tentýž vzor u objektu místo u hodnoty — `fromString()` je pojmenovaná továrna. |
| [Charakterizační testy](../../CharacterizationTests/) | **Krok nula.** Co dělat, když testy neexistují a zadání, podle kterého by se napsaly, taky ne. |

| [Comprehension refactoring](../../ComprehensionRefactoring/) | Často to, co tomuhle refaktoringu předchází — pojem nejdřív dostane jméno, pak teprve typ. |
| [Introduce Parameter Object](../IntroduceParameterObject/) | Sousední případ: skupina hodnot, které chodí spolu, místo jedné. |
---

## Původ

|             |                    |
| ----------- | ------------------ |
| **Autor**   | Martin Fowler      |
| **Rok**     | 1999, přepracováno 2018 |
| **Zdroj**   | *Refactoring*, katalog |
| **Náročnost** | ●●○○○            |

V katalogu je od prvního vydání, tehdy pod názvem **Replace Data Value with Object**; druhé vydání ho přejmenovalo na dnešní a uvádí i třetí jméno, **Replace Type Code with Class**. Ta tři jména odpovídají třem situacím, ve kterých se používá — prostá hodnota, datová položka a kód typu.

Fowlerův příklad v katalogu za pozornost stojí, protože nemíří na validaci:

```javascript
// před
orders.filter(o => "high" === o.priority || "rush" === o.priority);

// po
orders.filter(o => o.priority.higherThan(new Priority("normal")))
```

Ukazuje **porovnávání**: řetězec nemá pořadí, takže se musí vyjmenovat všechny hodnoty, které jsou „vyšší". Objekt může mít `higherThan()` — a nová priorita se pak přidá na jednom místě.

Náročnost je dvojka, ale je jiného druhu než u ostatních refaktoringů v téhle složce. Mechanika je triviální; **cena je v rozsahu a v jednom rozhodnutí**:

- **Primitivum bývá všude** a refaktoring se dotkne desítek souborů.
- **Někdo musí říct, které z těch tří pravidel platí.** To není technická otázka a bez odpovědi se nedá začít.
- **Testy, které se opíraly o volnější validaci, spadnou** — a je správně, že spadnou.

---

## Zdroje

- Martin Fowler: [*Replace Primitive with Object*](https://refactoring.com/catalog/replacePrimitiveWithObject.html) — katalog online
- Martin Fowler: *Refactoring*, 2. vydání, Addison-Wesley, 2018

---

<details>
<summary>Metadata techniky</summary>

```yaml
name: Replace Primitive with Object
level: code
author: Martin Fowler
year: 1999
duration: hodiny až dny
reversible: ano
requires_tests: ano
difficulty: 2
tags: [primitivní obsese, validace, normalizace, typy]
leads_to: [ValueObject]
related: [ValueObject, EncapsulateCollection, Factory, State, Entity]
status: done
```

</details>
