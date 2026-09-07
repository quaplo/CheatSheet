# Charakterizační testy

> [← zpět na Refaktoring](../)

> **V jedné větě:** Testy, které nepopisují, co má kód dělat, ale **co dělá** — aby se dal bezpečně měnit, i když specifikace neexistuje.

> [!IMPORTANT]
> Tohle je **krok nula všech ostatních technik** v téhle sekci. Každá z nich začíná větou „musí existovat testy" — a tenhle dokument odpovídá na otázku, co dělat, když neexistují a napsat je podle zadání nejde, protože zadání není.

---

## Kdy po tom sáhnout

Michael Feathers definuje legacy kód nezvykle úzce: **je to kód bez testů.** Ne starý, ne ošklivý — netestovaný. Protože právě testy rozhodují o tom, jestli se dá měnit.

**Poznáš to podle:**

- kód počítá něco důležitého a **nikdo neví, proč zrovna takhle**
- specifikace neexistuje, nebo popisuje verzi z roku 2019
- poslední člověk, který tomu rozuměl, **už tu nepracuje**
- „radši na to nešahej" je oficiální doporučení
- chceš to refaktorovat a nemáš se čeho chytit

```php
public function finalPriceInCents(int $base, int $quantity, string $customerType): int
{
    // …vrstvy pravidel z několika let, každé přidal někdo jiný…

    if ($quantity === 13) {
        $price += 100;   // Nikdo neví, odkud se tohle vzalo.
    }

    return max($price, 0);
}
```

---

## Mechanika

Feathersův postup je krátký a jedna jeho část je kontraintuitivní.

### 1. Napiš tvrzení, o kterém víš, že selže

```php
$this->assertSame(0, $pricing->finalPriceInCents(10000, 5, 'standard'));
```

```
pět kusů, standardní zákazník — očekáváno 0, dostal jsem 50000
```

**Selhání právě prozradilo, co ten kód dělá.**

Proč zrovna nula, když je zjevně špatně? Protože **záměrně selhávající tvrzení je falzifikovatelný pokus**. Kdyby neselhalo, znamená to, že test nic neověřuje — a tautologických testů vzniká překvapivě hodně.

**Po tomhle kroku platí:** znáš jednu skutečnou hodnotu.

### 2. Zapiš skutečnou hodnotu

```php
$this->assertSame(50000, $pricing->finalPriceInCents(10000, 5, 'standard'));
```

**Po tomhle kroku platí:** máš první charakterizační test.

### 3. Najdi hranice

Testovat se má tam, kde jsou větve — jenže u cizího kódu nevíš, kde jsou. Dá se to zjistit i bez čtení: **pusť funkci na rozsahu vstupů a hledej, kde se výstup zachová jinak.**

```
prohledáno množství:   1–60
nalezené skoky:        10, 11, 13, 14, 15, 50, 51

množství      cena              rozdíl proti předchozímu
9             900,00 Kč         100,00 Kč
10            900,00 Kč         0,00 Kč
13            1 171,00 Kč       91,00 Kč
14            1 260,00 Kč       89,00 Kč
50            4 275,00 Kč       -135,00 Kč
```

Skoky u 10 a 50 jsou množstevní slevy. **A u 13 je něco, co nikdo nečekal.**

**Po tomhle kroku platí:** víš, kam napsat testy nejdřív.

### 4. Zapiš i to, co vypadá jako chyba

```
12 kusů       1 080,00 Kč
13 kusů       1 171,00 Kč   ← o korunu víc, než by mělo
14 kusů       1 260,00 Kč
```

```php
$this->assertSame(117100, $pricing->finalPriceInCents(10000, 13, 'standard'));
```

Tohle je ta část, která juniorům přijde špatně — a je klíčová. **Není to schvalování chyby.** Je to konstatování, že tohle kód dnes dělá, a že to při refaktoringu **nesmí zmizet nechtěně**.

Opravit se to má **samostatně**, s vědomím, že se mění chování, a s někým, kdo může říct, jestli na tom náhodou něco nezávisí. Zákazníci si mohli za tři roky zvyknout kupovat po třinácti.

**Po tomhle kroku platí:** máš síť, která zachytí každou změnu chování — i tu žádoucí.

### 5. Refaktoruj

Teprve teď. Testy říkají, jestli se chování změnilo.

---

## Jak dobrá je ta síť

Nejdůležitější část dokumentu, protože právě tady vzniká falešná jistota.

Demo pouští **rozbitý přepis** — takový, který zaokrouhluje jednou na konci místo po každém kroku — proti dvěma sadám:

```
sada                      případů     zachyceno rozdílů
úzká (jedna cena)         21          0
široká (sedm cen)         168         37
```

**Rozbitý přepis prošel úzkou sadou beze zbytku — a přitom mění ceny.**

```
333 × 25 ks, vip — očekáváno 6967, dostal jsem 6968
333 × 50 ks, partner — očekáváno 12099, dostal jsem 12100
```

