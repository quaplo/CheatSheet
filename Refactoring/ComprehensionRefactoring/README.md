# Comprehension refactoring

> [← zpět na Refaktoring](../)

> **V jedné větě:** Když kódu nerozumíš, uprav ho tak, aby bylo vidět, co dělá — a to, cos pochopil, tím zůstane zapsané v kódu místo v tvé hlavě.

Ward Cunningham to popsal větou, která z toho dělá něco jiného než úklid:

> „By refactoring I move the understanding **from my head into the code itself**."
>
> — Ward Cunningham

Rozdíl je v tom, **kde ta znalost skončí**. Když si kód jen přečteš a pochopíš ho, máš to v hlavě — a za tři týdny už ne. Když ho při čtení průběžně upravuješ, zůstane to v kódu i po tobě.

Ralph Johnson k tomu přidal obraz, který sedí ještě líp: takový refaktoring je jako **„wiping the dirt off a window so you can see beyond"**. Okno se nemění. Jen je přes něj konečně vidět.

---

## Kdy po tom sáhnout

Spouštěčem není rozhodnutí refaktorovat. Spouštěčem je, že **něco potřebuješ v cizím kódu zjistit** a nejde to.

**Poznáš to podle:**

- čteš metodu potřetí a pořád nevíš, co ta proměnná `$s` drží
- kreslíš si to na papír, aby ses v tom vyznal
- ptáš se kolegy „co dělá tenhle `if`" a on to taky neví
- zjišťuješ, proč přišla reklamace, a nedokážeš najít, kde se ta částka počítá
- v hlavě si to průběžně překládáš („`$t` je asi zákaznická úroveň")

Ten poslední bod je ten podstatný. **Když si kód v hlavě překládáš, děláš práci, kterou příště bude muset udělat někdo znovu.** Comprehension refactoring znamená ten překlad zapsat.

---

## Jak to vypadá

Metoda, která počítá cenu objednávky. Testy k ní existují a procházejí — chování se tedy měnit nesmí.

```php
public function calc(array $d, int $t, bool $f): int
{
    $r = 0;

    foreach ($d as $i) {
        $s = $i[0] * $i[1];

        if ($i[1] >= 100 && $t <= 2) {
            $s = (int) ($s * 0.8);
        } elseif ($i[1] >= 100) {
            $s = (int) ($s * 0.9);
        } elseif ($t <= 2) {
            $s = (int) ($s * 0.95);
        }

        if ($i[2] === 1) {
            $s += 2500;
        }

        $r += $s;
    }

    if ($f && $r < 100000) {
        $r += 9900;
    }

    return $r;
}
```

### Krok 1: pojmenovat proměnné

Nic víc. Struktura zůstane přesně stejná, jen `$d` je `$items`, `$t` je `$customerTier`, `$s` je `$subtotal`.

```php
foreach ($items as $item) {
    [$unitPrice, $quantity, $isGiftWrapped] = $item;
    $subtotal = $unitPrice * $quantity;
```

**Po tomhle kroku platí:** kód dělá totéž, ale dá se číst bez papíru vedle. Zajímavé je, že žádná metrika se nezměnila — složitost, zanoření i počet magických čísel jsou stejné jako předtím. Demo to ukazuje černé na bílém.

### Krok 2: pojmenovat podmínky

```php
$isWholesale = $quantity >= self::WHOLESALE_QUANTITY;
$isPremiumCustomer = $customerTier <= self::PREMIUM_TIER;

if ($isWholesale && $isPremiumCustomer) {
    $subtotal = (int) ($subtotal * 0.8);
} elseif ($isWholesale) {
```

**Tady se to láme.** Ve chvíli, kdy podmínka dostane jméno, musíš to jméno vymyslet — a tím se ukáže, že kód zná pravidla, o kterých nikde nic není:

| Bylo v kódu jako | Ve skutečnosti znamená |
| ---------------- | ---------------------- |
| `$i[1] >= 100` | sto kusů a víc je **velkoobchodní množství** |
| `$t <= 2` | zákaznická úroveň 1 a 2 je **prémiový zákazník** |
| první větev | obojí zároveň má **vlastní, vyšší slevu** (20 %, ne 10 + 5) |
| `$s += 2500` | příplatek za **dárkové balení** |
| `$r < 100000` | **doprava zdarma od tisíce korun** |

Ta pravidla nikdo nevymyslel při refaktoringu. Byla tam celou dobu. Refaktoring je jen **pojmenoval**.

### Krok 3: rozdělit na věty

```php
public function calc(array $items, int $customerTier, bool $addShipping): int
{
    $total = 0;

    foreach ($items as $item) {
        $total += $this->lineTotal($item, $customerTier);
    }

    return $total + $this->shippingFor($total, $addShipping);
}
```

**Po tomhle kroku platí:** hlavní metoda se dá přečíst nahlas a dává smysl. Každá privátní metoda odpovídá na jednu otázku a její jméno je ta odpověď — `isWholesale()`, `discounted()`, `shippingFor()`.

### Kde se dá zastavit

**Po každém kroku.** To je na tomhle postupu to nejlepší — chování se nemění nikdy, takže se dá skončit kdykoli a nic nezůstane rozdělané. Když ti stačí krok 1, skončíš po kroku 1.

---

## Co se tím opravdu získalo

|  | Bez refaktoringu | Po refaktoringu |
| --- | --- | --- |
| Kde je pochopení | v hlavě | v kódu |
| Jak dlouho vydrží | týdny | dokud kód existuje |
| Kdo se k němu dostane | kdo se zeptá | kdokoli |
| Co když odejdu | začíná se znovu | zůstává |

To poslední je důvod, proč se tomu vyplatí věnovat čas i u kódu, který se nechystáš měnit. **Pochopení cizího kódu je práce, která se dělá pokaždé znovu** — pokud se nezapíše.

---

## Proč ne komentář nebo wiki

Nabízí se levnější varianta: pochopil jsem to, tak si to napíšu do komentáře. Nebo na wiki.

| | Komentář | Wiki | Kód |
| --- | --- | --- | --- |
| Cena teď | nejnižší | nízká | vyšší |
| Když se kód změní | zůstane starý | nikdo ho nenajde | **musí se změnit taky** |
| Kde ho čtenář hledá | u toho místa | nehledá | **kouká přímo na něj** |
| Dá se ověřit testem | ne | ne | **ano** |

Prostřední řádek je celý rozdíl. **Komentář a wiki se rozejdou s kódem a nikdo se to nedozví.** Jméno metody se rozejít nemůže — když metoda přestane dělat to, co říká její jméno, je to chyba, kterou někdo nahlásí.

To neznamená, že se komentáře nepíšou. Znamená to, že **komentář patří na to, co kód říct neumí** — proč je to tak, ne co to dělá. „Sazba 0.8 je z rámcové smlouvy z roku 2019" je dobrý komentář. „Aplikuje slevu" nad metodou `applyDiscount()` není.

---

## Bez čeho to nejde

Comprehension refactoring je změna kódu, kterému **z definice nerozumíš**. To je horší výchozí pozice než u kteréhokoli jiného refaktoringu — nemáš jak poznat, žes něco rozbil.

Proto:

1. **Nejdřív [charakterizační testy](../CharacterizationTests/).** Zapíší, co kód dělá teď, bez ohledu na to, jestli je to správně.
2. **Malé kroky, po každém spustit testy.** Přejmenování proměnné je bezpečné. Pět přejmenování a přesun metody najednou už ne.
3. **Použij nástroj, ne ruce.** Přejmenování přes IDE (PhpStorm: `Shift+F6`) nesáhne na řetězec, který se jen náhodou jmenuje stejně. `Ctrl+R` ano.

Ty dvě techniky jsou vlastně dvě poloviny téhož: **charakterizační testy zapíší chování, comprehension refactoring zapíše význam.** Obojí je způsob, jak z hlavy dostat ven něco, co v kódu nebylo napsané.

---

## Kdy to nedělat

> [!IMPORTANT]
> **Nespouštěj to jako samostatný úkol.** Comprehension refactoring je součást čtení kódu, ne položka v plánu. Ve chvíli, kdy někdo v týmu řekne „udělám průchod a pojmenuju to tam", je to *Planned refactoring* — a ten se plánuje jinak a schvaluje jinak.

| Situace | Proč ne | Co místo toho |
| ------- | ------- | ------------- |
| Kód, který se za měsíc smaže | Investuješ do něčeho, co zmizí | Přečíst a jít dál |
| Nejsou testy a nedají se rychle udělat | Nemáš jak poznat, žes to rozbil | Nejdřív [charakterizační testy](../CharacterizationTests/) |
| Produkční incident, hoří to | Refaktoring při hašení = dvě chyby najednou | Opravit, refaktorovat zítra |
| Cizí modul, do kterého nezasahuješ | Rozbiješ diffy a code ownership někomu jinému | Napsat si to k sobě, domluvit se |
| Chování se ti nezdá správné | To už není comprehension refactoring | Nahlásit chybu, opravit zvlášť |

Poslední řádek stojí za zdůraznění. **Když při pojmenovávání zjistíš, že kód dělá něco špatně, nejsi u toho, abys to opravil.** Objev si poznamenej a oprav ho jako samostatnou změnu — [dva klobouky](../PreparatoryRefactoring/#proč-to-dělat-ve-dvou-krocích) platí i tady.

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| Při pojmenovávání se rovnou opraví „chyba" | Refaktoring přestal být bezpečný a nikdo neví, co se změnilo | Objev poznamenat, opravit zvlášť |
| Jméno podle implementace | `$multipliedValue` neříká víc než `$s` | Jméno podle **domény**: `$subtotal`, `$isWholesale` |
| Pojmenuje se to a nepustí testy | Přejmenování přes `Ctrl+R` sáhne i do řetězců | Refaktoring přes IDE, testy po každém kroku |
| Skončí to přepsáním celé třídy | Z půlhodiny čtení je třídenní úkol a velký diff | Zastavit se dá po každém kroku |
| Pochopení skončí v komentáři | Za rok bude komentář lhát | Do jména metody nebo proměnné |
| Dělá se to na kód, který nikdo nečte | Investice bez návratnosti | Refaktoruj to, co právě čteš, protože to potřebuješ |

---

## Demo

```bash
php Refactoring/ComprehensionRefactoring/demo/run.php
```

Čtyři stavy téže metody — od `calc(array $d, int $t, bool $f)` po rozdělenou verzi. Demo je projede a spočítá **složitost, zanoření, magická čísla a pojmenované pojmy**:

```
krok                      složitost   zanoření    magická čísla   pojmenované pojmy
0. výchozí stav           9           3           8               0
1. pojmenované proměnné   9           3           8               0
2. pojmenované podmínky   9           3           3               5
3. rozdělené na věty      4           2           3               7
```

**První krok nezměnil ani jednu metriku** — a přesto je po něm ten kód čitelný. To je na tom to poučné: metriky měří strukturu, ne pochopení.

Zbytek dema ověří na 32 vstupech, že **všechny čtyři verze vracejí totéž** (32 z 32 shodných), a vypíše seznam pravidel, která se při pojmenovávání objevila.

---

## Související

| Dokument | Vztah |
| -------- | ----- |
| [Charakterizační testy](../CharacterizationTests/) | Síť, bez které se do cizího kódu sahat nedá. Zapíší chování, tohle zapíše význam. |
| [Litter-pickup refactoring](../LitterPickupRefactoring/) | Druhý oportunistický refaktoring — stejný postup, jiný spouštěč: „je to ošklivé" místo „nerozumím tomu". |
| [Přípravný refaktoring](../PreparatoryRefactoring/) | Druhý z Fowlerových sedmi workflow — a zdroj pravidla o dvou kloboucích. |
| [Plánovaný refaktoring](../PlannedRefactoring/) | Když je nesrozumitelný celý modul, na průběžné pojmenovávání to nestačí. |
| [Refaktoring kódu](../Code/) | Konkrétní techniky, kterými se pojmenovává. |
| [Replace Primitive with Object](../Code/ReplacePrimitiveWithObject/) | Pokračování kroku 2: pojem, který dostal jméno, může dostat i typ. |
| [Extract Class](../Code/ExtractClass/) | Pokračování kroku 3, když se ukáže, že v té třídě bydlí dvě věci. |
| [Ubiquitous Language](../../SoftwareDesign/DDD/UbiquitousLanguage/) | Jména, která se hledají, mají existovat i mimo kód — a být stejná. |
| [Zviditelni implicitní](../../SoftwareDesign/Principles/ObjectDesign.md#zviditelni-implicitní) | Přesně to, co se v kroku 2 stane s pravidly, o kterých nikde nic nebylo. |
| [Code review](../../Processes/CodeReview/Reviewer/) | Druhé místo, kde se cizí kód čte — a kde se nesrozumitelnost pozná dřív. |

---

## Původ

|             |                    |
| ----------- | ------------------ |
| **Autor**   | Ward Cunningham (myšlenka), Martin Fowler (pojmenování) |
| **Rok**     | 2014 (pojmenování) |
| **Zdroj**   | Fowler: *Workflows of Refactoring* |
| **Náročnost** | ●●○○○            |

Fowler zařadil comprehension refactoring mezi **sedm workflow refaktoringu** v článku z 8. ledna 2014 a jako zdroj myšlenky uvedl Warda Cunninghama a jeho větu o přesouvání pochopení z hlavy do kódu. Ralph Johnson pro totéž používal obraz s umytým oknem.

V tom Fowlerově seznamu patří comprehension mezi ty, o kterých **se nikoho neptáš** — je to způsob, jakým se čte kód, ne položka v plánu.

Náročnost je dvojka a je celá v sebeovládání. Mechanika je triviální: přejmenovat proměnnou, vytáhnout podmínku do pojmenovaného booleanu, rozdělit metodu. Těžké jsou dvě věci:

- **Zastavit se.** Kód je rozebraný a čitelný, sotva pár minut práce od toho, aby byl i „správně" — a to už je jiný úkol.
- **Vymyslet jméno.** Když jméno nejde vymyslet, obvykle to není o slovní zásobě. Znamená to, že ta věc nemá v doméně jméno, protože ji nikdo nepojmenoval — a to je zjištění, které patří kolegům, ne jen do kódu.

---

## Zdroje

- Martin Fowler: [*Workflows of Refactoring*](https://martinfowler.com/articles/workflowsOfRefactoring/), 2014
- Martin Fowler: [*Refactoring: Improving the Design of Existing Code*](https://martinfowler.com/books/refactoring.html), 2. vydání, 2018
- Michael Feathers: *Working Effectively with Legacy Code*, 2004 — kapitola *I Don't Understand the Code Well Enough to Change It*

---

<details>
<summary>Metadata techniky</summary>

```yaml
name: Comprehension refactoring
level: příprava
author: Ward Cunningham (myšlenka), Martin Fowler (pojmenování)
year: 2014
duration: minuty až hodiny
reversible: ano — refaktoring nic nemění
requires_tests: ano
difficulty: 2
tags: [workflow, čtení kódu, pojmenování, legacy, dva klobouky]
leads_to: [ReplacePrimitiveWithObject, ExtractClass]
related: [CharacterizationTests, PreparatoryRefactoring, UbiquitousLanguage]
status: done
```

</details>
