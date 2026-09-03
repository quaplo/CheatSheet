# Jednoduchost: KISS, YAGNI, DRY

> [← zpět na Principy](README.md)

> **V jedné větě:** Tři principy o tom, **kolik kódu psát a kdy** — a jedno pravidlo, které rozhoduje, když si odporují.

Tyhle tři se citují nejčastěji ze všech a taky se nejčastěji používají jako záminka. DRY tlačí ke sjednocování, YAGNI k odkládání, KISS k ubírání — a když je někdo použije bez pochopení, dokáže jimi obhájit skoro cokoli. Proto je tady dohromady: dávají smysl jen ve vzájemném napětí.

| Zkratka | Princip | V jedné větě |
| ------- | ------- | ------------ |
| **KISS** | [Keep It Simple](#kiss--keep-it-simple) | Nejjednodušší řešení, které opravdu funguje. |
| **YAGNI** | [You Aren't Gonna Need It](#yagni--you-arent-gonna-need-it) | Nedělej to, co zatím nikdo nechce. |
| **DRY** | [Don't Repeat Yourself](#dry--dont-repeat-yourself) | Každá **znalost** má v systému jediné vyjádření. |
| — | [Pravidlo tří](#pravidlo-tří) | Rozhodčí mezi předchozími dvěma. |

---

## KISS — Keep It Simple

> Nejjednodušší řešení, které splní zadání. **Jednoduché neznamená naivní ani krátké** — znamená srozumitelné pro toho, kdo to bude opravovat.

Původ je mimo softwarové inženýrství: **Kelly Johnson**, konstruktér letadel v Lockheed Skunk Works, kolem roku **1960**. Jeho zadání znělo, že letoun musí být opravitelný průměrným mechanikem s běžným nářadím v polních podmínkách. To je přesně to správné čtení: jednoduché = **opravitelné pod tlakem někým jiným než tebou**.

**Poznáš porušení podle:**

- chytrého jednořádkového výrazu, který si musíš po týdnu rozebrat, abys ho pochopil
- abstrakce, která má jednu implementaci a nikdy nebude mít druhou
- konfigurovatelnosti něčeho, co se za pět let nezměnilo
- vrstvy, kterou nikdo neumí vysvětlit jednou větou

```php
// Chytré
$result = array_values(array_filter(array_map(
    static fn (array $r): ?Order => $r['valid'] ? Order::fromRow($r) : null,
    $rows,
)));

// Jednoduché — a když spadne, hned víš kde
$orders = [];

foreach ($rows as $row) {
    if ($row['valid']) {
        $orders[] = Order::fromRow($row);
    }
}
```

**Pozor na zneužití.** KISS se často používá jako obhajoba toho, že se někdo nechce učit. Pattern není složitost — pattern je **pojmenovaná** složitost, a ta je vždycky levnější než nepojmenovaná. Když ti kolega řekne „to je moc složité, KISS“, zeptej se ho, jaké **konkrétní** jednodušší řešení navrhuje. Když žádné nemá, není to argument.

**Souvisí s patterny:** všechny sekce *Kdy nepoužít* v tomhle katalogu jsou v podstatě aplikované KISS.

---

## YAGNI — You Aren't Gonna Need It

> Implementuj věci ve chvíli, kdy je opravdu potřebuješ — ne když tušíš, že je jednou potřebovat budeš.

Pochází z [extrémního programování](../../Processes/ExtremeProgramming/), formuloval ho **Ron Jeffries** kolem roku **1998**. Argument není „nepřemýšlej dopředu“, ale ekonomický: za funkci, kterou postavíš předčasně, platíš **čtyřikrát**.

| Cena | Co to je |
| ---- | -------- |
| **Postavení** | Čas, který jsi mohl dát tomu, co je potřeba teď |
| **Odkladu** | To, co se kvůli tomu nestihlo, a hodnota, která tím nevznikla |
| **Nošení** | Každé čtení, každý refaktoring a každý test navíc — **platí se denně** |
| **Opravy** | Když se ukáže, že požadavek vypadá jinak, než jsi hádal |

Poslední dva jsou ty zákeřné. Nepotřebná abstrakce nestojí jen tu hodinu, co jsi ji psal — stojí každé další čtení kódu, dokud existuje.

**Poznáš porušení podle:**

- rozhraní s jedinou implementací, „kdyby náhodou přišla druhá“
- konfiguračních voleb, které nikdo nikdy nepřepnul
- obecného řešení pro jeden konkrétní případ
- vět typu „to se nám bude jednou hodit“ v code review

```php
// „Až budeme mít víc typů dopravců, bude to připravené.“
interface ShippingProviderFactoryInterface { /* … */ }
abstract class AbstractShippingProviderFactory { /* … */ }
final class DefaultShippingProviderFactory extends AbstractShippingProviderFactory { /* … */ }

// Zatím máme jednoho dopravce.
final class CzechPostShipping { /* … */ }
```

**Kde YAGNI neplatí.** To je ta část, kterou lidé vynechávají. YAGNI se týká **funkcí, ne kvality** — není to argument pro vynechání testů, pro špatné pojmenování ani pro nepořádek. A neplatí u věcí, které se **draho dodělávají zpětně**: bezpečnost, možnost migrace dat, hranice mezi službami. Tam je předvídavost levnější než oprava.

**Souvisí s patterny:** [Rules Engine](../Architecture/RulesEngine/) a [CQRS](../Architecture/CQRS/) mají obě škálu stupňů právě proto, že jsou to typičtí kandidáti na porušení YAGNI.

---

## DRY — Don't Repeat Yourself

> **Každá znalost** má mít v systému jediné, jednoznačné a autoritativní vyjádření.

Formulovali ho **Andy Hunt a Dave Thomas** v *The Pragmatic Programmer* (**1999**). A teď to nejdůležitější, co se z citací obvykle vytratí:

> V té definici je slovo **znalost**. Není tam slovo **kód**.

DRY není o tom, že se dva kusy kódu podobají. Je o tom, že **jedno rozhodnutí je zapsané na víc místech** — a při změně se musí najít všechna.

### Skutečné porušení DRY

Jedno byznysové pravidlo, tři místa. Změní se hranice a jedno místo se zapomene:

```php
// v košíku
if ($order->totalInCents >= 150000) { $shipping = 0; }

// v API pro mobilní aplikaci
if ($total >= 150000) { $shipping = 0; }

// v e-mailu „ještě 200 Kč do dopravy zdarma“
$missing = 150000 - $order->totalInCents;
```

Tohle je opravdové DRY porušení. Řešení je dát znalosti jméno a jedno místo — [Specification](../DDD/Specification/), [Value Object](../DDD/ValueObject/) nebo prostě pojmenovaná konstanta.

### Falešné porušení DRY — a tam vzniká škoda

Dva kusy kódu vypadají stejně, ale **mění se z různých důvodů**:

```php
// Validace uživatelského jména
if (mb_strlen($username) > 20) {
    throw new InvalidArgumentException('Jméno je příliš dlouhé.');
}

// Validace kódu produktu
if (mb_strlen($productCode) > 20) {
    throw new InvalidArgumentException('Kód je příliš dlouhý.');
}
```

Vypadá to jako duplicita a svědí to. Ale ta dvacítka **není tatáž dvacítka** — jedna vychází z návrhu registrace, druhá z číselníku dodavatele. Když je sjednotíš do `validateMaxLength20()`, spojil jsi dvě nezávislé věci a při první změně jedné z nich budeš tu abstrakci rozplétat zpátky.

Sandi Metz to shrnula větou, kterou stojí za to si zapamatovat:

> **Duplikace je mnohem levnější než špatná abstrakce.**

Špatná abstrakce totiž nezmizí — přibývají do ní parametry a příznaky (`validateLength($s, $max, $allowEmpty, $trim, $isProductCode)`), až se z ní stane nečitelná změť, kterou se nikdo neodváží smazat.

**Poznáš porušení podle:**

- ✅ **Opravdové:** změna jednoho pravidla si vynutí zásah na víc místech; jedno se zapomene a vznikne rozpor
- ✅ **Opravdové:** tatáž konstanta (hranice, sazba, limit) je zapsaná v kódu vícekrát
- ❌ **Falešné:** kód vypadá stejně, ale patří dvěma různým doménovým pojmům
- ❌ **Falešné:** společná metoda, která má parametr typu `bool $isForAdmin` — to je znamení, že jsi spojil dvě věci

**Kontrolní otázka:** *Když se změní tohle, musí se nutně změnit i to druhé?* Ano → jedna znalost, sjednoť. Ne → dvě znalosti, nech je být.

**Souvisí s patterny:** [Value Object](../DDD/ValueObject/) (pravidlo o hodnotě má jedno místo) · [Specification](../DDD/Specification/) (pravidlo dostane jméno) · [First Class Collection](../ObjectCalisthenics/FirstClassCollection/) (pravidla o skupině) · [Bounded Context](../DDD/BoundedContext/) — **totéž falešné DRY v měřítku celé firmy**: tři modely `Customer` nejsou duplicita, jsou to tři nezávislé znalosti

---

## Pravidlo tří

> Poprvé to prostě napíšeš. Podruhé ti duplicita vadí, ale napíšeš to znovu. **Až potřetí to sjednotíš.**

Přisuzuje se **Donu Robertsovi**, do širokého povědomí ho dostal Martin Fowler v *Refactoringu* (**1999**).

Není to lenost — je to rozhodčí mezi DRY a YAGNI. Ty dva principy si totiž odporují: DRY říká *sjednoť hned*, YAGNI říká *nestav dopředu*. Pravidlo tří říká, **kdy máš dost informací**:

- Při **prvním** výskytu nevíš vůbec nic o tom, jak se to bude měnit.
- Při **druhém** máš jeden datový bod. To na návrh abstrakce nestačí — vidíš, v čem se ty dva případy shodují, ale ne, v čem se budou lišit.
- Při **třetím** už vidíš vzorec. Teprve teď dokážeš navrhnout abstrakci, která tu čtvrtou změnu přežije.

Praktický dopad: **dvojí výskyt není důvod k refaktoringu**, je to důvod si toho všimnout. To je zároveň nejlepší obrana proti falešnému DRY z předchozí sekce.

---

## Jak spolu vycházejí

| Situace | Co říká který princip | Kdo má pravdu |
| ------- | --------------------- | ------------- |
| Vidím druhou kopii kódu | DRY: sjednoť · Pravidlo tří: počkej | **Pravidlo tří** |
| Chci přidat rozhraní pro budoucí druhou implementaci | YAGNI: nedělej to | **YAGNI** |
| Řešení je funkční, ale nikdo mu nerozumí | KISS: zjednoduš | **KISS** |
| Jedno pravidlo je zapsané na třech místech | DRY: sjednoť · YAGNI: to není funkce navíc | **DRY** — a hned |
| Chci zavést pattern, protože je „správný“ | KISS + YAGNI: jaký konkrétní problém řeší? | **KISS + YAGNI** |

Když si dva principy odporují, vyhrává skoro vždycky ten, který ti říká **méně psát**. Kód, který nenapíšeš, se nemůže pokazit.

---

## Původ

| Princip | Autor | Rok |
| ------- | ----- | --- |
| **KISS** | Kelly Johnson (Lockheed Skunk Works) | cca 1960 |
| **YAGNI** | Ron Jeffries, Kent Beck (extrémní programování) | cca 1998 |
| **DRY** | Andy Hunt, Dave Thomas — *The Pragmatic Programmer* | 1999 |
| **Pravidlo tří** | Don Roberts; rozšířil Martin Fowler — *Refactoring* | 1999 |

Stojí za povšimnutí, že tři ze čtyř vznikly na přelomu tisíciletí, v době, kdy se objektové programování dostávalo do praxe a ukázalo se, že hlavní riziko není nedostatek abstrakce, ale její přebytek. Všechny čtyři jsou reakcí na totéž — na kód, který někdo napsal pro budoucnost, která nikdy nepřišla.

---

## Zdroje

- Andy Hunt, Dave Thomas: *The Pragmatic Programmer*, Addison-Wesley, 1999 — DRY
- Martin Fowler: *Yagni*, 2015 — [martinfowler.com/bliki/Yagni.html](https://martinfowler.com/bliki/Yagni.html)
- Martin Fowler: *Refactoring*, Addison-Wesley, 1999 — pravidlo tří
- Sandi Metz: *The Wrong Abstraction*, 2016 — [sandimetz.com/blog/2016/1/20/the-wrong-abstraction](https://sandimetz.com/blog/2016/1/20/the-wrong-abstraction)