Úzká sada testovala jednu základní cenu (10 000 haléřů), která se dělí beze zbytku. U 333 už ne. **Charakterizační testy jsou přesně tak dobré jako sada vstupů** — a zelená sada neznamená, že se chování nezměnilo, jen že se nezměnilo tam, kam ses podíval.

Praktické důsledky:

- **Měň víc než jeden vstup.** Sada, kde se hýbe jen jedna proměnná, najde jen jednu třídu chyb.
- **U peněz a zaokrouhlování ber čísla, která se nedělí beze zbytku.** 333, 999, 4999.
- **Použij skutečná produkční data**, když jdou získat. Jsou lepší než vymyšlená.
- **Změř pokrytí.** Ne jako cíl, ale jako mapu toho, kam ses nepodíval.

---

## Co to stojí

| Cena | Kdy se vyplatí |
| ---- | -------------- |
| **Testy, které se po refaktoringu zahodí** | Skoro vždy — jsou to lešení, ne stavba |
| **Zapisují i chyby** | Když je cílem bezpečná změna, ne oprava |
| **Falešná jistota u úzké sady** | Nikdy; proto je potřeba sadu rozšiřovat |
| **Čas na hledání hranic** | Když je kód netriviální a nikdo mu nerozumí |

První řádek je důležitý pro to, jak se na ně dívat. **Charakterizační testy nejsou dobré testy** — nepopisují záměr, popisují stav. Jakmile kód rozumíš a máš specifikaci, nahradí se testy, které říkají, co má být. Do té doby jsou jediná síť, kterou máš.

---

## Kdy to nedělat

- ❌ **Testy existují a jsou dobré.** Pak není co charakterizovat.
- ❌ **Kód se má zahodit, ne měnit.** Síť pro něco, co za měsíc zmizí, je práce nazmar.
- ❌ **Chování je nedeterministické** (čas, náhoda, externí služba). Nejdřív se musí izolovat — Feathers tomu říká hledání švů.
- ❌ **Chceš tím opravit chybu.** Charakterizační test chybu zapíše, ne opraví; oprava je samostatný krok.

---

## Časté chyby

| Chyba | Proč vadí | Jak správně |
| ----- | --------- | ----------- |
| Test se napíše rovnou se správnou hodnotou | Snadno vznikne tautologie, která nic neověřuje | Napsat tvrzení, které selže, a hodnotu opsat ze selhání |
| Zapíše se „jak to má být" | To je specifikační test; k refaktoringu je nepoužitelný | Zapsat, co kód dělá |
| Chyba se při psaní testu rovnou opraví | Změna chování schovaná v přípravě | Zapsat, opravit samostatně |
| Úzká sada vstupů | Falešná jistota — [viz výš](#jak-dobrá-je-ta-síť) | Měnit víc proměnných, brát nedělitelná čísla |
| Testy se nechají navždy | Zabetonují chování včetně chyb | Nahradit specifikačními, až kód rozumíš |
| Netestuje se na hranicích | Vetve zůstanou nepokryté | Najít skoky ve výstupu |
| Testuje se přes celý systém | Pomalé a křehké; nepustí se často | Izolovat kus, který se mění |

---

## Souvislost s ostatními technikami

Tenhle dokument je předpokladem všech ostatních v sekci:

| Technika | Jak souvisí |
| -------- | ----------- |
| [Replace Conditional with Polymorphism](../Code/ReplaceConditionalWithPolymorphism/) | Testy na **všechny větve** dřív, než se rozdělí do tříd |
| [Encapsulate Collection](../Code/EncapsulateCollection/) | Testy na operace nad kolekcí, které se budou stěhovat |
| [Replace Primitive with Object](../Code/ReplacePrimitiveWithObject/) | Testy odhalí, že tři místa validují jinak — část z nich pak spadne, a je to nález |
| [Extract Class](../Code/ExtractClass/) | Testy drží chování, zatímco se stěhují pole a metody |
| [Replace Constructor with Factory Method](../Code/ReplaceConstructorWithFactoryMethod/) | Testy na všechny kombinace, které konstruktor připouští |
| [Parallel Run](../System/ParallelRun/) | **Totéž o úroveň výš.** Charakterizační test porovnává se zapsanou hodnotou, Parallel Run se skutečným během staré verze na produkci. |

Poslední řádek stojí za rozvedení. Když se sada vstupů nedá sestavit dost dobře — protože reálný provoz je pestřejší než cokoli vymyšleného — je [Parallel Run](../System/ParallelRun/) přirozené pokračování: **místo zapsaných hodnot se porovnává s běžící starou implementací.**

---

## Demo

```bash
php Refactoring/CharacterizationTests/demo/run.php
```

Legacy výpočet ceny, kterému nikdo nerozumí. Demo napíše tvrzení, o kterém ví, že selže, a **ze selhání opíše skutečnou hodnotu**. Pak automaticky **najde hranice** — pustí funkci na rozsahu množství a najde místa, kde se výstup skokově mění; kromě očekávaných slev objeví i příplatek u třinácti kusů, o kterém nikdo nevěděl.

Závěr je nejdůležitější: pustí **rozbitý přepis** proti dvěma sadám testů. Úzká sada (jedna základní cena) ho pustí dál se skóre 21 z 21; široká sada (sedm cen) zachytí **37 rozdílů**. Ukazuje tím, že zelená sada neznamená zachované chování — jen že se nezměnilo tam, kam ses podíval.

---

## Související

| Dokument | Vztah |
| -------- | ----- |
| [Refaktoring](../) | Všechny techniky v sekci tenhle krok předpokládají. |
| [Parallel Run](../System/ParallelRun/) | Pokračování, když se sada vstupů nedá sestavit dost dobře. |
| [Code review](../../Processes/CodeReview/) | Charakterizační testy a oprava chyby patří do **oddělených** pull requestů. |
| [Extreme Programming](../../Processes/ExtremeProgramming/) | Odkud pochází důraz na testy jako podmínku průběžné změny. |
| [Trunk-Based Development](../../GitWorkflows/TrunkBasedDevelopment/) | Bez sítě se denní integrace do hlavní větve dělat nedá. |
| [Přípravný refaktoring](../PreparatoryRefactoring/) | **Kdy** se do toho pouštět. Charakterizační testy jsou to, s čím — obojí předchází všem technikám. |
| [Comprehension refactoring](../ComprehensionRefactoring/) | Druhá polovina téhož: testy zapíší **chování**, comprehension refactoring **význam**. |
| [Litter-pickup refactoring](../LitterPickupRefactoring/) | Zelené testy jsou podmínka i pro ten nejmenší úklid. |
| [Plánovaný refaktoring](../PlannedRefactoring/) | U cizího modulu, na který je story, obvykle testy chybí — začíná se tady. |
| [TDD refaktoring](../TddRefactoring/) | Opačný případ: tam testy vznikly první a refaktoring je má zadarmo. |
| [Dva klobouky](../TwoHats/) | Zelená sada je podmínka celého pravidla — tohle je způsob, jak ji získat. |
| [Dlouhodobý refaktoring](../LongTermRefactoring/) | Kde se síť musí udržet po celé měsíce přestavby. |
| [Mikado metoda](../MikadoMethod/) | Stojí na tom, že se dá spolehlivě zjistit, co je rozbité. |

---

## Původ

|             |                    |
| ----------- | ------------------ |
| **Autor**   | Michael Feathers   |
| **Rok**     | 2004               |
| **Zdroj**   | *Working Effectively with Legacy Code* |
| **Náročnost** | ●●○○○            |

Feathers techniku popsal v knize, která dodnes zůstává hlavní referencí pro práci se starým kódem. Nejcitovanější věc z ní je definice legacy kódu — **„code without tests"** — a je záměrně provokativní: říká, že o tom, jestli se dá s kódem pracovat, nerozhoduje jeho stáří ani kvalita, ale existence sítě.

Postup samotný je pár řádků a jeho nejchytřejší část je ta, která vypadá jako chyba: **psát tvrzení, o kterém víš, že selže.** Feathers to zdůvodňuje tím, že jde o falzifikovatelný pokus — kdyby neselhalo, test by nic neověřoval. Tautologické testy vznikají snadno a nikdo si jich nevšimne, protože jsou zelené.

Náročnost je dvojka. Mechanika je triviální, ale dvě věci jsou nepříjemné:

- **Zapisovat chyby jako správné chování** jde proti instinktu a musí se vysvětlit — jinak to někdo „při tom" opraví.
- **Sada vstupů rozhoduje o všem** a její nedostatečnost se nepozná. Zelená sada vypadá stejně, ať je dobrá nebo špatná.

Za zmínku stojí, že u knihy z roku 2004 se skoro nic nezastaralo. Techniky jako hledání švů, *sprout method* a *wrap method* platí beze změny — jen se jim dnes někdy říká jinak.

---

## Zdroje

- Michael Feathers: *Working Effectively with Legacy Code*, Prentice Hall, 2004
- Michael Feathers: [*Characterization Testing*](https://michaelfeathers.silvrback.com/characterization-testing)
- [Characterization test](https://en.wikipedia.org/wiki/Characterization_test)

---

<details>
<summary>Metadata techniky</summary>

```yaml
name: Charakterizační testy
level: příprava
author: Michael Feathers
year: 2004
duration: hodiny až dny
reversible: ano — testy se dají zahodit
requires_tests: je to ono
difficulty: 2
tags: [legacy, testy, síť, hranice, Feathers]
leads_to: []
related: [ParallelRun, ReplaceConditionalWithPolymorphism, ExtractClass, CodeReview]
status: done
```

</details>
